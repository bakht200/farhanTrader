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
     * Line profit: selling amount minus cost of the qty sold in base units.
     *
     * Cost prefers the sold lot rate (same as POS Pur.Price). Falls back to the
     * product catalog purchase price when the line has no lot.
     */
    public static function grossProfitSql(): string
    {
        return '(sale_items.unit_price * sale_items.quantity - COALESCE(sale_items.discount, 0))'
            .' - COALESCE(product_lots.purchase_price, products.purchase_price, 0)'
            .' * COALESCE(sale_items.quantity_in_base_unit, sale_items.quantity)';
    }

    /**
     * @return \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder
     */
    protected function profitLinesQuery(int $branchId, Carbon $startDate, Carbon $endDate)
    {
        return SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->leftJoin('products', 'products.id', '=', 'sale_items.product_id')
            ->leftJoin('product_lots', 'product_lots.id', '=', 'sale_items.product_lot_id')
            ->where('sales.branch_id', $branchId)
            ->where('sales.status', 'completed')
            ->where('sales.sale_number', 'not like', 'ADJ-%')
            ->whereBetween('sales.sale_date', [$startDate->toDateString(), $endDate->toDateString()]);
    }

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
        $branchId = $branchId ?? CurrentBranch::id();
        $startDate = $start->copy()->startOfDay();
        $endDate = $end->copy()->endOfDay();

        $revenue = (float) Sale::query()
            ->where('branch_id', $branchId)
            ->where('status', 'completed')
            ->where('sale_number', 'not like', 'ADJ-%')
            ->whereBetween('sale_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->sum('total_amount');

        $grossProfit = (float) $this->profitLinesQuery($branchId, $startDate, $endDate)
            ->selectRaw('SUM('.self::grossProfitSql().') as gross_profit')
            ->value('gross_profit');

        $totalExpenses = (float) Expense::query()
            ->where('branch_id', $branchId)
            ->whereBetween('expense_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->sum('amount');

        $billCount = (int) Sale::query()
            ->where('branch_id', $branchId)
            ->where('status', 'completed')
            ->where('sale_number', 'not like', 'ADJ-%')
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
     * Completed sales (bills) for the period, each with line profit.
     *
     * @return Collection<int, Sale>
     */
    public function bills(Carbon $start, Carbon $end, ?int $branchId = null): Collection
    {
        $branchId = $branchId ?? CurrentBranch::id();
        $startDate = $start->copy()->startOfDay();
        $endDate = $end->copy()->endOfDay();

        $bills = Sale::query()
            ->where('branch_id', $branchId)
            ->where('status', 'completed')
            ->where('sale_number', 'not like', 'ADJ-%')
            ->whereBetween('sale_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderByDesc('sale_date')
            ->orderByDesc('created_at')
            ->get(['id', 'sale_number', 'sale_date', 'created_at', 'total_amount', 'paid_amount', 'payment_status', 'customer_id']);

        if ($bills->isEmpty()) {
            return $bills;
        }

        $profits = $this->profitLinesQuery($branchId, $startDate, $endDate)
            ->whereIn('sales.id', $bills->pluck('id'))
            ->groupBy('sales.id')
            ->select('sales.id')
            ->selectRaw('ROUND(SUM('.self::grossProfitSql().'), 2) as bill_profit')
            ->pluck('bill_profit', 'id');

        return $bills->map(function (Sale $bill) use ($profits) {
            $bill->setAttribute('profit', round((float) ($profits[$bill->id] ?? 0), 2));

            return $bill;
        });
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
