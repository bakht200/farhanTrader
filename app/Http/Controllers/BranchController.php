<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\User;
use App\Services\BranchStockService;
use App\Support\CurrentBranch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function index(): View
    {
        $branches = Branch::with(['users' => function ($query) {
            $query->where('role', User::ROLE_BRANCH_USER)->orderBy('name');
        }])
            ->orderBy('id')
            ->get();

        return view('branches.index', [
            'branches' => $branches,
            'currentBranchId' => CurrentBranch::id(),
        ]);
    }

    public function create(): View
    {
        return view('branches.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:branches,name'],
            'user_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        DB::transaction(function () use ($validated) {
            $branch = Branch::create([
                'name' => $validated['name'],
                'is_active' => true,
            ]);

            User::create([
                'name' => $validated['user_name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'role' => User::ROLE_BRANCH_USER,
                'branch_id' => $branch->id,
            ]);

            // Shared catalog: zero stock for every product on the new branch
            app(BranchStockService::class)->initializeBranch($branch);
        });

        return redirect()
            ->route('branches.index')
            ->with('success', 'Branch and login user created successfully.');
    }

    public function edit(Branch $branch): View
    {
        $branch->load(['users' => function ($query) {
            $query->where('role', User::ROLE_BRANCH_USER)->orderBy('name');
        }]);

        $availableUsers = User::query()
            ->with('branch')
            ->where('role', User::ROLE_BRANCH_USER)
            ->where(function ($query) use ($branch) {
                $query->whereNull('branch_id')
                    ->orWhere('branch_id', '!=', $branch->id);
            })
            ->orderBy('name')
            ->get();

        return view('branches.edit', [
            'branch' => $branch,
            'availableUsers' => $availableUsers,
        ]);
    }

    public function update(Request $request, Branch $branch): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('branches', 'name')->ignore($branch->id),
            ],
            'is_active' => ['sometimes', 'boolean'],
            'receipt_title' => ['nullable', 'string', 'max:255'],
            'receipt_subtitle' => ['nullable', 'string', 'max:255'],
            'receipt_phone' => ['nullable', 'string', 'max:50'],
            'receipt_mobile_1' => ['nullable', 'string', 'max:50'],
            'receipt_mobile_2' => ['nullable', 'string', 'max:50'],
            'receipt_email' => ['nullable', 'email', 'max:255'],
            'receipt_address' => ['nullable', 'string', 'max:500'],
        ]);

        $branch->update([
            'name' => $validated['name'],
            'is_active' => $request->boolean('is_active', $branch->is_active),
            'receipt_title' => $validated['receipt_title'] ?? null,
            'receipt_subtitle' => $validated['receipt_subtitle'] ?? null,
            'receipt_phone' => $validated['receipt_phone'] ?? null,
            'receipt_mobile_1' => $validated['receipt_mobile_1'] ?? null,
            'receipt_mobile_2' => $validated['receipt_mobile_2'] ?? null,
            'receipt_email' => $validated['receipt_email'] ?? null,
            'receipt_address' => $validated['receipt_address'] ?? null,
        ]);

        return redirect()
            ->route('branches.edit', $branch)
            ->with('success', 'Branch updated successfully.');
    }

    /**
     * Current branch receipt branding for print headers / popup.
     */
    public function receiptSettings(): JsonResponse
    {
        $branch = CurrentBranch::get();
        if (! $branch) {
            return response()->json([
                'success' => false,
                'message' => 'No active branch.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'branch_id' => $branch->id,
            'branch_name' => $branch->name,
            'receipt' => $branch->receiptBrandingPayload(),
        ]);
    }

    /**
     * Page for any branch user (or admin on active branch) to edit receipt header.
     */
    public function editReceiptSettings(): View|RedirectResponse
    {
        $branch = CurrentBranch::get();
        if (! $branch) {
            return redirect()->route('dashboard')->with('error', 'No active branch.');
        }

        return view('branches.receipt-settings', [
            'branch' => $branch,
        ]);
    }

    /**
     * Save receipt branding for the current active branch
     * (print popup JSON, or form submit from Receipt Settings page).
     */
    public function updateReceiptSettings(Request $request): JsonResponse|RedirectResponse
    {
        $branch = CurrentBranch::get();
        if (! $branch) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active branch.',
                ], 404);
            }

            return redirect()->route('dashboard')->with('error', 'No active branch.');
        }

        $validated = $request->validate([
            'receipt_title' => ['required', 'string', 'max:255'],
            'receipt_subtitle' => ['nullable', 'string', 'max:255'],
            'receipt_phone' => ['nullable', 'string', 'max:50'],
            'receipt_mobile_1' => ['nullable', 'string', 'max:50'],
            'receipt_mobile_2' => ['nullable', 'string', 'max:50'],
            'receipt_email' => ['nullable', 'email', 'max:255'],
            'receipt_address' => ['nullable', 'string', 'max:500'],
        ]);

        $branch->update([
            'receipt_title' => $validated['receipt_title'],
            'receipt_subtitle' => $validated['receipt_subtitle'] ?? null,
            'receipt_phone' => $validated['receipt_phone'] ?? null,
            'receipt_mobile_1' => $validated['receipt_mobile_1'] ?? null,
            'receipt_mobile_2' => $validated['receipt_mobile_2'] ?? null,
            'receipt_email' => $validated['receipt_email'] ?? null,
            'receipt_address' => $validated['receipt_address'] ?? null,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Receipt settings saved for this branch.',
                'branch_id' => $branch->id,
                'receipt' => $branch->fresh()->receiptBrandingPayload(),
            ]);
        }

        return redirect()
            ->route('branches.receipt-settings.edit')
            ->with('success', 'Receipt settings saved for '.$branch->name.'.');
    }

    public function addUser(Request $request, Branch $branch): RedirectResponse
    {
        $mode = $request->input('mode', 'existing');

        if ($mode === 'new') {
            $validated = $request->validate([
                'user_name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);

            User::create([
                'name' => $validated['user_name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'role' => User::ROLE_BRANCH_USER,
                'branch_id' => $branch->id,
            ]);

            return redirect()
                ->route('branches.edit', $branch)
                ->with('success', 'New user added to this branch.');
        }

        $validated = $request->validate([
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', User::ROLE_BRANCH_USER)),
            ],
        ]);

        $user = User::where('id', $validated['user_id'])
            ->where('role', User::ROLE_BRANCH_USER)
            ->firstOrFail();

        $user->update(['branch_id' => $branch->id]);

        return redirect()
            ->route('branches.edit', $branch)
            ->with('success', "{$user->name} assigned to {$branch->name}.");
    }

    public function removeUser(Branch $branch, User $user): RedirectResponse
    {
        if ($user->role !== User::ROLE_BRANCH_USER || (int) $user->branch_id !== (int) $branch->id) {
            return redirect()
                ->route('branches.edit', $branch)
                ->with('error', 'That user is not assigned to this branch.');
        }

        $user->update(['branch_id' => null]);

        return redirect()
            ->route('branches.edit', $branch)
            ->with('success', "{$user->name} removed from {$branch->name}.");
    }

    public function switch(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
        ]);

        $branch = Branch::where('id', $validated['branch_id'])
            ->where('is_active', true)
            ->firstOrFail();

        CurrentBranch::setActive($branch->id);

        // Always land on dashboard so lists/POS/offline cache aren't left on the previous
        // branch's page. Other open tabs are notified via branch_switched flash + JS.
        return redirect()
            ->route('dashboard')
            ->with('success', "Switched to branch: {$branch->name}")
            ->with('branch_switched', [
                'id' => (int) $branch->id,
                'name' => $branch->name,
            ]);
    }
}
