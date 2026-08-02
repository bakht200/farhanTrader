<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SalesReportController extends Controller
{
    public function index(Request $request)
    {
        // Get filter parameters
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $productId = $request->input('product_id', 'all');
        $storeId = $request->input('store_id', 'all'); // For future multi-store support
        $perPage = $request->input('per_page', 10);

        // Calculate date range for comparison (last month)
        $lastMonthStart = Carbon::parse($startDate)->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::parse($endDate)->subMonth()->endOfMonth();

        // Current period metrics
        $currentSales = Sale::whereBetween('sale_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->sum('total_amount');

        $currentCustomers = Customer::whereHas('sales', function($query) use ($startDate, $endDate) {
            $query->whereBetween('sale_date', [$startDate, $endDate])
                  ->where('status', 'completed');
        })->count();

        $currentInvoices = Invoice::whereBetween('invoice_date', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled')
            ->count();

        // Last month metrics for comparison
        $lastMonthSales = Sale::whereBetween('sale_date', [$lastMonthStart, $lastMonthEnd])
            ->where('status', 'completed')
            ->sum('total_amount');

        $lastMonthCustomers = Customer::whereHas('sales', function($query) use ($lastMonthStart, $lastMonthEnd) {
            $query->whereBetween('sale_date', [$lastMonthStart, $lastMonthEnd])
                  ->where('status', 'completed');
        })->count();

        $lastMonthInvoices = Invoice::whereBetween('invoice_date', [$lastMonthStart, $lastMonthEnd])
            ->where('status', '!=', 'cancelled')
            ->count();

        // Calculate percentage changes
        $salesChange = $lastMonthSales > 0 
            ? (($currentSales - $lastMonthSales) / $lastMonthSales) * 100 
            : ($currentSales > 0 ? 100 : 0);

        $customersChange = $lastMonthCustomers > 0 
            ? (($currentCustomers - $lastMonthCustomers) / $lastMonthCustomers) * 100 
            : ($currentCustomers > 0 ? 100 : 0);

        $invoicesChange = $lastMonthInvoices > 0 
            ? (($currentInvoices - $lastMonthInvoices) / $lastMonthInvoices) * 100 
            : ($currentInvoices > 0 ? 100 : 0);

        // Build sales report query
        $salesReportQuery = SaleItem::select(
                'products.sku',
                'products.name as product_name',
                'products.brand',
                'categories.name as category_name',
                DB::raw('SUM(sale_items.quantity) as sold_qty'),
                DB::raw('SUM(sale_items.total) as sold_amount'),
                DB::raw('MAX(products.stock_quantity) as instock_qty')
            )
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->whereBetween('sales.sale_date', [$startDate, $endDate])
            ->where('sales.status', 'completed');

        // Apply product filter
        if ($productId !== 'all') {
            $salesReportQuery->where('products.id', $productId);
        }

        $salesReport = $salesReportQuery
            ->groupBy('products.id', 'products.sku', 'products.name', 'products.brand', 'categories.name')
            ->orderBy('sold_amount', 'desc')
            ->paginate($perPage)
            ->appends($request->except('page'));

        // Get all products for filter dropdown
        $products = Product::where('is_active', true)->orderBy('name')->get();

        return view('reports.sales-report', compact(
            'currentSales',
            'currentCustomers',
            'currentInvoices',
            'salesChange',
            'customersChange',
            'invoicesChange',
            'salesReport',
            'products',
            'startDate',
            'endDate',
            'productId',
            'storeId'
        ));
    }
}
