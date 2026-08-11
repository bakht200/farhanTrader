<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Support\CurrentBranch;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ProfitReportService
{
    /**
     * @return array{
     *     revenue: float,
     *     gross_profit: float,
     *     total_expenses: float,
     *     net_profit: float,
     *     bill_count: int
     * }
     */
    public function summarize(Carbon $start, Carbon $end, ?int $branchId = null): array
    {
        $branchId = $branchId ?? CurrentBranch::id() ?? CurrentBranch::DEFAULT_BRANCH_ID;
        $startDate = $start->copy()->startOfDay();
        $endDate = $end->copy()->endOfDay();

        $revenue = (float) Sale::query()
            ->where('branch_id', $branchId)
            ->where('status', 'completed')
            ->whereBetween('sale_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->sum('total_amount');

        $grossProfit = (float) SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->leftJoin('products', 'products.id', '=', 'sale_items.product_id')
            ->where('sales.branch_id', $branchId)
            ->where('sales.status', 'completed')
            ->whereBetween('sales.sale_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->selectRaw('SUM((sale_items.unit_price - COALESCE(products.purchase_price, 0)) * sale_items.quantity - COALESCE(sale_items.discount, 0)) as gross_profit')
            ->value('gross_profit');

        $totalExpenses = (float) Expense::query()
            ->where('branch_id', $branchId)
            ->whereBetween('expense_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->sum('amount');

        $billCount = (int) Sale::query()
            ->where('branch_id', $branchId)
            ->where('status', 'completed')
            ->whereBetween('sale_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->count();

        $grossProfit = round($grossProfit, 2);
        $totalExpenses = round($totalExpenses, 2);
        $revenue = round($revenue, 2);
        $netProfit = round($grossProfit - $totalExpenses, 2);

        return [
            'revenue' => $revenue,
            'gross_profit' => $grossProfit,
            'total_expenses' => $totalExpenses,
            'net_profit' => $netProfit,
            'bill_count' => $billCount,
        ];
    }

    /**
     * Completed sales (bills) for the period.
     *
     * @return Collection<int, Sale>
     */
    public function bills(Carbon $start, Carbon $end, ?int $branchId = null): Collection
    {
        $branchId = $branchId ?? CurrentBranch::id() ?? CurrentBranch::DEFAULT_BRANCH_ID;

        return Sale::query()
            ->where('branch_id', $branchId)
            ->where('status', 'completed')
            ->whereBetween('sale_date', [$start->toDateString(), $end->toDateString()])
            ->orderByDesc('sale_date')
            ->orderByDesc('created_at')
            ->get(['id', 'sale_number', 'sale_date', 'created_at', 'total_amount', 'paid_amount', 'payment_status', 'customer_id']);
    }

    /**
     * Resolve date range from report mode filters.
     *
     * @return array{start: Carbon, end: Carbon, label: string}
     */
    public function resolveRange(string $mode, array $filters): array
    {
        $mode = in_array($mode, ['daily', 'monthly', 'yearly'], true) ? $mode : 'daily';

        if ($mode === 'yearly') {
            $startYear = (int) ($filters['start_year'] ?? now()->year);
            $endYear = (int) ($filters['end_year'] ?? $startYear);
            if ($endYear < $startYear) {
                [$startYear, $endYear] = [$endYear, $startYear];
            }

            return [
                'start' => Carbon::create($startYear, 1, 1)->startOfDay(),
                'end' => Carbon::create($endYear, 12, 31)->endOfDay(),
                'label' => $startYear === $endYear
                    ? (string) $startYear
                    : "{$startYear} – {$endYear}",
            ];
        }

        if ($mode === 'monthly') {
            $startMonth = (string) ($filters['start_month'] ?? now()->format('Y-m'));
            $endMonth = (string) ($filters['end_month'] ?? $startMonth);
            $start = Carbon::createFromFormat('Y-m', $startMonth)->startOfMonth();
            $end = Carbon::createFromFormat('Y-m', $endMonth)->endOfMonth();
            if ($end->lt($start)) {
                [$start, $end] = [$end->copy()->startOfMonth(), $start->copy()->endOfMonth()];
            }

            return [
                'start' => $start,
                'end' => $end,
                'label' => $start->format('M Y') === $end->copy()->startOfMonth()->format('M Y')
                    ? $start->format('F Y')
                    : $start->format('M Y').' – '.$end->format('M Y'),
            ];
        }

        $startDate = (string) ($filters['start_date'] ?? now()->toDateString());
        $endDate = (string) ($filters['end_date'] ?? $startDate);
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();
        if ($end->lt($start)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return [
            'start' => $start,
            'end' => $end,
            'label' => $start->toDateString() === $end->toDateString()
                ? $start->format('d M Y')
                : $start->format('d M Y').' – '.$end->format('d M Y'),
        ];
    }
}
