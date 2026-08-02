<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $query = Sale::with('customer', 'user');
        
        // Exclude ADJ bills from main query - they will be merged with their related Sale
        $query->where('sale_number', 'not like', 'ADJ-%');
        
        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $relatedSaleNumber = null;
            
            // If searching for an ADJ bill number, find the related Sale
            if (str_starts_with(strtoupper($search), 'ADJ-')) {
                $adjBill = Sale::where('sale_number', 'like', "%{$search}%")
                    ->where('sale_number', 'like', 'ADJ-%')
                    ->first();
                
                if ($adjBill && $adjBill->notes) {
                    // Extract sale number from notes: "Previous balance payment - Extra payment from Sale: SALE-000033"
                    if (preg_match('/Sale:\s*([A-Z]+-\d+)/i', $adjBill->notes, $matches)) {
                        $relatedSaleNumber = $matches[1];
                    }
                }
            }
            
            $query->where(function($q) use ($search, $relatedSaleNumber) {
                $q->where('sale_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($customerQuery) use ($search) {
                      $customerQuery->where('name', 'like', "%{$search}%");
                  });
                
                // Include related Sale if searching for ADJ bill
                if ($relatedSaleNumber) {
                    $q->orWhere('sale_number', $relatedSaleNumber);
                }
            });
        }

        // Status filter
        if ($request->filled('status') && $request->status !== 'all') {
            if ($request->status === 'on_hold') {
                // Include both on_hold status and draft orders with "Held Order" in notes
                $query->where(function($q) {
                    $q->where('status', 'on_hold')
                      ->orWhere(function($subQ) {
                          $subQ->where('status', 'draft')
                               ->where('notes', 'like', '%Held Order%');
                      });
                });
            } else {
                $query->where('status', $request->status);
            }
        }

        // Payment status filter
        if ($request->filled('payment_status') && $request->payment_status !== 'all') {
            $query->where('payment_status', $request->payment_status);
        }

        // Category filter (filter by products in sale items)
        if ($request->filled('category_id') && $request->category_id !== 'all') {
            $query->whereHas('items', function($itemQuery) use ($request) {
                $itemQuery->whereHas('product', function($productQuery) use ($request) {
                    $productQuery->where('category_id', $request->category_id);
                });
            });
        }

        // Date range filter
        if ($request->filled('start_date')) {
            $query->whereDate('sale_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('sale_date', '<=', $request->end_date);
        }
        
        $sales = $query->latest()
            ->paginate($request->get('per_page', 15))
            ->appends($request->query());
        Sale::attachCalculatedBalances($sales->getCollection());

        // Get categories for filter
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        
        return view('sales.index', compact('sales', 'categories'));
    }

    public function create()
    {
        return view('sales.create');
    }

    public function store(Request $request)
    {
        // Implementation for storing sales
        return redirect()->route('sales.index')->with('success', 'Sale created successfully.');
    }

    public function show(Request $request, Sale $sale)
    {
        $sale->load(['customer', 'items.product.unit', 'items.unit']);
        Sale::attachCalculatedBalances(collect([$sale]));

        $previousBalance = (float) ($sale->previous_balance ?? 0);
        $adjPaidAmount = (float) ($sale->adj_paid_amount ?? 0);
        $adjBillNumber = $sale->adj_bill_number ?? null;
        $regularPaidAmount = max(0, (float) ($sale->db_paid_amount ?? (($sale->paid_amount ?? 0) - $adjPaidAmount)));
        $previousBalancePayment = $adjPaidAmount;
        
        // If JSON request, return JSON response
        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'sale' => [
                    'id' => $sale->id,
                    'sale_number' => $sale->sale_number,
                    'sale_date' => $sale->created_at ? $sale->created_at->format('Y-m-d h:i A') : ($sale->sale_date ? $sale->sale_date->format('Y-m-d') : null),
                    'total_amount' => $sale->total_amount ?? 0,
                    'paid_amount' => $sale->paid_amount ?? 0,
                    'regular_paid_amount' => $regularPaidAmount,
                    'adj_paid_amount' => $adjPaidAmount,
                    'adj_bill_number' => $adjBillNumber,
                    'payment_method' => $sale->payment_method ?? 'cash',
                    'customer_name' => $sale->customer ? $sale->customer->name : ($sale->customer_name ?? 'Walk-in Customer'),
                    'previous_balance' => round($previousBalance, 2),
                    'previous_balance_payment' => round($previousBalancePayment, 2),
                    'items' => $sale->items->map(function($item) {
                        // Prioritize unit_id from sale_item, then fallback to product unit
                        $unitId = $item->unit_id ?? ($item->product ? ($item->product->base_unit_id ?? $item->product->unit_id) : null);
                        $unitShortName = 'Pcs';
                        
                        if ($unitId) {
                            // Try to get unit from item relationship first
                            if ($item->unit) {
                                $unitShortName = $item->unit->short_name;
                            } elseif ($item->product && $item->product->unit && $item->product->unit->id == $unitId) {
                                $unitShortName = $item->product->unit->short_name;
                            } else {
                                // Load unit if not loaded
                                $unit = \App\Models\Unit::find($unitId);
                                if ($unit) {
                                    $unitShortName = $unit->short_name;
                                }
                            }
                        } elseif ($item->product && $item->product->unit) {
                            $unitShortName = $item->product->unit->short_name;
                        }
                        
                        return [
                            'id' => $item->id,
                            'product_id' => $item->product_id,
                            'product_name' => $item->product_name ?? ($item->product->name ?? 'N/A'),
                            'quantity' => $item->quantity,
                            'unit_id' => $unitId,
                            'unit_price' => $item->unit_price,
                            'discount' => $item->discount ?? 0,
                            'total' => $item->total ?? 0,
                            'unit_name' => $unitShortName,
                            'unit_short_name' => $unitShortName
                        ];
                    })
                ]
            ]);
        }
        
        // Load unit relationships for items
        $sale->load('items.unit');
        
        return view('sales.show', compact('sale'));
    }

    public function edit(Sale $sale)
    {
        return view('sales.edit', compact('sale'));
    }

    public function update(Request $request, Sale $sale)
    {
        // Implementation for updating sales
        return redirect()->route('sales.index')->with('success', 'Sale updated successfully.');
    }

    public function destroy(Sale $sale)
    {
        $sale->delete();
        return redirect()->route('sales.index')->with('success', 'Sale deleted successfully.');
    }

    public function printSalesReport(Request $request)
    {
        try {
            $query = Sale::with('customer', 'user', 'items.product.unit');
            
            // Exclude ADJ bills from main query - they will be merged with their related Sale
            $query->where('sale_number', 'not like', 'ADJ-%');
            
            // Apply same filters as index method
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('sale_number', 'like', "%{$search}%")
                      ->orWhereHas('customer', function($customerQuery) use ($search) {
                          $customerQuery->where('name', 'like', "%{$search}%");
                      });
                });
            }

            if ($request->filled('status') && $request->status !== 'all') {
                if ($request->status === 'on_hold') {
                    $query->where(function($q) {
                        $q->where('status', 'on_hold')
                          ->orWhere(function($subQ) {
                              $subQ->where('status', 'draft')
                                   ->where('notes', 'like', '%Held Order%');
                          });
                    });
                } else {
                    $query->where('status', $request->status);
                }
            }

            if ($request->filled('payment_status') && $request->payment_status !== 'all') {
                $query->where('payment_status', $request->payment_status);
            }

            if ($request->filled('category_id') && $request->category_id !== 'all') {
                $query->whereHas('items', function($itemQuery) use ($request) {
                    $itemQuery->whereHas('product', function($productQuery) use ($request) {
                        $productQuery->where('category_id', $request->category_id);
                    });
                });
            }

            if ($request->filled('start_date')) {
                $query->whereDate('sale_date', '>=', $request->start_date);
            }
            if ($request->filled('end_date')) {
                $query->whereDate('sale_date', '<=', $request->end_date);
            }

            $sales = $query->orderBy('sale_date')->orderBy('created_at')->get();
            Sale::attachCalculatedBalances($sales);

            // Calculate totals
            $totalSales = $sales->count();
            $totalAmount = $sales->sum('total_amount');
            $totalPaid = $sales->sum(fn ($sale) => (float) ($sale->paid_amount ?? 0));
            $totalRemaining = $sales->sum(fn ($sale) => (float) ($sale->remaining_balance_due ?? 0));

            // Get filter info for report
            $filters = [
                'search' => $request->input('search'),
                'status' => $request->input('status'),
                'payment_status' => $request->input('payment_status'),
                'category_id' => $request->input('category_id'),
                'category_name' => $request->filled('category_id') && $request->category_id !== 'all' 
                    ? \App\Models\Category::find($request->category_id)?->name 
                    : null,
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
            ];

            // Format sales data for report
            $salesData = $sales->map(function($sale) {
                $paidAmount = (float) ($sale->paid_amount ?? 0);
                $balance = (float) ($sale->remaining_balance_due ?? max(0, ($sale->total_amount ?? 0) - $paidAmount));
                return [
                    'sale_number' => $sale->sale_number,
                    'sale_date' => $sale->created_at ? $sale->created_at->format('Y-m-d h:i A') : ($sale->sale_date ? $sale->sale_date->format('Y-m-d') : ''),
                    'sale_time' => '', // Combined with sale_date now
                    'customer_name' => $sale->customer ? $sale->customer->name : 'Walk-in Customer',
                    'total_amount' => $sale->total_amount ?? 0,
                    'paid_amount' => $paidAmount,
                    'remaining' => $balance,
                    'payment_status' => $sale->payment_status ?? 'pending',
                    'status' => $sale->status ?? 'pending',
                ];
            })->toArray();

            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'sales' => $salesData,
                    'filters' => $filters,
                    'totals' => [
                        'total_sales' => $totalSales,
                        'total_amount' => $totalAmount,
                        'total_paid' => $totalPaid,
                        'total_remaining' => $totalRemaining,
                    ]
                ]);
            }

            return view('sales.print-report', compact('salesData', 'filters', 'totalSales', 'totalAmount', 'totalPaid', 'totalRemaining'));
        } catch (\Exception $e) {
            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'error' => $e->getTraceAsString()
                ], 500);
            }
            throw $e;
        }
    }

    public function payment(Request $request)
    {
        try {
            $validated = $request->validate([
                'sale_id' => 'required|exists:sales,id',
                'amount' => 'required|numeric|min:0.01',
                'comment' => 'required|string|min:1|max:1000',
            ]);

            $sale = Sale::findOrFail($validated['sale_id']);
            $paymentAmount = $validated['amount'];
            
            $currentPaid = $sale->paid_amount ?? 0;
            $remainingBalance = $sale->total_amount - $currentPaid;
            
            // Calculate customer's total outstanding balance (including previous bills)
            $customerTotalOutstanding = $remainingBalance; // Default to current sale's remaining balance
            if ($sale->customer_id) {
                // Get all sales for this customer excluding ADJ bills
                $allSales = Sale::where('customer_id', $sale->customer_id)
                    ->where('sale_number', 'not like', 'ADJ-%')
                    ->get();

                Sale::attachCalculatedBalances($allSales);
                $customerTotalOutstanding = (float) $allSales->sum(
                    fn ($customerSale) => (float) ($customerSale->remaining_balance_due ?? 0)
                );
            }
            
            // Allow payment up to customer's total outstanding balance (not just current sale)
            // This enables paying for old bills together
            // Only validate if customer has outstanding balance
            if ($customerTotalOutstanding > 0 && $paymentAmount > $customerTotalOutstanding) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Payment amount exceeds total outstanding balance. Total outstanding balance: PKR ' . number_format($customerTotalOutstanding, 2)
                    ], 400);
                }
                return redirect()->back()->with('error', 'Payment amount exceeds total outstanding balance. Total outstanding balance: PKR ' . number_format($customerTotalOutstanding, 2));
            }
            
            // Calculate how much applies to current sale vs previous balance
            $paidAmountForSale = min($paymentAmount, $remainingBalance);
            
            // Extra payment that goes towards previous balance (if any)
            $extraPayment = max(0, $paymentAmount - $remainingBalance);
            
            $newPaidAmount = $currentPaid + $paidAmountForSale;
            $balance = $sale->total_amount - $newPaidAmount;
            
            // Determine payment status based on sale amount only
            $paymentStatus = 'paid';
            if ($balance > 0) {
                $paymentStatus = 'partial';
            }
            
            DB::beginTransaction();
            
            // Update current sale
            $sale->update([
                'paid_amount' => $newPaidAmount,
                'payment_status' => $paymentStatus,
            ]);
            
            // Log payment for current sale
            if ($sale->customer_id && $paidAmountForSale > 0) {
                \App\Models\CustomerPaymentLog::create([
                    'customer_id' => $sale->customer_id,
                    'user_id' => auth()->id(),
                    'log_type' => 'payment',
                    'sale_id' => $sale->id,
                    'reference_number' => $sale->sale_number,
                    'amount' => $paidAmountForSale,
                    'previous_amount' => $currentPaid,
                    'new_amount' => $newPaidAmount,
                    'payment_status' => $paymentStatus,
                    'description' => "Payment received for Sale: {$sale->sale_number}. Amount: PKR " . number_format($paidAmountForSale, 2),
                    'comment' => $validated['comment'] ?? null,
                ]);
            }
            
            // If customer paid extra (more than current sale amount), create adjustment record
            // This reduces previous balance by applying to pending sales
            if ($extraPayment > 0 && $sale->customer_id) {
                // Create ADJ bill record
                $adjSale = Sale::create([
                    'sale_number' => Sale::generateSaleNumber('ADJ'),
                    'customer_id' => $sale->customer_id,
                    'user_id' => auth()->id(),
                    'sale_date' => now(),
                    'subtotal' => 0,
                    'tax_amount' => 0,
                    'discount_amount' => 0,
                    'total_amount' => 0,
                    'paid_amount' => $extraPayment,
                    'payment_status' => 'paid',
                    'status' => 'completed',
                    'notes' => "Previous balance payment - Extra payment from Sale: {$sale->sale_number}",
                ]);

                // Get pending/partial sales for this customer (excluding ADJ bills and current sale)
                $pendingSales = Sale::where('customer_id', $sale->customer_id)
                    ->where('id', '!=', $sale->id)
                    ->where('id', '!=', $adjSale->id)
                    ->where(function($query) {
                        $query->where('payment_status', 'pending')
                              ->orWhere('payment_status', 'partial');
                    })
                    ->where('sale_number', 'not like', 'ADJ-%')
                    ->orderBy('sale_date', 'asc')
                    ->orderBy('id', 'asc')
                    ->get();

                // Apply extra payment to pending sales
                $remainingPayment = $extraPayment;
                foreach ($pendingSales as $pendingSale) {
                    if ($remainingPayment <= 0) {
                        break;
                    }

                    $currentPaidPending = $pendingSale->paid_amount ?? 0;
                    $remainingBalancePending = $pendingSale->total_amount - $currentPaidPending;
                    
                    if ($remainingBalancePending > 0) {
                        $paymentToApply = min($remainingPayment, $remainingBalancePending);
                        $newPaidAmountPending = $currentPaidPending + $paymentToApply;
                        
                        // Determine payment status
                        $newPaymentStatus = 'partial';
                        if ($newPaidAmountPending >= $pendingSale->total_amount) {
                            $newPaymentStatus = 'paid';
                        }
                        
                        // Update the sale
                        $pendingSale->update([
                            'paid_amount' => $newPaidAmountPending,
                            'payment_status' => $newPaymentStatus,
                        ]);
                        
                        // Log payment applied to old sale
                        \App\Models\CustomerPaymentLog::create([
                            'customer_id' => $sale->customer_id,
                            'user_id' => auth()->id(),
                            'log_type' => 'payment',
                            'sale_id' => $pendingSale->id,
                            'reference_number' => $pendingSale->sale_number,
                            'amount' => $paymentToApply,
                            'previous_amount' => $currentPaidPending,
                            'new_amount' => $newPaidAmountPending,
                            'payment_status' => $newPaymentStatus,
                            'description' => "Previous balance payment from Sale: {$sale->sale_number}. Applied PKR " . number_format($paymentToApply, 2) . " to Sale: {$pendingSale->sale_number}",
                            'comment' => $validated['comment'] ?? null,
                        ]);
                        
                        $remainingPayment -= $paymentToApply;
                    }
                }
            }
            
            DB::commit();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment processed successfully',
                    'paid_amount' => $newPaidAmount,
                    'balance' => $balance,
                    'payment_status' => $paymentStatus,
                    'extra_payment' => round($extraPayment, 2),
                    'previous_balance_payment' => round($extraPayment, 2)
                ]);
            }
            
            return redirect()->route('sales.index')->with('success', 'Payment processed successfully.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error processing payment: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()->with('error', 'Error processing payment.');
        }
    }
}