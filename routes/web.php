<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\SalesReturnController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\POSController;
use App\Http\Controllers\SalesReportController;
use App\Http\Controllers\InvoiceReportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserActivityController;
use App\Http\Controllers\AiInsightsController;
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\SyncController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    // Offline sync API
    Route::prefix('sync')->name('sync.')->group(function () {
        Route::get('/ping', [SyncController::class, 'ping'])->name('ping');
        Route::get('/bootstrap', [SyncController::class, 'bootstrap'])->name('bootstrap');
        Route::get('/pull', [SyncController::class, 'pull'])->name('pull');
        Route::post('/push', [SyncController::class, 'push'])->name('push');
        Route::post('/enroll-vault', [SyncController::class, 'enrollVault'])->name('enroll-vault');
    });

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Products routes
    Route::get('/products/low-stocks', [ProductController::class, 'lowStocks'])->name('products.low-stocks');
    Route::get('/products/api/all', [ProductController::class, 'getAllProducts'])->name('products.api.all');
    Route::get('/products/{product}/units', [ProductController::class, 'getProductUnits'])->name('products.units');
    Route::get('/products/{product}/conversion/{fromUnit}/{toUnit}', [ProductController::class, 'getConversion'])->name('products.conversion');
    Route::resource('products', ProductController::class);

    // Categories routes
    Route::resource('categories', CategoryController::class);

    // Units routes
    Route::resource('units', UnitController::class);

    // Expenses routes
    Route::resource('expenses', ExpenseController::class);

    // Customers routes - specific routes before resource route
    Route::get('customers/print-all-report', [CustomerController::class, 'printAllCustomersReport'])->name('customers.print.all.report');
    Route::get('customers/{customer}/day-wise-bills', [CustomerController::class, 'dayWiseBills'])->name('customers.day-wise-bills');
    Route::post('customers/{customer}/previous-balance', [CustomerController::class, 'addPreviousBalance'])->name('customers.previous-balance');
    Route::resource('customers', CustomerController::class);

    // Suppliers routes - specific routes before resource route
    Route::get('suppliers/print-all-report', [SupplierController::class, 'printAllSuppliersReport'])->name('suppliers.print.all.report');
    Route::get('suppliers/{supplier}/transactions/create', [SupplierController::class, 'createTransaction'])->name('suppliers.transactions.create');
    Route::post('suppliers/{supplier}/transactions', [SupplierController::class, 'storeTransaction'])->name('suppliers.transactions.store');
    Route::get('suppliers/{supplier}/transactions/{transaction}/edit', [SupplierController::class, 'editTransaction'])->name('suppliers.transactions.edit');
    Route::put('suppliers/{supplier}/transactions/{transaction}', [SupplierController::class, 'updateTransaction'])->name('suppliers.transactions.update');
    Route::get('suppliers/{supplier}/bills/{bill}/edit', [SupplierController::class, 'editBill'])->name('suppliers.bills.edit');
    Route::put('suppliers/{supplier}/bills/{bill}', [SupplierController::class, 'updateBill'])->name('suppliers.bills.update');
    Route::get('suppliers/{supplier}/bills/{billId}/receipt', [SupplierController::class, 'printBillReceipt'])->name('suppliers.bills.receipt');
    Route::get('suppliers/{supplier}/print-report', [SupplierController::class, 'printSupplierReport'])->name('suppliers.print.report');
    Route::resource('suppliers', SupplierController::class);

    // Sales routes - POS and sub-routes must come before resource route
    Route::prefix('sales')->name('sales.')->group(function () {
        Route::prefix('pos')->name('pos.')->group(function () {
            Route::get('/', [POSController::class, 'index'])->name('index');
            Route::post('/process', [POSController::class, 'process'])->name('process');
            Route::post('/hold', [POSController::class, 'hold'])->name('hold');
            Route::get('/hold-orders', [POSController::class, 'getHoldOrders'])->name('hold-orders');
            Route::get('/load-hold-order/{id}', [POSController::class, 'loadHoldOrder'])->name('load-hold-order');
            Route::delete('/hold-order/{id}', [POSController::class, 'deleteHoldOrder'])->name('delete-hold-order');
            Route::get('/last-order-items/{customerId}', [POSController::class, 'getLastOrderItems'])->name('last-order-items');
            Route::get('/last-price/{customerId}/{productId}', [POSController::class, 'getLastProductPrice'])->name('last-price');
        });
        Route::prefix('invoices')->name('invoices.')->group(function () {
            Route::resource('', InvoiceController::class)->parameters(['' => 'invoice']);
        });
        Route::prefix('returns')->name('returns.')->group(function () {
            Route::resource('', SalesReturnController::class)->parameters(['' => 'return']);
        });
    });
    // Sales routes - specific routes before resource route
    Route::get('sales/print-report', [SaleController::class, 'printSalesReport'])->name('sales.print-report');
    Route::post('sales/payment', [SaleController::class, 'payment'])->name('sales.payment');
    Route::resource('sales', SaleController::class);

    // Orders routes
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/completed', [OrderController::class, 'completed'])->name('completed');
        Route::get('/pending', [OrderController::class, 'pending'])->name('pending');
        Route::get('/on-hold', [OrderController::class, 'onHold'])->name('on-hold');
    });
    Route::resource('orders', OrderController::class);

    // Reports routes
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/sales-report', [SalesReportController::class, 'index'])->name('sales-report');
        Route::get('/invoice-report', [InvoiceReportController::class, 'index'])->name('invoice-report');
    });

    // AI Insights routes
    Route::prefix('ai-insights')->name('ai-insights.')->group(function () {
        Route::get('/', [AiInsightsController::class, 'index'])->name('index');
        Route::get('/forecast', [AiInsightsController::class, 'forecast'])->name('forecast');
        Route::get('/inventory', [AiInsightsController::class, 'inventory'])->name('inventory');
        Route::get('/customers', [AiInsightsController::class, 'customers'])->name('customers');
        Route::get('/recommendations', [AiInsightsController::class, 'recommendations'])->name('recommendations');
        Route::get('/anomalies', [AiInsightsController::class, 'anomalies'])->name('anomalies');
    });

    // User Activity routes
    Route::get('user-activities/{userActivity}', [UserActivityController::class, 'show'])->name('user-activities.show');
    Route::get('user-activities', [UserActivityController::class, 'index'])->name('user-activities.index');

    // System routes
    Route::get('health-check', [HealthCheckController::class, 'index'])->name('health-check.index');

    // Branches (admin only)
    Route::middleware('admin')->group(function () {
        Route::get('branches', [BranchController::class, 'index'])->name('branches.index');
        Route::get('branches/create', [BranchController::class, 'create'])->name('branches.create');
        Route::post('branches', [BranchController::class, 'store'])->name('branches.store');
        Route::get('branches/{branch}/edit', [BranchController::class, 'edit'])->name('branches.edit');
        Route::put('branches/{branch}', [BranchController::class, 'update'])->name('branches.update');
        Route::post('branches/{branch}/users', [BranchController::class, 'addUser'])->name('branches.users.add');
        Route::delete('branches/{branch}/users/{user}', [BranchController::class, 'removeUser'])->name('branches.users.remove');
        Route::post('branches/switch', [BranchController::class, 'switch'])->name('branches.switch');
    });
});

require __DIR__.'/auth.php';
