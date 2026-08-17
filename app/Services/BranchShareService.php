<?php

namespace App\Services;

use App\Models\BranchShare;
use App\Models\BranchShareInvestment;
use App\Models\User;
use App\Support\CurrentBranch;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BranchShareService
{
    public function __construct(
        protected ProfitReportService $profitReportService
    ) {}

    public function currentBranchId(): int
    {
        return CurrentBranch::requireId();
    }

    /**
     * Ensure an open share exists for the given year/month (defaults to current month).
     */
    public function getOrCreateOpenShare(?int $year = null, ?int $month = null, ?int $branchId = null): BranchShare
    {
        $branchId = $branchId ?? $this->currentBranchId();
        $year = $year ?? (int) now()->year;
        $month = $month ?? (int) now()->month;

        $share = BranchShare::query()
            ->where('branch_id', $branchId)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        if ($share) {
            return $share;
        }

        $start = Carbon::create($year, $month, 1)->startOfMonth();

        return BranchShare::create([
            'branch_id' => $branchId,
            'year' => $year,
            'month' => $month,
            'period_start' => $start->toDateString(),
            'period_end' => $start->copy()->endOfMonth()->toDateString(),
            'status' => BranchShare::STATUS_OPEN,
        ]);
    }

    /**
     * Live or locked financial summary for a share period.
     *
     * @return array{
     *     revenue: float,
     *     gross_profit: float,
     *     total_expenses: float,
     *     net_profit: float,
     *     bill_count: int,
     *     total_investment: float,
     *     allocations: Collection
     * }
     */
    public function summarizeShare(BranchShare $share): array
    {
        if ($share->isClosed()) {
            $totalInvestment = (float) $share->total_investment;
            $summary = [
                'revenue' => (float) $share->revenue,
                'gross_profit' => (float) $share->gross_profit,
                'total_expenses' => (float) $share->total_expenses,
                'net_profit' => (float) $share->net_profit,
                'bill_count' => 0,
                'total_investment' => $totalInvestment,
            ];

            $allocations = $share->investments->map(function (BranchShareInvestment $inv) {
                return [
                    'investment' => $inv,
                    'user' => $inv->user,
                    'amount' => (float) $inv->amount,
                    'share_percent' => (float) $inv->share_percent,
                    'profit_share' => (float) $inv->profit_share,
                ];
            });

            return array_merge($summary, ['allocations' => $allocations]);
        }

        $start = Carbon::parse($share->period_start)->startOfDay();
        $end = Carbon::parse($share->period_end)->endOfDay();
        $pnl = $this->profitReportService->summarize($start, $end, (int) $share->branch_id);

        $investments = $share->relationLoaded('investments')
            ? $share->investments
            : $share->investments()->with('user')->get();

        $totalInvestment = (float) $investments->sum('amount');
        $netProfit = $pnl['net_profit'];

        $allocations = $investments->map(function (BranchShareInvestment $inv) use ($totalInvestment, $netProfit) {
            $amount = (float) $inv->amount;
            $percent = $totalInvestment > 0 ? ($amount / $totalInvestment) * 100 : 0.0;
            $profitShare = $totalInvestment > 0 ? round(($amount / $totalInvestment) * $netProfit, 2) : 0.0;

            return [
                'investment' => $inv,
                'user' => $inv->user,
                'amount' => $amount,
                'share_percent' => round($percent, 4),
                'profit_share' => $profitShare,
            ];
        })->sortByDesc('amount')->values();

        return [
            'revenue' => $pnl['revenue'],
            'gross_profit' => $pnl['gross_profit'],
            'total_expenses' => $pnl['total_expenses'],
            'net_profit' => $netProfit,
            'bill_count' => $pnl['bill_count'],
            'total_investment' => $totalInvestment,
            'allocations' => $allocations,
        ];
    }

    /**
     * Eligible investors: admins + users of this branch.
     *
     * @return Collection<int, User>
     */
    public function eligibleInvestors(?int $branchId = null): Collection
    {
        $branchId = $branchId ?? $this->currentBranchId();

        return User::query()
            ->where(function ($q) use ($branchId) {
                $q->where('role', User::ROLE_ADMIN)
                    ->orWhere('branch_id', $branchId);
            })
            ->orderBy('name')
            ->get();
    }

    public function upsertInvestment(
        BranchShare $share,
        int $userId,
        float $amount,
        ?string $notes = null
    ): BranchShareInvestment {
        if ($share->isClosed()) {
            throw new InvalidArgumentException('Cannot edit investments on a closed share.');
        }

        $eligibleIds = $this->eligibleInvestors((int) $share->branch_id)->pluck('id')->all();
        if (! in_array($userId, $eligibleIds, true)) {
            throw new InvalidArgumentException('Selected user cannot invest in this branch.');
        }

        if ($amount < 0) {
            throw new InvalidArgumentException('Investment amount cannot be negative.');
        }

        $actorId = Auth::id();

        $investment = BranchShareInvestment::query()
            ->where('branch_share_id', $share->id)
            ->where('user_id', $userId)
            ->first();

        if ($investment) {
            $investment->update([
                'amount' => $amount,
                'notes' => $notes,
                'updated_by' => $actorId,
            ]);

            return $investment->fresh(['user']);
        }

        return BranchShareInvestment::create([
            'branch_share_id' => $share->id,
            'user_id' => $userId,
            'amount' => $amount,
            'notes' => $notes,
            'created_by' => $actorId,
            'updated_by' => $actorId,
        ]);
    }

    public function removeInvestment(BranchShare $share, BranchShareInvestment $investment): void
    {
        if ($share->isClosed()) {
            throw new InvalidArgumentException('Cannot remove investments from a closed share.');
        }

        if ((int) $investment->branch_share_id !== (int) $share->id) {
            throw new InvalidArgumentException('Investment does not belong to this share.');
        }

        $investment->delete();
    }

    /**
     * Lock investments + P&L, then open next month's share if missing.
     */
    public function closeMonth(BranchShare $share): BranchShare
    {
        if ($share->isClosed()) {
            throw new InvalidArgumentException('This share month is already closed.');
        }

        return DB::transaction(function () use ($share) {
            $share->load(['investments.user']);
            $summary = $this->summarizeShare($share);

            foreach ($summary['allocations'] as $row) {
                /** @var BranchShareInvestment $investment */
                $investment = $row['investment'];
                $investment->update([
                    'share_percent' => $row['share_percent'],
                    'profit_share' => $row['profit_share'],
                ]);
            }

            $share->update([
                'status' => BranchShare::STATUS_CLOSED,
                'total_investment' => $summary['total_investment'],
                'revenue' => $summary['revenue'],
                'gross_profit' => $summary['gross_profit'],
                'total_expenses' => $summary['total_expenses'],
                'net_profit' => $summary['net_profit'],
                'closed_at' => now(),
                'closed_by' => Auth::id(),
            ]);

            // Ensure next calendar month has an open share ready
            $next = Carbon::create($share->year, $share->month, 1)->addMonth();
            $this->getOrCreateOpenShare((int) $next->year, (int) $next->month, (int) $share->branch_id);

            return $share->fresh(['investments.user', 'closedByUser']);
        });
    }
}
