<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Supplier;
use App\Services\BranchStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        // Weekly Earnings
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();
        $lastWeekStart = Carbon::now()->subWeek()->startOfWeek();
        $lastWeekEnd = Carbon::now()->subWeek()->endOfWeek();
        
        $weeklyEarning = Sale::whereBetween('sale_date', [$startOfWeek, $endOfWeek])
            ->sum('total_amount');
        
        $lastWeekEarning = Sale::whereBetween('sale_date', [$lastWeekStart, $lastWeekEnd])
            ->sum('total_amount');
        
        $weeklyEarningIncrease = $lastWeekEarning > 0 
            ? round((($weeklyEarning - $lastWeekEarning) / $lastWeekEarning) * 100, 1)
            : 0;
        
        // Total Sales Count
        $totalSales = Sale::count();
        
        // Purchased Goods (Total Products) — current branch stock
        $purchasedGoods = app(BranchStockService::class)->sumForBranch();

        // Dashboard KPIs (more actionable than raw counts)
        $totalCustomers = Customer::where('is_active', true)->count();
        $totalReceivable = (float) Sale::where('sale_number', 'not like', 'ADJ-%')
            ->where('status', 'completed')
            ->selectRaw("SUM(CASE WHEN (total_amount - paid_amount) > 0 THEN (total_amount - paid_amount) ELSE 0 END) as receivable")
            ->value('receivable');
        
        // Best Sellers (Top 5 products by sales) — current branch only
        $bestSellers = SaleItem::select('sale_items.product_id', DB::raw('SUM(sale_items.quantity) as total_sold'))
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->with('product')
            ->whereHas('sale')
            ->groupBy('sale_items.product_id')
            ->orderBy('total_sold', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'product' => $item->product,
                    'total_sold' => $item->total_sold,
                    'total_revenue' => SaleItem::where('product_id', $item->product_id)->whereHas('sale')->sum('total')
                ];
            });
        
        // Recent Transactions (Last 5 sales)
        $recentTransactions = Sale::with(['customer', 'items.product'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        // Sales Analytics (Monthly data for current year)
        $salesAnalytics = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthStart = Carbon::create(date('Y'), $i, 1)->startOfMonth();
            $monthEnd = Carbon::create(date('Y'), $i, 1)->endOfMonth();
            
            $salesAnalytics[] = [
                'month' => Carbon::create(null, $i, 1)->format('M'),
                'amount' => Sale::whereBetween('sale_date', [$monthStart, $monthEnd])
                    ->sum('total_amount')
            ];
        }
        
        // Overall Information
        $totalSuppliers = Supplier::count();
        $totalCustomers = Customer::count();
        $totalOrders = Order::count();
        
        // Customer Overview (First time vs Return customers)
        // First time customers: customers with only one sale
        $firstTimeCustomers = Customer::has('sales', '=', 1)->count();
        
        // Return customers: customers with more than one sale
        $returnCustomers = Customer::has('sales', '>', 1)->count();
        
        $totalActiveCustomers = $firstTimeCustomers + $returnCustomers;
        $firstTimePercentage = $totalActiveCustomers > 0 
            ? round(($firstTimeCustomers / $totalActiveCustomers) * 100, 1)
            : 0;
        $returnPercentage = $totalActiveCustomers > 0 
            ? round(($returnCustomers / $totalActiveCustomers) * 100, 1)
            : 0;
        
        return view('dashboard', compact(
            'user',
            'weeklyEarning',
            'weeklyEarningIncrease',
            'totalSales',
            'purchasedGoods',
            'totalCustomers',
            'totalReceivable',
            'bestSellers',
            'recentTransactions',
            'salesAnalytics',
            'totalSuppliers',
            'totalCustomers',
            'totalOrders',
            'firstTimeCustomers',
            'returnCustomers',
            'firstTimePercentage',
            'returnPercentage'
        ));
    }
}

