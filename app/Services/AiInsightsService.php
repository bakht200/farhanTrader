<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AiInsightsService
{
    public function getTopSummaryStats(): array
    {
        $branchId = \App\Support\CurrentBranch::id() ?? \App\Support\CurrentBranch::DEFAULT_BRANCH_ID;

        $totalProfit = (float) SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->leftJoin('products', 'products.id', '=', 'sale_items.product_id')
            ->where('sales.branch_id', $branchId)
            ->selectRaw('SUM((sale_items.unit_price - COALESCE(products.purchase_price, 0)) * sale_items.quantity - COALESCE(sale_items.discount, 0)) as total_profit')
            ->value('total_profit');

        $totalLost = (float) SaleItem::query()
            ->whereHas('sale')
            ->sum('discount');
        $totalStock = app(BranchStockService::class)->sumForBranch($branchId);
        $totalStockValue = (float) DB::table('branch_product_stocks')
            ->join('products', 'products.id', '=', 'branch_product_stocks.product_id')
            ->where('branch_product_stocks.branch_id', $branchId)
            ->selectRaw('SUM(COALESCE(branch_product_stocks.stock_quantity, 0) * COALESCE(products.purchase_price, 0)) as total_stock_value')
            ->value('total_stock_value');

        // Same as Supplier page "Total": sum of credit (purchase/bill) transactions for this branch
        $supplierTotal = (float) DB::table('supplier_transactions')
            ->where('branch_id', $branchId)
            ->where('type', 'credit')
            ->sum('amount');

        $supplierPaid = (float) DB::table('supplier_transactions')
            ->where('branch_id', $branchId)
            ->where('type', 'debit')
            ->sum('amount');

        $supplierRemaining = $supplierTotal - $supplierPaid;

        return [
            'total_profit' => max(0, round($totalProfit, 2)),
            'total_lost' => max(0, round($totalLost, 2)),
            'total_stock' => round($totalStock, 2),
            'total_stock_value' => max(0, round($totalStockValue, 2)),
            'supplier_total' => max(0, round($supplierTotal, 2)),
            'supplier_paid' => max(0, round($supplierPaid, 2)),
            'supplier_remaining' => round($supplierRemaining, 2),
        ];
    }

    public function getBusinessHealthScore(): array
    {
        $now = now();
        $last30Start = $now->copy()->subDays(30);
        $prev30Start = $now->copy()->subDays(60);

        $last30Sales = (float) Sale::whereBetween('sale_date', [$last30Start, $now])->sum('total_amount');
        $prev30Sales = (float) Sale::whereBetween('sale_date', [$prev30Start, $last30Start])->sum('total_amount');

        $salesMomentum = 50.0;
        if ($prev30Sales > 0) {
            $change = (($last30Sales - $prev30Sales) / $prev30Sales) * 100;
            $salesMomentum = max(0, min(100, 50 + $change));
        } elseif ($last30Sales > 0) {
            $salesMomentum = 75.0;
        }

        $products = Product::query()
            ->with('currentBranchStock')
            ->select('id', 'stock_quantity', 'low_stock_threshold', 'quantity_alert')
            ->get();
        $totalProducts = max(1, $products->count());
        $lowStockCount = $products->filter(function ($p) {
            $threshold = (float) ($p->low_stock_threshold ?? $p->quantity_alert ?? 0);
            return $threshold > 0 && (float) $p->stock_quantity <= $threshold;
        })->count();
        $inventoryHealth = max(0, min(100, (1 - ($lowStockCount / $totalProducts)) * 100));

        $cutoff = $now->copy()->subDays(180);
        $customerStats = Sale::query()
            ->where('sale_date', '>=', $cutoff)
            ->whereNotNull('customer_id')
            ->groupBy('customer_id')
            ->selectRaw('customer_id, COUNT(*) as order_count')
            ->get();
        $repeatCustomers = $customerStats->where('order_count', '>', 1)->count();
        $customerRetention = $customerStats->count() > 0
            ? ($repeatCustomers / $customerStats->count()) * 100
            : 50;

        $categoryRevenue = SaleItem::query()
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.branch_id', \App\Support\CurrentBranch::id() ?? \App\Support\CurrentBranch::DEFAULT_BRANCH_ID)
            ->where('sales.sale_date', '>=', $now->copy()->subDays(90))
            ->groupBy('products.category_id')
            ->selectRaw('products.category_id, SUM(sale_items.total) as revenue')
            ->pluck('revenue');
        $revenueDiversity = $this->calculateRevenueDiversityScore($categoryRevenue);

        $overall = (int) round(collect([
            $salesMomentum,
            $inventoryHealth,
            $customerRetention,
            $revenueDiversity,
        ])->avg());

        return [
            'overall' => $overall,
            'sales_momentum' => (int) round($salesMomentum),
            'inventory_health' => (int) round($inventoryHealth),
            'customer_retention' => (int) round($customerRetention),
            'revenue_diversity' => (int) round($revenueDiversity),
        ];
    }

    public function getSmartInsights(): array
    {
        $health = $this->getBusinessHealthScore();
        $insights = [];

        $insights[] = [
            'type' => $health['sales_momentum'] >= 60 ? 'positive' : 'warning',
            'message' => $health['sales_momentum'] >= 60
                ? 'Sales momentum is healthy compared to the previous month.'
                : 'Sales momentum is soft; consider promotions on fast-moving products.',
        ];

        $insights[] = [
            'type' => $health['inventory_health'] >= 70 ? 'positive' : 'negative',
            'message' => $health['inventory_health'] >= 70
                ? 'Inventory levels are mostly balanced with low stockout risk.'
                : 'Multiple products are near reorder levels; act to avoid stockouts.',
        ];

        $topProduct = SaleItem::query()
            ->whereHas('sale')
            ->selectRaw('product_id, SUM(total) as revenue')
            ->whereNotNull('product_id')
            ->groupBy('product_id')
            ->orderByDesc('revenue')
            ->with('product:id,name')
            ->first();

        if ($topProduct?->product) {
            $insights[] = [
                'type' => 'positive',
                'message' => "{$topProduct->product->name} is your top revenue product recently.",
            ];
        }

        $insights[] = [
            'type' => $health['customer_retention'] >= 50 ? 'positive' : 'warning',
            'message' => $health['customer_retention'] >= 50
                ? 'Repeat purchase behavior is good and supports steady growth.'
                : 'Repeat customer ratio is low; try loyalty offers to improve retention.',
        ];

        return $insights;
    }

    public function getReorderAlerts(): Collection
    {
        $now = now();
        $windowStart = $now->copy()->subDays(30);

        $velocity = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.branch_id', \App\Support\CurrentBranch::id() ?? \App\Support\CurrentBranch::DEFAULT_BRANCH_ID)
            ->where('sales.sale_date', '>=', $windowStart)
            ->groupBy('sale_items.product_id')
            ->selectRaw('sale_items.product_id, SUM(sale_items.quantity) / 30 as daily_velocity')
            ->pluck('daily_velocity', 'product_id');

        $products = Product::query()->with(['category:id,name', 'currentBranchStock'])->get();

        $alerts = $products->map(function (Product $product) use ($velocity) {
            $currentStock = (float) ($product->stock_quantity ?? 0);
            $dailyVelocity = (float) ($velocity[$product->id] ?? 0);
            $threshold = (float) ($product->low_stock_threshold ?? $product->quantity_alert ?? 0);

            if ($dailyVelocity <= 0 && $threshold <= 0) {
                return null;
            }

            $daysRemaining = $dailyVelocity > 0 ? (int) floor($currentStock / $dailyVelocity) : 999;
            $safetyStock = max($threshold, ceil($dailyVelocity * 7));
            $reorderQty = max(0, (int) ceil(($dailyVelocity * 14) + $safetyStock - $currentStock));

            $isAlert = $currentStock <= max($threshold, $dailyVelocity * 10) || $daysRemaining <= 14;
            if (!$isAlert) {
                return null;
            }

            $urgency = $daysRemaining <= 3 ? 'critical' : ($daysRemaining <= 7 ? 'high' : 'medium');

            return [
                'product' => $product,
                'current_stock' => round($currentStock, 2),
                'daily_velocity' => round($dailyVelocity, 2),
                'days_remaining' => max(0, $daysRemaining),
                'safety_stock' => (int) $safetyStock,
                'reorder_qty' => $reorderQty,
                'urgency' => $urgency,
            ];
        })->filter()->values();

        return $alerts->sortBy([
            ['days_remaining', 'asc'],
            ['daily_velocity', 'desc'],
        ])->values();
    }

    public function getSalesForecast(): array
    {
        $monthly = Sale::query()
            ->where('sale_date', '>=', now()->subMonths(12)->startOfMonth())
            ->selectRaw("DATE_FORMAT(sale_date, '%Y-%m-01') as month_start, SUM(total_amount) as amount")
            ->groupBy('month_start')
            ->orderBy('month_start')
            ->get()
            ->map(function ($row) {
                return [
                    'month_start' => $row->month_start,
                    'month_short' => Carbon::parse($row->month_start)->format('M'),
                    'amount' => (float) $row->amount,
                ];
            })
            ->values();

        if ($monthly->isEmpty()) {
            $monthly = collect([[
                'month_start' => now()->startOfMonth()->toDateString(),
                'month_short' => now()->format('M'),
                'amount' => 0.0,
            ]]);
        }

        $slope = $this->calculateLinearSlope($monthly->pluck('amount')->all());
        $trendDirection = $slope >= 0 ? 'upward' : 'downward';

        $forecast = collect();
        $lastAmount = (float) $monthly->last()['amount'];
        $baseMonth = Carbon::parse($monthly->last()['month_start']);
        for ($i = 1; $i <= 3; $i++) {
            $predicted = max(0, $lastAmount + ($slope * $i));
            $month = $baseMonth->copy()->addMonths($i);
            $forecast->push([
                'month_short' => $month->format('M'),
                'amount' => round($predicted, 2),
            ]);
        }

        $last4Weeks = (float) Sale::query()
            ->where('sale_date', '>=', now()->subDays(28))
            ->sum('total_amount');
        $weeklyAverage = $last4Weeks / 4;
        $nextWeekForecast = max(0, $weeklyAverage + ($slope / 4));

        return [
            'trend_direction' => $trendDirection,
            'trend_value' => round(abs($slope), 2),
            'weekly_average' => round($weeklyAverage, 2),
            'next_week_forecast' => round($nextWeekForecast, 2),
            'historical' => $monthly->map(fn ($m) => [
                'month_short' => $m['month_short'],
                'amount' => $m['amount'],
            ])->all(),
            'forecast' => $forecast->all(),
        ];
    }

    public function getSalesTrends(): array
    {
        $dayPatternRaw = Sale::query()
            ->where('sale_date', '>=', now()->subDays(90))
            ->selectRaw('DAYOFWEEK(sale_date) as d, SUM(total_amount) as total')
            ->groupBy('d')
            ->pluck('total', 'd');

        $days = [1 => 'Sun', 2 => 'Mon', 3 => 'Tue', 4 => 'Wed', 5 => 'Thu', 6 => 'Fri', 7 => 'Sat'];
        $dayPattern = collect($days)->map(function ($label, $idx) use ($dayPatternRaw) {
            return [
                'day' => $label,
                'total' => round((float) ($dayPatternRaw[$idx] ?? 0), 2),
            ];
        })->values()->all();

        $monthly = Sale::query()
            ->where('sale_date', '>=', now()->subMonths(7)->startOfMonth())
            ->selectRaw("DATE_FORMAT(sale_date, '%Y-%m-01') as month_start, SUM(total_amount) as total")
            ->groupBy('month_start')
            ->orderBy('month_start')
            ->get();

        $monthlyGrowth = [];
        $prev = null;
        foreach ($monthly as $row) {
            $current = (float) $row->total;
            $growth = $prev && $prev > 0 ? (($current - $prev) / $prev) * 100 : 0;
            $monthlyGrowth[] = [
                'month' => Carbon::parse($row->month_start)->format('M'),
                'growth' => round($growth, 1),
            ];
            $prev = $current;
        }

        $categoryPerformance = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.branch_id', \App\Support\CurrentBranch::id() ?? \App\Support\CurrentBranch::DEFAULT_BRANCH_ID)
            ->leftJoin('products', 'products.id', '=', 'sale_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->where('sales.sale_date', '>=', now()->subDays(30))
            ->groupBy('categories.id', 'categories.name')
            ->selectRaw('COALESCE(categories.name, "Uncategorized") as category, SUM(sale_items.total) as revenue, SUM(sale_items.quantity) as units_sold')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn ($r) => [
                'category' => $r->category,
                'revenue' => round((float) $r->revenue, 2),
                'units_sold' => round((float) $r->units_sold, 2),
            ])
            ->all();

        return [
            'day_pattern' => $dayPattern,
            'monthly_growth' => $monthlyGrowth,
            'category_performance' => $categoryPerformance,
        ];
    }

    public function getAbcAnalysis(): array
    {
        $rows = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.branch_id', \App\Support\CurrentBranch::id() ?? \App\Support\CurrentBranch::DEFAULT_BRANCH_ID)
            ->where('sales.sale_date', '>=', now()->subDays(90))
            ->whereNotNull('sale_items.product_id')
            ->groupBy('sale_items.product_id')
            ->selectRaw('sale_items.product_id, SUM(sale_items.total) as revenue, SUM(sale_items.quantity) as quantity')
            ->orderByDesc('revenue')
            ->get();

        $products = Product::query()->with('category:id,name')->whereIn('id', $rows->pluck('product_id'))->get()->keyBy('id');
        $totalRevenue = max(0.01, (float) $rows->sum('revenue'));

        $cumulative = 0.0;
        $classified = collect();
        foreach ($rows as $row) {
            $revenue = (float) $row->revenue;
            $pct = ($revenue / $totalRevenue) * 100;
            $cumulative += $pct;

            $class = $cumulative <= 80 ? 'A' : ($cumulative <= 95 ? 'B' : 'C');
            $classified->push([
                'class' => $class,
                'product' => $products->get($row->product_id),
                'revenue' => round($revenue, 2),
                'quantity' => round((float) $row->quantity, 2),
                'revenue_percentage' => round($pct, 2),
                'cumulative_percentage' => round($cumulative, 2),
            ]);
        }

        $groups = [
            'A' => $classified->where('class', 'A')->values(),
            'B' => $classified->where('class', 'B')->values(),
            'C' => $classified->where('class', 'C')->values(),
        ];

        $summary = [];
        foreach (['A', 'B', 'C'] as $class) {
            $classRevenue = $groups[$class]->sum('revenue');
            $summary[$class] = [
                'count' => $groups[$class]->count(),
                'revenue' => round($classRevenue, 2),
                'percentage' => round(($classRevenue / $totalRevenue) * 100, 2),
            ];
        }

        return [
            'summary' => $summary,
            'A' => $groups['A'],
            'B' => $groups['B'],
            'C' => $groups['C'],
        ];
    }

    public function getCustomerRfmSegmentation(): array
    {
        $rows = Sale::query()
            ->whereNotNull('customer_id')
            ->groupBy('customer_id')
            ->selectRaw('customer_id, MAX(sale_date) as last_purchase, COUNT(*) as frequency, SUM(total_amount) as monetary')
            ->get();

        if ($rows->isEmpty()) {
            return ['segments' => [], 'customers' => collect()];
        }

        $now = now();
        $recencies = $rows->map(fn ($r) => $now->diffInDays(Carbon::parse($r->last_purchase)));
        $frequencies = $rows->pluck('frequency');
        $monetaries = $rows->pluck('monetary');

        $customers = Customer::query()->whereIn('id', $rows->pluck('customer_id'))->get()->keyBy('id');

        $rfmRows = $rows->map(function ($row, $idx) use ($customers, $recencies, $frequencies, $monetaries) {
            $recency = (int) $recencies[$idx];
            $frequency = (float) $row->frequency;
            $monetary = (float) $row->monetary;

            $rScore = $this->quantileScore($recency, $recencies, false);
            $fScore = $this->quantileScore($frequency, $frequencies, true);
            $mScore = $this->quantileScore($monetary, $monetaries, true);
            $segment = $this->mapSegment($rScore, $fScore, $mScore);

            return [
                'customer' => $customers->get($row->customer_id),
                'recency' => $recency,
                'frequency' => round($frequency, 2),
                'monetary' => round($monetary, 2),
                'r_score' => $rScore,
                'f_score' => $fScore,
                'm_score' => $mScore,
                'segment' => $segment,
            ];
        })->filter(fn ($r) => $r['customer'] !== null)
            ->sortByDesc('monetary')
            ->values();

        $segments = $rfmRows->groupBy('segment')->map(function ($group) {
            return [
                'count' => $group->count(),
                'avg_monetary' => round($group->avg('monetary'), 2),
                'avg_frequency' => round($group->avg('frequency'), 2),
            ];
        })->toArray();

        return [
            'segments' => $segments,
            'customers' => $rfmRows,
        ];
    }

    public function getProductRecommendations(): Collection
    {
        $saleItems = SaleItem::query()
            ->whereHas('sale')
            ->whereNotNull('product_id')
            ->select('sale_id', 'product_id')
            ->orderBy('sale_id')
            ->get()
            ->groupBy('sale_id')
            ->map(fn ($items) => $items->pluck('product_id')->unique()->values())
            ->filter(fn ($ids) => $ids->count() >= 2);

        if ($saleItems->isEmpty()) {
            return collect();
        }

        $pairCounts = [];
        $productOrderCounts = [];

        foreach ($saleItems as $productIds) {
            foreach ($productIds as $pid) {
                $productOrderCounts[$pid] = ($productOrderCounts[$pid] ?? 0) + 1;
            }

            $count = $productIds->count();
            for ($i = 0; $i < $count; $i++) {
                for ($j = $i + 1; $j < $count; $j++) {
                    $a = (int) $productIds[$i];
                    $b = (int) $productIds[$j];
                    [$p1, $p2] = $a < $b ? [$a, $b] : [$b, $a];
                    $key = $p1 . '-' . $p2;
                    $pairCounts[$key] = ($pairCounts[$key] ?? 0) + 1;
                }
            }
        }

        $totalOrders = max(1, $saleItems->count());
        $productMap = Product::query()->whereIn('id', collect(array_keys($productOrderCounts))->map(fn ($k) => (int) $k))->get()->keyBy('id');

        return collect($pairCounts)
            ->sortDesc()
            ->take(20)
            ->map(function ($count, $key) use ($productMap, $productOrderCounts, $totalOrders) {
                [$p1, $p2] = array_map('intval', explode('-', $key));
                $prod1 = $productMap->get($p1);
                $prod2 = $productMap->get($p2);
                if (!$prod1 || !$prod2) {
                    return null;
                }

                $support = ($count / $totalOrders) * 100;
                $confidence = ($count / max(1, ($productOrderCounts[$p1] ?? 1))) * 100;
                $strength = $confidence >= 50 ? 'very_strong' : ($confidence >= 30 ? 'strong' : ($confidence >= 15 ? 'moderate' : 'weak'));

                return [
                    'product_1' => $prod1,
                    'product_2' => $prod2,
                    'times_bought_together' => (int) $count,
                    'support' => round($support, 2),
                    'confidence' => round($confidence, 2),
                    'strength' => $strength,
                ];
            })
            ->filter()
            ->values();
    }

    public function getAnomalyDetection(): array
    {
        $start = now()->subDays(90);
        $dailyTotals = Sale::query()
            ->where('sale_date', '>=', $start)
            ->groupBy(DB::raw('DATE(sale_date)'))
            ->selectRaw('DATE(sale_date) as day, SUM(total_amount) as total')
            ->orderBy('day')
            ->get();

        $totals = $dailyTotals->pluck('total')->map(fn ($v) => (float) $v);
        $mean = $totals->avg() ?? 0;
        $std = $this->stdDev($totals->all(), (float) $mean);

        $anomalies = [];
        foreach ($dailyTotals as $row) {
            $total = (float) $row->total;
            if ($std <= 0) {
                continue;
            }
            $z = ($total - $mean) / $std;
            if (abs($z) >= 2) {
                $anomalies[] = [
                    'type' => 'sales_amount',
                    'severity' => abs($z) >= 3 ? 'high' : 'medium',
                    'message' => 'Unusual daily sales amount detected.',
                    'date' => Carbon::parse($row->day)->format('Y-m-d'),
                    'z_score' => round($z, 2),
                    'expected' => round($mean, 2),
                ];
            }
        }

        $highDiscounts = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.branch_id', \App\Support\CurrentBranch::id() ?? \App\Support\CurrentBranch::DEFAULT_BRANCH_ID)
            ->where('sales.sale_date', '>=', $start)
            ->whereRaw('(sale_items.discount / NULLIF((sale_items.unit_price * sale_items.quantity), 0)) > 0.35')
            ->selectRaw('sales.sale_date as day, sale_items.discount as discount')
            ->limit(10)
            ->get();

        foreach ($highDiscounts as $row) {
            $anomalies[] = [
                'type' => 'high_discount',
                'severity' => 'medium',
                'message' => 'High discount transaction flagged for review.',
                'date' => Carbon::parse($row->day)->format('Y-m-d'),
            ];
        }

        $priceDrop = SaleItem::query()
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.branch_id', \App\Support\CurrentBranch::id() ?? \App\Support\CurrentBranch::DEFAULT_BRANCH_ID)
            ->where('sales.sale_date', '>=', $start)
            ->whereRaw('sale_items.unit_price < (products.selling_price * 0.7)')
            ->selectRaw('sales.sale_date as day')
            ->limit(10)
            ->get();

        foreach ($priceDrop as $row) {
            $anomalies[] = [
                'type' => 'price_drop',
                'severity' => 'medium',
                'message' => 'Significant price drop sale detected.',
                'date' => Carbon::parse($row->day)->format('Y-m-d'),
            ];
        }

        usort($anomalies, function ($a, $b) {
            $priority = ['high' => 2, 'medium' => 1];
            return ($priority[$b['severity']] ?? 0) <=> ($priority[$a['severity']] ?? 0);
        });

        return array_slice($anomalies, 0, 25);
    }

    private function calculateRevenueDiversityScore(Collection $revenues): float
    {
        if ($revenues->isEmpty()) {
            return 50.0;
        }

        $total = (float) $revenues->sum();
        if ($total <= 0) {
            return 50.0;
        }

        $shares = $revenues->map(fn ($r) => ((float) $r) / $total);
        $hhi = $shares->reduce(fn ($carry, $s) => $carry + ($s * $s), 0.0);
        $n = max(1, $shares->count());
        $normalized = $n > 1 ? (1 - $hhi) / (1 - (1 / $n)) : 0.0;

        return max(0, min(100, $normalized * 100));
    }

    private function calculateLinearSlope(array $values): float
    {
        $n = count($values);
        if ($n < 2) {
            return 0.0;
        }

        $sumX = $sumY = $sumXY = $sumXX = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $x = $i + 1;
            $y = (float) $values[$i];
            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumXX += $x * $x;
        }

        $denominator = ($n * $sumXX) - ($sumX * $sumX);
        if ($denominator == 0.0) {
            return 0.0;
        }

        return (($n * $sumXY) - ($sumX * $sumY)) / $denominator;
    }

    private function quantileScore(float|int $value, Collection $values, bool $higherIsBetter): int
    {
        $sorted = $values->map(fn ($v) => (float) $v)->sort()->values();
        $n = $sorted->count();
        if ($n === 0) {
            return 3;
        }

        $position = $sorted->search(function ($v) use ($value) {
            return $value <= $v;
        });
        if ($position === false) {
            $position = $n - 1;
        }

        $percentile = ($position + 1) / $n;
        $score = (int) ceil($percentile * 5);
        $score = max(1, min(5, $score));

        return $higherIsBetter ? $score : (6 - $score);
    }

    private function mapSegment(int $r, int $f, int $m): string
    {
        if ($r >= 4 && $f >= 4 && $m >= 4) {
            return 'Champions';
        }
        if ($r >= 3 && $f >= 4) {
            return 'Loyal Customers';
        }
        if ($r >= 4 && $f >= 3) {
            return 'Potential Loyalists';
        }
        if ($r === 5 && $f <= 2) {
            return 'New Customers';
        }
        if ($r >= 3 && $f === 2) {
            return 'Promising';
        }
        if ($r === 3 && $f === 3) {
            return 'Need Attention';
        }
        if ($r <= 2 && $f >= 3) {
            return 'At Risk';
        }
        if ($r <= 2 && $f >= 4 && $m >= 4) {
            return "Can't Lose Them";
        }
        if ($r === 1 && $f <= 2) {
            return 'Lost';
        }

        return 'Others';
    }

    private function stdDev(array $values, float $mean): float
    {
        $count = count($values);
        if ($count <= 1) {
            return 0.0;
        }

        $sum = 0.0;
        foreach ($values as $v) {
            $sum += ((float) $v - $mean) ** 2;
        }

        return sqrt($sum / ($count - 1));
    }
}
