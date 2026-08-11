<?php

namespace App\Http\Controllers;

use App\Models\BranchShare;
use App\Models\BranchShareInvestment;
use App\Services\BranchShareService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class BranchShareController extends Controller
{
    public function __construct(
        protected BranchShareService $shareService
    ) {}

    public function index(): View
    {
        $current = $this->shareService->getOrCreateOpenShare();
        $current->load(['investments.user']);

        $summary = $this->shareService->summarizeShare($current);

        $history = BranchShare::query()
            ->with(['investments.user', 'closedByUser'])
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->paginate(12);

        $investors = $this->shareService->eligibleInvestors();

        return view('shares.index', [
            'currentShare' => $current,
            'summary' => $summary,
            'history' => $history,
            'investors' => $investors,
        ]);
    }

    public function show(BranchShare $share): View
    {
        $this->ensureShareBranchAccess($share);

        $share->load(['investments.user', 'closedByUser']);
        $summary = $this->shareService->summarizeShare($share);
        $investors = $this->shareService->eligibleInvestors((int) $share->branch_id);

        return view('shares.show', [
            'share' => $share,
            'summary' => $summary,
            'investors' => $investors,
        ]);
    }

    public function storeInvestment(Request $request, BranchShare $share): RedirectResponse
    {
        $this->ensureShareBranchAccess($share);

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->shareService->upsertInvestment(
                $share,
                (int) $validated['user_id'],
                (float) $validated['amount'],
                $validated['notes'] ?? null
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['investment' => $e->getMessage()])->withInput();
        }

        return back()->with('success', 'Investment saved successfully.');
    }

    public function updateInvestment(
        Request $request,
        BranchShare $share,
        BranchShareInvestment $investment
    ): RedirectResponse {
        $this->ensureShareBranchAccess($share);
        $this->ensureInvestmentBelongsToShare($share, $investment);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->shareService->upsertInvestment(
                $share,
                (int) $investment->user_id,
                (float) $validated['amount'],
                $validated['notes'] ?? $investment->notes
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['investment' => $e->getMessage()]);
        }

        return back()->with('success', 'Investment updated successfully.');
    }

    public function destroyInvestment(
        BranchShare $share,
        BranchShareInvestment $investment
    ): RedirectResponse {
        $this->ensureShareBranchAccess($share);
        $this->ensureInvestmentBelongsToShare($share, $investment);

        try {
            $this->shareService->removeInvestment($share, $investment);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['investment' => $e->getMessage()]);
        }

        return back()->with('success', 'Investment removed.');
    }

    public function close(BranchShare $share): RedirectResponse
    {
        $this->ensureShareBranchAccess($share);

        try {
            $closed = $this->shareService->closeMonth($share);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['close' => $e->getMessage()]);
        }

        return redirect()
            ->route('shares.show', $closed)
            ->with('success', $closed->periodLabel().' share closed. Profit has been allocated by investment.');
    }

    protected function ensureShareBranchAccess(BranchShare $share): void
    {
        $currentBranchId = $this->shareService->currentBranchId();

        if ((int) $share->branch_id !== $currentBranchId) {
            abort(404);
        }
    }

    protected function ensureInvestmentBelongsToShare(BranchShare $share, BranchShareInvestment $investment): void
    {
        if ((int) $investment->branch_share_id !== (int) $share->id) {
            abort(404);
        }
    }
}
