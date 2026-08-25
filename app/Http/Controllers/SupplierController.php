<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\SupplierTransaction;
use App\Models\SupplierBill;
use App\Models\SupplierBillItem;
use App\Models\Product;
use App\Models\ProductHistory;
use App\Models\ProductUnit;
use App\Models\Category;
use App\Models\Unit;
use App\Services\BranchStockService;
use App\Support\CurrentBranch;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $query = Supplier::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('supplier_id', 'like', "%{$search}%");
            });
        }

        $suppliers = $query->latest()
            ->paginate($request->get('per_page', 10))
            ->appends($request->query());

        // Calculate totals from transactions for each supplier
        foreach ($suppliers as $supplier) {
            // Calculate from transactions: Credit = Amount Owed, Debit = Payment Made
            $creditTotal = $supplier->transactions()->where('type', 'credit')->sum('amount'); // Total Owed
            $debitTotal = $supplier->transactions()->where('type', 'debit')->sum('amount'); // Total Paid
            $balance = $creditTotal - $debitTotal; // Remaining
            
            $supplier->total_owed = $creditTotal;
            $supplier->total_paid = $debitTotal;
            $supplier->remaining = $balance;
            $supplier->hasUnpaid = $balance > 0;
        }

        // Calculate totals across ALL suppliers (not just paginated)
        $allSuppliersQuery = Supplier::query();
        if ($request->filled('search')) {
            $search = $request->search;
            $allSuppliersQuery->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('supplier_id', 'like', "%{$search}%");
            });
        }
        $allSuppliers = $allSuppliersQuery->get();
        
        $grandTotalOwed = 0;
        $grandTotalPaid = 0;
        $grandTotalRemaining = 0;
        
        foreach ($allSuppliers as $supplier) {
            $creditTotal = $supplier->transactions()->where('type', 'credit')->sum('amount');
            $debitTotal = $supplier->transactions()->where('type', 'debit')->sum('amount');
            $balance = $creditTotal - $debitTotal;
            
            $grandTotalOwed += $creditTotal;
            $grandTotalPaid += $debitTotal;
            $grandTotalRemaining += $balance;
        }

        return view('suppliers.index', compact('suppliers', 'grandTotalOwed', 'grandTotalPaid', 'grandTotalRemaining'));
    }

    public function printAllBills()
    {
        // Get all suppliers with their bills, transactions, and items
        $suppliers = Supplier::with([
            'bills' => function($query) {
                $query->with(['items', 'transactions'])->orderBy('bill_date', 'desc');
            },
            'transactions' => function($query) {
                $query->orderBy('transaction_date', 'desc')->orderBy('created_at', 'desc');
            }
        ])->get();

        // Process data for each supplier
        $suppliersData = [];
        foreach ($suppliers as $supplier) {
            // Calculate totals
            $creditTotal = $supplier->transactions()->where('type', 'credit')->sum('amount');
            $debitTotal = $supplier->transactions()->where('type', 'debit')->sum('amount');
            $balance = $creditTotal - $debitTotal;
            
            // Process bills
            $bills = [];
            foreach ($supplier->bills as $bill) {
                $bill->paid_amount = $bill->transactions()->where('type', 'debit')->sum('amount');
                $bill->remaining = $bill->bill_amount - $bill->paid_amount;
                
                $paymentHistory = $bill->transactions()
                    ->where('type', 'debit')
                    ->orderBy('transaction_date', 'asc')
                    ->get()
                    ->map(function($transaction) {
                        return [
                            'date' => $transaction->transaction_date->toDateString(),
                            'amount' => $transaction->amount,
                            'description' => $transaction->description,
                            'reference_number' => $transaction->reference_number,
                        ];
                    });
                
                $billItems = $bill->items()->get()->map(function($item) {
                    return [
                        'product_name' => $item->product_name,
                        'product_sku' => $item->product_sku,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'discount' => $item->discount,
                        'tax' => $item->tax,
                        'total' => $item->total,
                    ];
                });
                
                $bills[] = [
                    'id' => $bill->id,
                    'bill_number' => $bill->bill_number,
                    'bill_date' => $bill->bill_date->toDateString(),
                    'bill_amount' => $bill->bill_amount,
                    'paid_amount' => $bill->paid_amount,
                    'remaining' => $bill->remaining,
                    'description' => $bill->description,
                    'reference_number' => $bill->reference_number,
                    'bill_image' => $bill->bill_image,
                    'bill_items' => $billItems,
                    'payment_history' => $paymentHistory
                ];
            }
            
            // Process all transactions
            $allTransactions = $supplier->transactions()->get()->map(function($transaction) {
                return [
                    'id' => $transaction->id,
                    'type' => $transaction->type,
                    'amount' => $transaction->amount,
                    'description' => $transaction->description,
                    'transaction_date' => $transaction->transaction_date->toDateString(),
                    'reference_number' => $transaction->reference_number,
                    'supplier_bill_id' => $transaction->supplier_bill_id,
                ];
            });
            
            $suppliersData[] = [
                'supplier' => [
                    'id' => $supplier->id,
                    'supplier_id' => $supplier->supplier_id,
                    'name' => $supplier->name,
                    'company_name' => $supplier->company_name,
                    'email' => $supplier->email,
                    'phone' => $supplier->phone,
                    'address' => $supplier->address,
                    'city' => $supplier->city,
                    'state' => $supplier->state,
                    'country' => $supplier->country,
                ],
                'summary' => [
                    'total_owed' => $creditTotal,
                    'total_paid' => $debitTotal,
                    'remaining' => $balance,
                    'total_bills' => count($bills),
                    'total_transactions' => count($allTransactions),
                ],
                'bills' => $bills,
                'transactions' => $allTransactions
            ];
        }

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'suppliers' => $suppliersData
            ]);
        }

        return view('suppliers.print-all-bills', compact('suppliersData'));
    }

    public function printAllSuppliersReport(Request $request)
    {
        try {
            // Optional date range filters from request
            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');

            // Normalize simple date strings; if invalid, treat as null
            if ($fromDate && !strtotime($fromDate)) {
                $fromDate = null;
            }
            if ($toDate && !strtotime($toDate)) {
                $toDate = null;
            }

            // Get all suppliers (transactions will be queried per-supplier with date filters)
            $suppliers = Supplier::all();

            // If no explicit dates provided, determine overall date range from all supplier transactions
            if (!$fromDate) {
                $fromDate = SupplierTransaction::whereNotNull('transaction_date')->min('transaction_date');
            }
            if (!$toDate) {
                $toDate = SupplierTransaction::whereNotNull('transaction_date')->max('transaction_date');
            }

            // Process data for each supplier and calculate grand totals
            $suppliersData = [];
            $grandTotalOwed = 0;
            $grandTotalPaid = 0;
            $grandTotalRemaining = 0;
            
            foreach ($suppliers as $supplier) {
                // Build transactions query within optional date range
                $transactionsQuery = $supplier->transactions();
                if ($fromDate) {
                    $transactionsQuery->whereDate('transaction_date', '>=', $fromDate);
                }
                if ($toDate) {
                    $transactionsQuery->whereDate('transaction_date', '<=', $toDate);
                }

                // Clone for aggregations
                $transactionsForTotals = clone $transactionsQuery;

                // Calculate totals within date range (or complete if no dates)
                $creditTotal = (clone $transactionsForTotals)->where('type', 'credit')->sum('amount') ?? 0;
                $debitTotal = (clone $transactionsForTotals)->where('type', 'debit')->sum('amount') ?? 0;
                $balance = $creditTotal - $debitTotal;
                
                // Count transactions within range
                $totalTransactions = $transactionsForTotals->count();

                // Bills count remains overall for now (could also be filtered if needed)
                $totalBills = $supplier->bills()->count();

                // Detailed transactions list for this supplier in range
                $transactionsDetails = $transactionsQuery
                    ->orderBy('transaction_date')
                    ->orderBy('created_at')
                    ->get(['type', 'amount', 'description', 'transaction_date', 'reference_number', 'supplier_bill_id'])
                    ->map(function ($tx) {
                        return [
                            'type' => $tx->type,
                            'amount' => $tx->amount,
                            'description' => $tx->description,
                            'transaction_date' => optional($tx->transaction_date)->toDateString(),
                            'reference_number' => $tx->reference_number,
                            'supplier_bill_id' => $tx->supplier_bill_id,
                        ];
                    })
                    ->values()
                    ->all();
                
                $suppliersData[] = [
                    'supplier' => [
                        'id' => $supplier->id,
                        'supplier_id' => $supplier->supplier_id,
                        'name' => $supplier->name ?? '',
                        'company_name' => $supplier->company_name ?? '',
                        'email' => $supplier->email ?? '',
                        'phone' => $supplier->phone ?? '',
                        'address' => $supplier->address ?? '',
                        'city' => $supplier->city ?? '',
                        'state' => $supplier->state ?? '',
                        'country' => $supplier->country ?? '',
                    ],
                    'summary' => [
                        'total_owed' => $creditTotal,
                        'total_paid' => $debitTotal,
                        'remaining' => $balance,
                        'total_bills' => $totalBills,
                        'total_transactions' => $totalTransactions,
                    ],
                    'transactions' => $transactionsDetails,
                ];
                
                // Add to grand totals
                $grandTotalOwed += $creditTotal;
                $grandTotalPaid += $debitTotal;
                $grandTotalRemaining += $balance;
            }

            // Always return JSON for AJAX requests
            if (request()->expectsJson() || request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'suppliers' => $suppliersData,
                    'from_date' => $fromDate,
                    'to_date' => $toDate,
                    'grand_totals' => [
                        'total_owed' => $grandTotalOwed,
                        'total_paid' => $grandTotalPaid,
                        'remaining' => $grandTotalRemaining,
                    ],
                    'total_suppliers' => count($suppliersData),
                ]);
            }

            return view('suppliers.print-all-report', compact('suppliersData', 'fromDate', 'toDate'));
        } catch (\Exception $e) {
            if (request()->expectsJson() || request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'error' => $e->getTraceAsString()
                ], 500);
            }
            throw $e;
        }
    }

    public function create()
    {
        return view('suppliers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'nullable|string|unique:suppliers,supplier_id',
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:suppliers,email',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'country' => 'nullable|string',
            'postal_code' => 'nullable|string',
            'tax_id' => 'nullable|string',
        ]);

        if (($validated['email'] ?? '') === '') {
            $validated['email'] = null;
        }
        if (($validated['supplier_id'] ?? '') === '') {
            $validated['supplier_id'] = null;
        }

        Supplier::create($validated);
        return redirect()->route('suppliers.index')->with('success', 'Supplier created successfully.');
    }

    public function edit(Supplier $supplier)
    {
        return view('suppliers.edit', compact('supplier'));
    }

    public function show(Supplier $supplier)
    {
        $products = Product::query()
            ->where(function ($q) use ($supplier) {
                $q->where('supplier_id', $supplier->id)
                    ->orWhere('supplier_name', $supplier->name);
            })
            ->purchasableOnBill()
            ->with('currentBranchStock')
            ->get();

        $supplier->products = $products;
        $supplier->total_price = $products->sum(function($product) {
            return $product->purchase_price * $product->stock_quantity;
        });
        
        // Calculate from transactions
        // Credit = Amount Owed (supplier gave goods), Debit = Payment Made (we paid)
        $transactions = $supplier->transactions()->orderBy('transaction_date', 'desc')->orderBy('created_at', 'desc')->get();
        $creditTotal = $supplier->transactions()->where('type', 'credit')->sum('amount'); // Amount Owed
        $debitTotal = $supplier->transactions()->where('type', 'debit')->sum('amount'); // Payment Made
        $balance = $creditTotal - $debitTotal; // Amount Owed - Payment Made = Remaining
        
        // Get bills with remaining amounts (oldest first, same as ledger)
        $bills = $supplier->bills()->with('transactions')->orderBy('bill_date', 'asc')->orderBy('id', 'asc')->get()->map(function($bill) {
            $bill->paid_amount = $bill->transactions()->where('type', 'debit')->sum('amount'); // Payments are debits
            $bill->remaining = $bill->bill_amount - $bill->paid_amount;
            return $bill;
        });

        $ledgerEntries = $this->buildSupplierLedger($supplier, $balance);
        
        return view('suppliers.show', compact('supplier', 'transactions', 'creditTotal', 'debitTotal', 'balance', 'bills', 'ledgerEntries'));
    }

    /**
     * Chronological debit/credit ledger for supplier detail (same layout as customer ledger).
     * Credit = amount owed to supplier; Debit = payment made.
     *
     * @return array{rows: list<array<string, mixed>>, total_debit: float, total_credit: float, final_balance: float}
     */
    protected function buildSupplierLedger(Supplier $supplier, float $currentBalance): array
    {
        $transactions = $supplier->transactions()
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $entries = [];
        foreach ($transactions as $tx) {
            $isCredit = $tx->type === 'credit';
            $entries[] = [
                'date' => $tx->transaction_date,
                'type' => $isCredit ? 'Credit' : 'Payment',
                'ref' => $tx->reference_number ?: ($tx->supplier_bill_id ? '#' . $tx->supplier_bill_id : '-'),
                'narration' => filled($tx->description) ? $tx->description : ($isCredit ? 'Credit' : 'Payment'),
                'debit' => $isCredit ? null : (float) $tx->amount,
                'credit' => $isCredit ? (float) $tx->amount : null,
            ];
        }

        $totalCredit = array_sum(array_map(fn ($e) => $e['credit'] ?? 0, $entries));
        $totalDebit = array_sum(array_map(fn ($e) => $e['debit'] ?? 0, $entries));

        $opening = round($currentBalance - ($totalCredit - $totalDebit), 2);
        if (abs($opening) >= 0.01) {
            array_unshift($entries, [
                'date' => null,
                'type' => 'Opening',
                'ref' => '-',
                'narration' => 'Opening balance',
                'debit' => $opening < 0 ? abs($opening) : null,
                'credit' => $opening > 0 ? $opening : null,
            ]);
            if ($opening > 0) {
                $totalCredit += $opening;
            } else {
                $totalDebit += abs($opening);
            }
        }

        $running = 0.0;
        foreach ($entries as &$entry) {
            $running += ($entry['credit'] ?? 0) - ($entry['debit'] ?? 0);
            $entry['balance'] = round($running, 2);
        }
        unset($entry);

        return [
            'rows' => $entries,
            'total_debit' => round($totalDebit, 2),
            'total_credit' => round($totalCredit, 2),
            'final_balance' => round($running, 2),
        ];
    }

    public function printSupplierReport(Supplier $supplier)
    {
        // Get all bills with items and transactions
        $bills = $supplier->bills()->with(['items', 'transactions'])->orderBy('bill_date', 'desc')->get();
        
        // Calculate totals
        $creditTotal = $supplier->transactions()->where('type', 'credit')->sum('amount');
        $debitTotal = $supplier->transactions()->where('type', 'debit')->sum('amount');
        $balance = $creditTotal - $debitTotal;
        
        // Process bills data
        $billsData = [];
        foreach ($bills as $bill) {
            $bill->paid_amount = $bill->transactions()->where('type', 'debit')->sum('amount');
            $bill->remaining = $bill->bill_amount - $bill->paid_amount;
            
            $paymentHistory = $bill->transactions()
                ->where('type', 'debit')
                ->orderBy('transaction_date', 'asc')
                ->get()
                ->map(function($transaction) {
                    return [
                        'date' => $transaction->transaction_date->toDateString(),
                        'amount' => $transaction->amount,
                        'description' => $transaction->description,
                        'reference_number' => $transaction->reference_number,
                    ];
                });
            
            $billItems = $bill->items()->get()->map(function($item) {
                return [
                    'product_name' => $item->product_name,
                    'product_sku' => $item->product_sku,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'discount' => $item->discount,
                    'tax' => $item->tax,
                    'total' => $item->total,
                ];
            });
            
            $billsData[] = [
                'id' => $bill->id,
                'bill_number' => $bill->bill_number,
                'bill_date' => $bill->bill_date->toDateString(),
                'bill_amount' => $bill->bill_amount,
                'paid_amount' => $bill->paid_amount,
                'remaining' => $bill->remaining,
                'description' => $bill->description,
                'reference_number' => $bill->reference_number,
                'bill_image' => $bill->bill_image,
                'bill_items' => $billItems,
                'payment_history' => $paymentHistory
            ];
        }
        
        // Process all transactions
        $allTransactions = $supplier->transactions()->orderBy('transaction_date', 'desc')->orderBy('created_at', 'desc')->get()->map(function($transaction) {
            return [
                'id' => $transaction->id,
                'type' => $transaction->type,
                'amount' => $transaction->amount,
                'description' => $transaction->description,
                'transaction_date' => $transaction->transaction_date->toDateString(),
                'reference_number' => $transaction->reference_number,
                'supplier_bill_id' => $transaction->supplier_bill_id,
            ];
        });
        
        $supplierData = [
            'supplier' => [
                'id' => $supplier->id,
                'supplier_id' => $supplier->supplier_id,
                'name' => $supplier->name,
                'company_name' => $supplier->company_name,
                'email' => $supplier->email,
                'phone' => $supplier->phone,
                'address' => $supplier->address,
                'city' => $supplier->city,
                'state' => $supplier->state,
                'country' => $supplier->country,
            ],
            'summary' => [
                'total_owed' => $creditTotal,
                'total_paid' => $debitTotal,
                'remaining' => $balance,
                'total_bills' => count($billsData),
                'total_transactions' => count($allTransactions),
            ],
            'bills' => $billsData,
            'transactions' => $allTransactions
        ];

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'supplier' => $supplierData
            ]);
        }

        return view('suppliers.print-report', compact('supplierData'));
    }

    public function createTransaction(Supplier $supplier)
    {
        // Calculate wallet summary
        // Credit = Amount Owed, Debit = Payment Made
        $creditTotal = $supplier->transactions()->where('type', 'credit')->sum('amount'); // Amount Owed
        $debitTotal = $supplier->transactions()->where('type', 'debit')->sum('amount'); // Payment Made
        $balance = $creditTotal - $debitTotal;
        
        // Get bills with remaining amounts for payment selection
        $bills = $supplier->bills()->with('transactions')->get()->map(function ($bill) {
            $bill->remaining = $bill->bill_amount - $bill->transactions->where('type', 'debit')->sum('amount');
            return $bill;
        })->filter(function ($bill) {
            return $bill->remaining > 0;
        });

        $products = $this->billFormProducts();
        $productsData = $this->billFormProductsPayload($products);
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $units = Unit::where('is_active', true)->orderBy('name')->get();

        return view('suppliers.transactions.create', compact(
            'supplier',
            'creditTotal',
            'debitTotal',
            'balance',
            'bills',
            'products',
            'productsData',
            'categories',
            'units'
        ));
    }

    public function storeTransaction(Request $request, Supplier $supplier)
    {
        // Custom validation: if creating a bill, either amount or products must be provided
        $rules = [
            'type' => 'required|in:credit,debit',
            'amount' => 'nullable|numeric|min:0.01',
            'description' => 'nullable|string|max:1000',
            'transaction_date' => 'required|date',
            'reference_number' => 'nullable|string|max:255',
            'supplier_bill_id' => 'nullable|exists:supplier_bills,id',
            'create_bill' => 'nullable|boolean',
            'bill_number' => 'nullable|string|max:255',
            'bill_date' => 'nullable|date',
            'bill_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'paid_amount' => 'nullable|numeric|min:0',
            'products' => 'nullable|array',
            'products.*.product_id' => 'nullable|integer',
            'products.*.product_name' => 'required_without:products.*.product_id|string|max:255',
            'products.*.product_sku' => 'nullable|string|max:255',
            'products.*.quantity' => 'required|numeric|min:0.01',
            'products.*.unit_price' => 'required|numeric|min:0',
            'products.*.discount' => 'nullable|numeric|min:0|max:100',
            'products.*.tax' => 'nullable|numeric|min:0',
            'products.*.total' => 'required|numeric|min:0',
            'products.*.category_id' => 'nullable|exists:categories,id',
            'products.*.unit_id' => 'nullable|exists:units,id',
            'products.*.selling_type' => 'nullable|in:retail,wholesale,both',
            'products.*.retail_price' => 'nullable|numeric|min:0',
            'products.*.wholesale_price' => 'nullable|numeric|min:0',
        ];
        
        // If creating a bill without products, amount is required
        if ($request->has('create_bill') && $request->create_bill && empty($request->products)) {
            $rules['amount'] = 'required|numeric|min:0.01';
        }
        
        $validated = $request->validate($rules);
        $creatingBill = $validated['type'] === 'credit' && $request->has('create_bill') && $request->create_bill;

        if ($creatingBill) {
            $billAmount = $validated['amount'] ?? 0;
            if (! empty($request->products)) {
                $productTotal = collect($request->products)->sum('total');
                $billAmount = $productTotal;
                if (isset($validated['amount']) && abs($validated['amount'] - $productTotal) > 0.01) {
                    return back()->withErrors([
                        'amount' => 'Bill amount must match the total of all products. Product total: PKR ' . number_format($productTotal, 2) . ', Amount entered: PKR ' . number_format($validated['amount'], 2)
                    ])->withInput();
                }
            }
            if (isset($validated['paid_amount']) && $validated['paid_amount'] > $billAmount) {
                return back()->withErrors([
                    'paid_amount' => 'Paid amount cannot exceed bill amount. Bill amount: PKR ' . number_format($billAmount, 2)
                ])->withInput();
            }
            $validated['amount'] = $billAmount;
        }

        $billImagePath = null;

        try {
            DB::transaction(function () use ($request, $supplier, &$validated, $creatingBill, &$billImagePath) {
                if ($creatingBill) {
                    if ($request->hasFile('bill_image')) {
                        $billImagePath = $request->file('bill_image')->store('supplier-bills', 'public');
                    }

                    $bill = $supplier->bills()->create([
                        'bill_number' => $validated['bill_number'] ?? null,
                        'bill_amount' => $validated['amount'] ?? 0,
                        'bill_date' => $validated['bill_date'] ?? $validated['transaction_date'],
                        'description' => $validated['description'] ?? null,
                        'reference_number' => $validated['reference_number'] ?? null,
                        'bill_image' => $billImagePath,
                    ]);

                    if (! empty($request->products)) {
                        foreach ($request->products as $productData) {
                            $resolved = $this->resolveBillProduct($productData, $supplier);
                            $product = $resolved['product'];
                            $created = $resolved['created'];
                            $quantityToAdd = (float) ($productData['quantity'] ?? 0);
                            $purchasePrice = (float) ($productData['unit_price'] ?? 0);
                            $stockBranchId = CurrentBranch::requireId();
                            $oldStock = $product->currentStock($stockBranchId);
                            $oldPrice = (float) ($product->getAttributes()['purchase_price'] ?? 0);
                            $newStock = $quantityToAdd > 0
                                ? $product->incrementStock($quantityToAdd, $stockBranchId)
                                : $oldStock;

                            ProductHistory::create([
                                'product_id' => $product->id,
                                'supplier_id' => $supplier->id,
                                'supplier_bill_id' => $bill->id,
                                'type' => $created ? 'created' : ($quantityToAdd > 0 ? 'quantity_added' : 'price_updated'),
                                'quantity_added' => $quantityToAdd,
                                'old_price' => $created ? null : $oldPrice,
                                'new_price' => $purchasePrice,
                                'old_stock_quantity' => $created ? 0 : $oldStock,
                                'new_stock_quantity' => $newStock,
                                'notes' => $created
                                    ? 'Product created from supplier bill'
                                    : "Updated from supplier bill #{$bill->bill_number}",
                                'transaction_date' => $validated['bill_date'] ?? $validated['transaction_date'],
                            ]);

                            SupplierBillItem::create([
                                'supplier_bill_id' => $bill->id,
                                'product_id' => $product->id,
                                'product_name' => $productData['product_name'] ?? $product->name,
                                'product_sku' => $productData['product_sku'] ?? $product->sku,
                                'quantity' => $quantityToAdd,
                                'unit_price' => $purchasePrice,
                                'discount' => $productData['discount'] ?? 0,
                                'tax' => $productData['tax'] ?? 0,
                                'total' => $productData['total'] ?? 0,
                            ]);
                        }
                    }

                    $validated['supplier_bill_id'] = $bill->id;

                    if (isset($validated['paid_amount']) && $validated['paid_amount'] > 0) {
                        $supplier->transactions()->create([
                            'type' => 'debit',
                            'amount' => $validated['paid_amount'],
                            'description' => $validated['description'] ?? 'Payment for bill #' . ($bill->bill_number ?? $bill->id),
                            'transaction_date' => $validated['transaction_date'],
                            'reference_number' => $validated['reference_number'] ?? null,
                            'supplier_bill_id' => $bill->id,
                        ]);
                    }
                }

                unset($validated['create_bill'], $validated['bill_number'], $validated['bill_date'], $validated['products'], $validated['paid_amount']);

                if (isset($validated['amount']) && $validated['amount'] > 0) {
                    $supplier->transactions()->create($validated);
                }
            });
        } catch (InvalidArgumentException $e) {
            if ($billImagePath) {
                Storage::disk('public')->delete($billImagePath);
            }

            return back()->withErrors(['products' => $e->getMessage()])->withInput();
        } catch (QueryException $e) {
            if ($billImagePath) {
                Storage::disk('public')->delete($billImagePath);
            }
            if ($this->isUniqueConstraintFailure($e)) {
                return back()->withErrors([
                    'products' => 'This product SKU already exists. Pick the product from the list instead of typing a new one.',
                ])->withInput();
            }
            throw $e;
        }

        return redirect()->route('suppliers.show', $supplier)->with('success', 'Transaction added successfully.');
    }

    public function editTransaction(Supplier $supplier, SupplierTransaction $transaction)
    {
        // Verify transaction belongs to supplier
        if ($transaction->supplier_id !== $supplier->id) {
            abort(404);
        }

        // Calculate wallet summary
        $creditTotal = $supplier->transactions()->where('type', 'credit')->sum('amount');
        $debitTotal = $supplier->transactions()->where('type', 'debit')->sum('amount');
        $balance = $creditTotal - $debitTotal;
        
        // Get bills with remaining amounts for payment selection
        $bills = $supplier->bills()->with('transactions')->get()->map(function($bill) {
            $bill->remaining = $bill->bill_amount - $bill->transactions()->where('type', 'debit')->sum('amount');
            return $bill;
        })->filter(function($bill) {
            return $bill->remaining > 0;
        });
        
        return view('suppliers.transactions.edit', compact('supplier', 'transaction', 'creditTotal', 'debitTotal', 'balance', 'bills'));
    }

    public function updateTransaction(Request $request, Supplier $supplier, SupplierTransaction $transaction)
    {
        // Verify transaction belongs to supplier
        if ($transaction->supplier_id !== $supplier->id) {
            abort(404);
        }

        $rules = [
            'type' => 'required|in:credit,debit',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:1000',
            'transaction_date' => 'required|date',
            'reference_number' => 'nullable|string|max:255',
            'supplier_bill_id' => 'nullable|exists:supplier_bills,id',
        ];
        
        $validated = $request->validate($rules);
        
        $transaction->update($validated);

        return redirect()->route('suppliers.show', $supplier)->with('success', 'Transaction updated successfully.');
    }

    public function editBill(Supplier $supplier, SupplierBill $bill)
    {
        // Verify bill belongs to supplier
        if ($bill->supplier_id !== $supplier->id) {
            abort(404);
        }

        // Load bill with items
        $bill->load('items');
        
        // Get products, categories, and units for product selection
        $products = $this->billFormProducts();
        $productsData = $this->billFormProductsPayload($products);
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $units = Unit::where('is_active', true)->orderBy('name')->get();
        
        // Calculate wallet summary
        $creditTotal = $supplier->transactions()->where('type', 'credit')->sum('amount');
        $debitTotal = $supplier->transactions()->where('type', 'debit')->sum('amount');
        $balance = $creditTotal - $debitTotal;
        
        return view('suppliers.bills.edit', compact('supplier', 'bill', 'products', 'productsData', 'categories', 'units', 'creditTotal', 'debitTotal', 'balance'));
    }

    public function updateBill(Request $request, Supplier $supplier, SupplierBill $bill)
    {
        // Verify bill belongs to supplier
        if ($bill->supplier_id !== $supplier->id) {
            abort(404);
        }

        $rules = [
            'bill_number' => 'nullable|string|max:255',
            'bill_date' => 'required|date',
            'bill_amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:1000',
            'reference_number' => 'nullable|string|max:255',
            'bill_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'products' => 'nullable|array',
            'products.*.product_id' => 'nullable|integer',
            'products.*.product_name' => 'required_without:products.*.product_id|string|max:255',
            'products.*.product_sku' => 'nullable|string|max:255',
            'products.*.quantity' => 'required|numeric|min:0.01',
            'products.*.unit_price' => 'required|numeric|min:0',
            'products.*.discount' => 'nullable|numeric|min:0|max:100',
            'products.*.tax' => 'nullable|numeric|min:0',
            'products.*.total' => 'required|numeric|min:0',
        ];
        
        $validated = $request->validate($rules);
        
        // Calculate bill amount from products if provided
        $billAmount = $validated['bill_amount'];
        $syncProducts = $request->has('products');
        $productRows = $syncProducts ? ($request->input('products') ?? []) : null;

        if ($syncProducts && ! empty($productRows)) {
            $productTotal = collect($productRows)->sum('total');
            $billAmount = $productTotal;
            
            // Validate that amount matches product total
            if (abs($validated['bill_amount'] - $productTotal) > 0.01) {
                return back()->withErrors([
                    'bill_amount' => 'Bill amount must match the total of all products. Product total: PKR ' . number_format($productTotal, 2)
                ])->withInput();
            }
        } elseif ($syncProducts && empty($productRows)) {
            return back()->withErrors([
                'products' => 'Add at least one product, or cancel without clearing all bill lines.',
            ])->withInput();
        }
        
        // Handle bill image upload or removal
        $billImagePath = $bill->bill_image;
        
        if ($request->has('remove_image') && $request->remove_image == '1') {
            // Delete old image if exists
            if ($bill->bill_image) {
                Storage::disk('public')->delete($bill->bill_image);
            }
            $billImagePath = null;
        } elseif ($request->hasFile('bill_image')) {
            // Delete old image if exists
            if ($bill->bill_image) {
                Storage::disk('public')->delete($bill->bill_image);
            }
            $billImagePath = $request->file('bill_image')->store('supplier-bills', 'public');
        }
        
        // Store old values for transaction update
        $oldBillAmount = $bill->bill_amount;
        $oldBillDate = $bill->bill_date;

        try {
            DB::beginTransaction();

            // Snapshot old purchased quantities before rewriting lines (purchase bill → stock in)
            $oldQtyByProduct = [];
            if ($syncProducts) {
                $bill->loadMissing('items');
                foreach ($bill->items as $oldItem) {
                    if (! $oldItem->product_id) {
                        continue;
                    }
                    $pid = (int) $oldItem->product_id;
                    $oldQtyByProduct[$pid] = ($oldQtyByProduct[$pid] ?? 0) + (float) $oldItem->quantity;
                }
            }
            
            // Update bill
            $bill->update([
                'bill_number' => $validated['bill_number'] ?? null,
                'bill_amount' => $billAmount,
                'bill_date' => $validated['bill_date'],
                'description' => $validated['description'] ?? null,
                'reference_number' => $validated['reference_number'] ?? null,
                'bill_image' => $billImagePath,
            ]);
            
            $newQtyByProduct = [];

            // Update or recreate bill items if products are provided
            if ($syncProducts) {
                $bill->items()->delete();
                
                foreach ($productRows as $productData) {
                    $resolved = $this->resolveBillProduct($productData, $supplier);
                    $product = $resolved['product'];
                    $productId = (int) $product->id;
                    $quantity = (float) ($productData['quantity'] ?? 0);

                    SupplierBillItem::create([
                        'supplier_bill_id' => $bill->id,
                        'product_id' => $productId,
                        'product_name' => $productData['product_name'] ?? $product->name,
                        'product_sku' => $productData['product_sku'] ?? $product->sku,
                        'quantity' => $quantity,
                        'unit_price' => $productData['unit_price'] ?? 0,
                        'discount' => $productData['discount'] ?? 0,
                        'tax' => $productData['tax'] ?? 0,
                        'total' => $productData['total'] ?? 0,
                    ]);

                    if ($quantity > 0) {
                        $newQtyByProduct[$productId] = ($newQtyByProduct[$productId] ?? 0) + $quantity;
                    }
                }

                // Apply stock delta once per product: new purchased qty - old purchased qty
                $billBranchId = (int) ($bill->branch_id ?? CurrentBranch::requireId());
                $productIds = array_unique(array_merge(array_keys($oldQtyByProduct), array_keys($newQtyByProduct)));
                foreach ($productIds as $productId) {
                    $oldQty = (float) ($oldQtyByProduct[$productId] ?? 0);
                    $newQty = (float) ($newQtyByProduct[$productId] ?? 0);
                    $delta = $newQty - $oldQty;

                    $product = Product::query()->find($productId);
                    if (! $product || ! $product->isPurchasableByCurrentBranch($billBranchId)) {
                        continue;
                    }

                    // If this bill never recorded stock history for the product and branch stock
                    // is still empty while the bill has qty, apply the full purchased qty once.
                    if (abs($delta) < 0.000001 && $newQty > 0) {
                        $hasHistory = ProductHistory::query()
                            ->where('supplier_bill_id', $bill->id)
                            ->where('product_id', $productId)
                            ->exists();
                        $currentStock = app(BranchStockService::class)->get($product, $billBranchId);
                        if (! $hasHistory && $currentStock < 0.000001) {
                            $delta = $newQty;
                        }
                    }

                    if (abs($delta) < 0.000001) {
                        continue;
                    }

                    $stockBefore = app(BranchStockService::class)->get($product, $billBranchId);

                    if ($delta > 0) {
                        $stockAfter = $product->incrementStock($delta, $billBranchId);
                    } else {
                        $stockAfter = $product->decrementStock(abs($delta), $billBranchId);
                    }

                    ProductHistory::create([
                        'product_id' => $productId,
                        'supplier_id' => $supplier->id,
                        'supplier_bill_id' => $bill->id,
                        'type' => $delta > 0 ? 'quantity_added' : 'quantity_removed',
                        'quantity_added' => $delta,
                        'old_price' => $product->purchase_price,
                        'new_price' => $product->purchase_price,
                        'old_stock_quantity' => $stockBefore,
                        'new_stock_quantity' => $stockAfter,
                        'notes' => 'Stock adjusted from supplier bill edit #'.($bill->bill_number ?? $bill->id),
                        'transaction_date' => $validated['bill_date'],
                    ]);
                }
            }
            
            // Update related credit transaction if bill amount or date changed
            $creditTransaction = $supplier->transactions()
                ->where('type', 'credit')
                ->where('supplier_bill_id', $bill->id)
                ->first();
                
            if ($creditTransaction) {
                $updateData = [];
                
                // Update amount if it changed
                if (abs($oldBillAmount - $billAmount) > 0.01) {
                    $updateData['amount'] = $billAmount;
                }
                
                // Update transaction date if bill date changed
                if ($oldBillDate->format('Y-m-d') !== $validated['bill_date']) {
                    $updateData['transaction_date'] = $validated['bill_date'];
                }
                
                // Update description if changed
                if (($validated['description'] ?? null) && $creditTransaction->description !== $validated['description']) {
                    $updateData['description'] = $validated['description'];
                }
                
                if (! empty($updateData)) {
                    $creditTransaction->update($updateData);
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withErrors([
                'bill_amount' => 'Could not update bill: '.$e->getMessage(),
            ])->withInput();
        }
        
        return redirect()->route('suppliers.show', $supplier)->with('success', 'Bill updated successfully.');
    }

    public function printBillReceipt(Supplier $supplier, $billId)
    {
        $bill = $supplier->bills()->findOrFail($billId);
        
        // Calculate paid amount
        $bill->paid_amount = $bill->transactions()->where('type', 'debit')->sum('amount');
        $bill->remaining = $bill->bill_amount - $bill->paid_amount;
        
        // Get payment history (debit transactions for this bill)
        $paymentHistory = $bill->transactions()
            ->where('type', 'debit')
            ->orderBy('transaction_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function($transaction) {
                return [
                    'id' => $transaction->id,
                    'date' => $transaction->transaction_date->toDateString(),
                    'amount' => $transaction->amount,
                    'description' => $transaction->description,
                    'reference_number' => $transaction->reference_number,
                ];
            });
        
        // Get bill items (products)
        $billItems = $bill->items()->get()->map(function($item) {
            return [
                'id' => $item->id,
                'product_name' => $item->product_name,
                'product_sku' => $item->product_sku,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'discount' => $item->discount,
                'tax' => $item->tax,
                'total' => $item->total,
            ];
        });
        
        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'bill' => [
                    'id' => $bill->id,
                    'bill_number' => $bill->bill_number,
                    'bill_date' => $bill->bill_date->toDateString(),
                    'bill_amount' => $bill->bill_amount,
                    'paid_amount' => $bill->paid_amount,
                    'remaining' => $bill->remaining,
                    'description' => $bill->description,
                    'reference_number' => $bill->reference_number,
                ],
                'supplier' => [
                    'id' => $supplier->id,
                    'name' => $supplier->name,
                    'company_name' => $supplier->company_name,
                    'email' => $supplier->email,
                    'phone' => $supplier->phone,
                    'address' => $supplier->address,
                ],
                'bill_items' => $billItems,
                'payment_history' => $paymentHistory
            ]);
        }
        
        return view('suppliers.bills.receipt', compact('supplier', 'bill', 'paymentHistory'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'supplier_id' => 'nullable|string|unique:suppliers,supplier_id,' . $supplier->id,
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:suppliers,email,' . $supplier->id,
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'country' => 'nullable|string',
            'postal_code' => 'nullable|string',
            'tax_id' => 'nullable|string',
        ]);

        $supplier->update($validated);
        return redirect()->route('suppliers.index')->with('success', 'Supplier updated successfully.');
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
        return redirect()->route('suppliers.index')->with('success', 'Supplier deleted successfully.');
    }

    /**
     * Products the bill form may autocomplete: this branch's membership or
     * products it owns (including wipe orphans). Phandu catalog is not listed;
     * typing a new name creates a branch-owned product.
     */
    private function billFormProducts()
    {
        return Product::query()
            ->where('is_active', true)
            ->purchasableOnBill()
            ->with(['unit', 'baseUnit', 'productUnits.unit', 'currentBranchStock'])
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Product>  $products
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function billFormProductsPayload($products)
    {
        return $products->map(function (Product $p) {
            $baseUnitId = $p->base_unit_id ?? $p->unit_id;
            $availableUnits = collect();

            if ($p->baseUnit) {
                $availableUnits->push([
                    'id' => $p->baseUnit->id,
                    'name' => $p->baseUnit->name,
                    'short_name' => $p->baseUnit->short_name,
                    'is_base_unit' => true,
                ]);
            } elseif ($p->unit) {
                $availableUnits->push([
                    'id' => $p->unit->id,
                    'name' => $p->unit->name,
                    'short_name' => $p->unit->short_name,
                    'is_base_unit' => true,
                ]);
            }

            foreach ($p->productUnits as $pu) {
                if (! $pu->is_active || ! $pu->unit || (int) $pu->unit->id === (int) $baseUnitId) {
                    continue;
                }
                $availableUnits->push([
                    'id' => $pu->unit->id,
                    'name' => $pu->unit->name,
                    'short_name' => $pu->unit->short_name,
                    'is_base_unit' => false,
                ]);
            }

            return [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku ?? '',
                'purchase_price' => $p->purchase_price ?? 0,
                'unit_id' => $p->unit_id ?? null,
                'base_unit_id' => $baseUnitId,
                'unit_name' => $p->unit ? $p->unit->short_name : '',
                'selling_type' => $p->selling_type ?? 'both',
                'retail_price' => $p->retail_price ?? 0,
                'wholesale_price' => $p->wholesale_price ?? 0,
                'available_units' => $availableUnits->values()->all(),
            ];
        })->values();
    }

    private function isUniqueConstraintFailure(QueryException $e): bool
    {
        $sqlState = (string) ($e->errorInfo[0] ?? '');
        $driverCode = (int) ($e->errorInfo[1] ?? 0);

        return $sqlState === '23000' || $driverCode === 1062 || str_contains($e->getMessage(), 'Duplicate entry');
    }

    /**
     * @param  array<string, mixed>  $productData
     * @return array{product: Product, created: bool}
     */
    private function resolveBillProduct(array $productData, Supplier $supplier): array
    {
        $productId = ! empty($productData['product_id']) ? (int) $productData['product_id'] : null;
        $productName = trim((string) ($productData['product_name'] ?? ''));
        $productSku = trim((string) ($productData['product_sku'] ?? ''));
        $product = $productId ? Product::query()->find($productId) : null;

        if ($product && ! $product->isPurchasableByCurrentBranch()) {
            $product = null;
        }

        if (! $product && $productSku !== '') {
            $bySku = Product::query()->where('sku', $productSku)->first();
            if ($bySku && $bySku->isPurchasableByCurrentBranch()) {
                $product = $bySku;
            }
        }

        if (! $product && $productName !== '') {
            $byName = Product::query()
                ->purchasableOnBill()
                ->where('name', $productName)
                ->first();
            if ($byName) {
                $product = $byName;
            }
        }

        if ($product) {
            $branchId = CurrentBranch::requireId();
            app(BranchStockService::class)->ensureMembership($product, $branchId);
            $this->syncBillProductPricing($product, $productData, $supplier, $branchId);

            return ['product' => $product->fresh() ?? $product, 'created' => false];
        }

        if ($productName === '') {
            throw new \InvalidArgumentException('Product name is required when adding a product from a supplier bill.');
        }

        return ['product' => $this->createProductFromBillLine($productData, $supplier), 'created' => true];
    }

    /**
     * @param  array<string, mixed>  $productData
     */
    private function createProductFromBillLine(array $productData, Supplier $supplier): Product
    {
        $productName = trim((string) $productData['product_name']);
        $productSku = $productData['product_sku'] ?? null;

        $categoryId = $productData['category_id'] ?? Category::query()->value('id');
        if (! $categoryId) {
            $categoryId = Category::query()->create([
                'name' => 'Uncategorized',
                'slug' => Str::slug('Uncategorized'),
                'is_active' => true,
            ])->id;
        }

        $unitId = $productData['unit_id'] ?? Unit::query()->value('id');
        if (! $unitId) {
            $unitId = Unit::query()->create([
                'name' => 'Piece',
                'short_name' => 'Pc',
                'is_active' => true,
            ])->id;
        }

        $purchasePrice = (float) ($productData['unit_price'] ?? 0);
        $sellingType = $productData['selling_type'] ?? 'both';
        [$retailPrice, $wholesalePrice, $sellingPrice] = $this->billLineSellingPrices($productData, $purchasePrice, $sellingType);
        $baseUnitId = $productData['base_unit_id'] ?? $unitId;
        $currentBranchId = CurrentBranch::requireId();

        if ($productSku && Product::query()->where('sku', $productSku)->exists()) {
            $productSku = $this->generateSku($productName);
        }

        $product = Product::query()->create([
            'name' => $productName,
            'slug' => Str::slug($productName.'-'.uniqid()),
            'sku' => $productSku ?: $this->generateSku($productName),
            'category_id' => $categoryId,
            'unit_id' => $unitId,
            'base_unit_id' => $baseUnitId,
            'supplier_id' => $supplier->id,
            'supplier_name' => $supplier->name,
            'purchase_price' => $purchasePrice,
            'selling_price' => $sellingPrice,
            'retail_price' => $retailPrice,
            'wholesale_price' => $wholesalePrice,
            'stock_quantity' => 0,
            'low_stock_threshold' => 10,
            'selling_type' => $sellingType,
            'product_type' => 'single',
            'is_active' => true,
            'user_id' => auth()->id(),
            'owner_branch_id' => $currentBranchId,
        ]);

        if ((int) ($product->getAttributes()['owner_branch_id'] ?? 0) !== (int) $currentBranchId) {
            $product->forceFill(['owner_branch_id' => $currentBranchId])->save();
        }

        app(BranchStockService::class)->initializeProduct(
            $product,
            0,
            $currentBranchId,
            $sellingType,
            false
        );

        ProductUnit::query()->create([
            'product_id' => $product->id,
            'unit_id' => $baseUnitId,
            'is_base_unit' => true,
            'selling_price' => $sellingPrice,
            'is_active' => true,
        ]);

        return $product;
    }

    /**
     * @param  array<string, mixed>  $productData
     * @return array{0: float, 1: float, 2: float}
     */
    private function billLineSellingPrices(array $productData, float $purchasePrice, string $sellingType): array
    {
        $retailPrice = (float) ($productData['retail_price'] ?? 0);
        $wholesalePrice = (float) ($productData['wholesale_price'] ?? 0);
        if ($retailPrice <= 0) {
            $retailPrice = $purchasePrice;
        }
        if ($wholesalePrice <= 0) {
            $wholesalePrice = $purchasePrice;
        }

        $sellingPrice = $purchasePrice;
        if ($sellingType === 'retail') {
            $sellingPrice = $retailPrice;
        } elseif ($sellingType === 'wholesale') {
            $sellingPrice = $wholesalePrice;
        } else {
            $sellingPrice = $retailPrice > 0 ? $retailPrice : $wholesalePrice;
        }

        if ($sellingPrice <= 0) {
            $sellingPrice = $purchasePrice;
        }

        return [$retailPrice, $wholesalePrice, $sellingPrice];
    }

    /**
     * @param  array<string, mixed>  $productData
     */
    private function syncBillProductPricing(Product $product, array $productData, Supplier $supplier, int $branchId): void
    {
        $purchasePrice = (float) ($productData['unit_price'] ?? $product->getAttributes()['purchase_price'] ?? 0);
        $sellingType = $productData['selling_type'] ?? ($product->getAttributes()['selling_type'] ?? 'both');
        [$retailPrice, $wholesalePrice, $sellingPrice] = $this->billLineSellingPrices($productData, $purchasePrice, $sellingType);

        if ($product->writesMasterForCurrentBranch($branchId)) {
            $attrs = $product->getAttributes();
            $payload = [
                'purchase_price' => $purchasePrice,
            ];
            if ((float) ($attrs['retail_price'] ?? 0) <= 0) {
                $payload['retail_price'] = $retailPrice;
            }
            if ((float) ($attrs['wholesale_price'] ?? 0) <= 0) {
                $payload['wholesale_price'] = $wholesalePrice;
            }
            if ((float) ($attrs['selling_price'] ?? 0) <= 0) {
                $payload['selling_price'] = $sellingPrice;
            }
            if (! empty($productData['selling_type'])) {
                $payload['selling_type'] = $sellingType;
            }
            if ((int) $product->owner_branch_id === (int) $branchId) {
                $payload['supplier_id'] = $supplier->id;
                $payload['supplier_name'] = $supplier->name;
            }
            $product->update($payload);

            return;
        }

        $overrides = [
            'purchase_price' => $purchasePrice,
        ];
        if (! empty($productData['selling_type'])) {
            $overrides['selling_type'] = $sellingType;
        }
        if ((float) ($productData['retail_price'] ?? 0) > 0) {
            $overrides['retail_price'] = $retailPrice;
            $overrides['selling_price'] = $sellingPrice;
        }
        if ((float) ($productData['wholesale_price'] ?? 0) > 0) {
            $overrides['wholesale_price'] = $wholesalePrice;
        }

        app(BranchStockService::class)->setOverrides($product, $overrides, $branchId);
    }

    /**
     * Generate a unique SKU for the product
     */
    private function generateSku($productName = null)
    {
        // Get initials from product name if provided
        $prefix = 'PRD';
        if ($productName) {
            $words = explode(' ', strtoupper($productName));
            $initials = '';
            foreach ($words as $word) {
                if (!empty($word)) {
                    $initials .= substr($word, 0, 1);
                }
            }
            if (strlen($initials) >= 2) {
                $prefix = substr($initials, 0, 3);
            }
        }

        // Generate date part (YYYYMMDD)
        $datePart = date('Ymd');

        // Generate random number part (4 digits)
        $randomPart = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

        // Combine: PREFIX-YYYYMMDD-XXXX
        $sku = $prefix . '-' . $datePart . '-' . $randomPart;

        // Check if SKU already exists, if so, regenerate
        $counter = 1;
        while (Product::where('sku', $sku)->exists()) {
            $randomPart = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
            $sku = $prefix . '-' . $datePart . '-' . $randomPart;
            $counter++;
            // Prevent infinite loop
            if ($counter > 100) {
                $sku = $prefix . '-' . $datePart . '-' . time();
                break;
            }
        }

        return $sku;
    }
}
