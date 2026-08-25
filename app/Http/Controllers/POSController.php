<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Unit;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\CustomerBalanceService;
use App\Services\UnitConversionService;
use App\Support\BranchRules;
use App\Support\CurrentBranch;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class POSController extends Controller
{
    protected function findVisibleProduct(int $productId): ?Product
    {
        $branchId = CurrentBranch::requireId();

        return Product::query()
            ->visibleToBranch($branchId)
            ->whereKey($productId)
            ->first();
    }

    public function index(Request $request)
    {
        $categoryId = $request->get('category_id', 'all');
        $search = $request->get('search', '');
        $branchId = CurrentBranch::id();

        if (! $branchId) {
            $products = collect();
            $categories = Category::where('is_active', true)->withCount('products')->get();
            $customers = collect();
            $customerTypesForPos = collect();
            $units = Unit::where('is_active', true)->get();
            $editOrderData = null;
            $editOrderId = $request->get('edit_order_id');

            return view('pos.index', compact(
                'products',
                'categories',
                'customers',
                'customerTypesForPos',
                'units',
                'categoryId',
                'search',
                'editOrderData',
                'editOrderId'
            ));
        }

        $query = Product::where('is_active', true)
            ->visibleToBranch($branchId)
            ->with(['category', 'unit', 'productUnits.unit', 'currentBranchStock']);

        // Category filter
        if ($categoryId !== 'all') {
            $query->where('category_id', $categoryId);
        }

        // Search filter
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%");
            });
        }

        $products = $query->get();
        $categories = Category::where('is_active', true)->withCount('products')->get();
        $customers = Customer::where('is_active', true)->get();
        $customerTypesForPos = Customer::where('is_active', true)
            ->whereNotNull('customer_type')
            ->where('customer_type', '!=', '')
            ->distinct()
            ->orderBy('customer_type')
            ->pluck('customer_type')
            ->values();
        $units = Unit::where('is_active', true)->get();
        
        // Check if editing an order first (needed to exclude from balance calculation)
        $editOrderId = $request->get('edit_order_id');
        $editOrder = null;
        if ($editOrderId) {
            $editOrder = Sale::with('items.product.unit', 'customer')->find($editOrderId);
            if (!$editOrder) {
                $editOrder = \App\Models\Order::with('items.product.unit', 'customer')->find($editOrderId);
            }
        }

        // Calculate balance for each customer - exclude ADJ bills as they are adjustment records
        // Also exclude the order being edited (if any) from the balance calculation
        foreach ($customers as $customer) {
            $excludeSaleId = ($editOrder && (int) $editOrder->customer_id === (int) $customer->id)
                ? (int) $editOrder->id
                : null;

            $balanceSummary = $this->calculateCustomerBalanceSummary($customer->id, $excludeSaleId);
            $customer->total_price = $balanceSummary['total_price'];
            $customer->paid_amount = $balanceSummary['paid_amount'];
            $customer->unpaid_amount = $balanceSummary['unpaid_amount'];
        }

        // Format edit order data for JavaScript
        $editOrderData = null;
        if ($editOrder) {
                // Format order data for JavaScript
                $editOrderData = [
                    'id' => $editOrder->id,
                    'sale_number' => $editOrder->sale_number ?? $editOrder->order_number ?? null,
                    'order_number' => $editOrder->order_number ?? $editOrder->sale_number ?? null,
                    'customer' => $editOrder->customer ? [
                        'id' => $editOrder->customer->id,
                        'name' => $editOrder->customer->name,
                        'customer_id' => $editOrder->customer->customer_id,
                        'customer_type' => $editOrder->customer->customer_type,
                    ] : null,
                    'items' => $editOrder->items->map(function($item) {
                        // Get unit_id from sale_item first, then fallback to product unit
                        $unitId = $item->unit_id ?? ($item->product ? ($item->product->base_unit_id ?? $item->product->unit_id) : null);
                        $unit = $unitId ? Unit::find($unitId) : ($item->product && $item->product->unit ? $item->product->unit : null);
                        
                        return [
                            'id' => $item->id,
                            'product_id' => $item->product_id,
                            'product_name' => $item->product_name ?? ($item->product->name ?? 'N/A'),
                            'name' => $item->product_name ?? ($item->product->name ?? 'N/A'),
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unit_price,
                            'selling_price' => $item->unit_price,
                            'discount' => $item->discount ?? 0,
                            'unit_id' => $unitId,
                            'unit_name' => $unit ? $unit->short_name : 'Pcs',
                            'unit_short_name' => $unit ? $unit->short_name : 'Pcs',
                        ];
                    })->values()->all(),
                ];
        }

        return view('pos.index', compact('products', 'categories', 'customers', 'customerTypesForPos', 'units', 'categoryId', 'search', 'editOrderData', 'editOrderId'));
    }

    public function process(Request $request)
    {
        try {
            // Custom validation rules - product_id is required for regular products, product_name for custom products
            $validated = $request->validate([
                'order_id' => ['nullable', BranchRules::exists('sales')],
                'customer_id' => ['nullable', BranchRules::exists('customers')],
                'customer_name' => 'nullable|string|max:255',
                'payment_method' => 'required|in:cash,card,other',
                'paid_amount' => 'nullable|numeric|min:0',
                'comment' => 'required|string|min:1|max:1000',
                'items' => 'required|array|min:1',
                'items.*.quantity' => 'required|numeric|min:0.01',
                'items.*.unit_id' => 'nullable|exists:units,id',
                'items.*.selling_price' => 'required|numeric|min:0',
                'items.*.discount_type' => 'nullable|in:percentage,fixed',
                'items.*.discount' => 'nullable|numeric|min:0',
            ]);
            
            // Validate each item - either product_id or product_name (for custom products)
            foreach ($request->input('items', []) as $index => $item) {
                $isCustom = isset($item['is_custom']) && $item['is_custom'] == '1';
                
                if ($isCustom) {
                    if (empty($item['product_name'])) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            "items.{$index}.product_name" => ['Product name is required for custom products.']
                        ]);
                    }
                } else {
                    if (empty($item['product_id'])) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            "items.{$index}.product_id" => ['Product ID is required for regular products.']
                        ]);
                    }
                    // Validate product exists
                    if (!$this->findVisibleProduct((int) $item['product_id'])) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            "items.{$index}.product_id" => ['Product not found.']
                        ]);
                    }
                }
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }
            throw $e;
        }

        // Calculate totals
        $subtotal = 0;
        $totalDiscount = 0;

        foreach ($request->input('items', []) as $item) {
            $isCustom = isset($item['is_custom']) && $item['is_custom'] == '1';
            
            if ($isCustom) {
                // Custom product - no stock check, no product lookup
                $productName = $item['product_name'] ?? 'Custom Product';
            } else {
                // Regular product
                $product = $this->findVisibleProduct((int) $item['product_id']);
                
                if (!$product) {
                    if ($request->expectsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => "Product not found with ID: {$item['product_id']}"
                        ], 404);
                    }
                    return redirect()->back()->with('error', "Product not found");
                }
                
                $unitId = $item['unit_id'] ?? $product->base_unit_id ?? $product->unit_id;
                try {
                    $requestedInBaseUnit = $this->resolveQuantityInBaseUnit($product, (float) $item['quantity'], $unitId ? (int) $unitId : null);
                } catch (\RuntimeException $e) {
                    if ($request->expectsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => $e->getMessage(),
                        ], 400);
                    }

                    return redirect()->back()->with('error', $e->getMessage());
                }
                $availableInBaseUnit = (float) ($product->stock_quantity ?? 0);

                if ($requestedInBaseUnit > $availableInBaseUnit + 0.000001) {
                    $baseUnitName = $product->baseUnit->short_name ?? $product->unit->short_name ?? 'base unit';

                    if ($request->expectsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => "Insufficient stock for {$product->name}. Available: {$availableInBaseUnit} {$baseUnitName}, Requested: {$requestedInBaseUnit} {$baseUnitName}"
                        ], 400);
                    }
                    return redirect()->back()->with('error', "Insufficient stock for {$product->name}");
                }
                
                $productName = $product->name;
            }
            
            $itemTotal = round($item['quantity'] * $item['selling_price'], 2);
            
            if (isset($item['discount']) && $item['discount'] > 0) {
                if ($item['discount_type'] === 'percentage') {
                    $discount = round($itemTotal * ($item['discount'] / 100), 2);
                } else {
                    $discount = round($item['discount'], 2);
                }
                $itemTotal = round($itemTotal - $discount, 2);
                $totalDiscount += $discount;
            }
            
            $subtotal += $itemTotal;
        }

        $taxAmount = 0; // Can be calculated if needed
        $totalAmount = round($subtotal + $taxAmount, 2);
        $subtotal = round($subtotal, 2);
        $totalDiscount = round($totalDiscount, 2);
        
        // Get paid amount from request, default to total amount if not provided
        $requestedPaidAmount = $request->input('paid_amount', $totalAmount);
        
        // Calculate previous balance before this sale (excluding ADJ bills)
        $previousBalance = 0;
        if (!empty($validated['customer_id'])) {
            $excludeCurrentOrderId = !empty($validated['order_id']) ? (int) $validated['order_id'] : null;
            $previousBalance = $this->calculateCustomerBalanceSummary((int) $validated['customer_id'], $excludeCurrentOrderId)['unpaid_amount'];
        }
        
        // When remaining balance > 0, customer is required (Walk-in not allowed)
        $grandTotal = round($totalAmount + $previousBalance, 2);
        $remainingBalance = round($grandTotal - $requestedPaidAmount, 2);
        if ($remainingBalance > 0 && empty($validated['customer_id'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'customer_id' => ['Please select a customer. Walk-in customer is only allowed when the order is fully paid (Remaining Balance = 0).']
            ]);
        }
        
        // Calculate how much applies to current sale vs previous balance
        // paid_amount for this sale cannot exceed totalAmount
        $paidAmountForSale = min($requestedPaidAmount, $totalAmount);
        
        // Extra payment that goes towards previous balance (if any)
        $extraPayment = max(0, $requestedPaidAmount - $totalAmount);
        
        // Determine payment status based on sale amount only
        $paymentStatus = 'paid';
        if ($paidAmountForSale < $totalAmount) {
            $paymentStatus = 'partial';
        } else if ($paidAmountForSale == 0) {
            $paymentStatus = 'pending';
        }
        
        // Use paidAmountForSale for the sale record
        $paidAmount = $paidAmountForSale;

        // Use database transaction
        try {
            DB::beginTransaction();

            // Get customer name, default to "Walk-in Customer" if not provided
            $customerName = !empty($validated['customer_name']) ? trim($validated['customer_name']) : 'Walk-in Customer';
            
            // Check if editing existing order
            $isEditing = !empty($validated['order_id']);
            $sale = null;
            
            if ($isEditing) {
                // Update existing sale/order
                $sale = Sale::with('items')->find($validated['order_id']);
                
                if (!$sale) {
                    throw new \Exception('Order not found for editing.');
                }
                
                // Restore stock for all existing items
                foreach ($sale->items as $oldItem) {
                    if ($oldItem->product_id && $oldItem->product) {
                        // Use quantity_in_base_unit if available, otherwise use quantity
                        $quantityToRestore = $oldItem->quantity_in_base_unit ?? $oldItem->quantity;
                        $oldItem->product->incrementStock($quantityToRestore, null, [
                            'source_type' => 'sale',
                            'source_id' => $sale->id,
                            'reason' => 'POS edit restore',
                        ]);
                    }
                }
                
                // Delete all existing items
                $sale->items()->delete();
                
                // Update sale
                $sale->update([
                    'customer_id' => $validated['customer_id'] ?? null,
                    'payment_method' => $validated['payment_method'] ?? 'cash',
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'discount_amount' => $totalDiscount,
                    'total_amount' => $totalAmount,
                    'paid_amount' => $paidAmount,
                    'payment_status' => $paymentStatus,
                    'status' => 'completed',
                    'notes' => "Customer: {$customerName}",
                ]);
            } else {
                // Create new sale
                $sale = Sale::create([
                    'sale_number' => Sale::generateSaleNumber('SALE', \App\Support\CurrentBranch::id()),
                    'customer_id' => $validated['customer_id'] ?? null,
                    'payment_method' => $validated['payment_method'] ?? 'cash',
                    'user_id' => auth()->id(),
                    'sale_date' => now(),
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'discount_amount' => $totalDiscount,
                    'total_amount' => $totalAmount,
                    'paid_amount' => $paidAmount,
                    'payment_status' => $paymentStatus,
                    'status' => 'completed',
                    'notes' => "Customer: {$customerName}",
                ]);
            }
            
            // Log payment for sale (if customer and payment amount > 0)
            if ($sale->customer_id && $paidAmount > 0) {
                \App\Models\CustomerPaymentLog::create([
                    'customer_id' => $sale->customer_id,
                    'user_id' => auth()->id(),
                    'log_type' => 'cash_received',
                    'sale_id' => $sale->id,
                    'reference_number' => $sale->sale_number,
                    'amount' => $paidAmount,
                    'previous_amount' => 0,
                    'new_amount' => $paidAmount,
                    'payment_status' => $paymentStatus,
                    'description' => "Cash received for Sale: {$sale->sale_number}. Amount: PKR " . number_format($paidAmount, 2),
                    'comment' => $validated['comment'] ?? null,
                ]);
            }

        // Create sale items
        $stockAlerts = [];
        $stockChangeTracker = []; // product_id => [name, oldQty, newQty]

        foreach ($request->input('items', []) as $item) {
            $isCustom = isset($item['is_custom']) && $item['is_custom'] == '1';
            
            $itemTotal = round($item['quantity'] * $item['selling_price'], 2);
            $discount = 0;

            if (isset($item['discount']) && $item['discount'] > 0) {
                if ($item['discount_type'] === 'percentage') {
                    $discount = round($itemTotal * ($item['discount'] / 100), 2);
                } else {
                    $discount = round($item['discount'], 2);
                }
                $itemTotal = round($itemTotal - $discount, 2);
            }

            if ($isCustom) {
                // Custom product - store with null product_id and product_name
                // Get unit_id from request
                $unitId = $item['unit_id'] ?? null;
                
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => null,
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'unit_id' => $unitId,
                    'quantity_in_base_unit' => $item['quantity'], // For custom products, quantity = quantity_in_base_unit
                    'unit_price' => $item['selling_price'],
                    'discount' => $discount,
                    'tax' => 0,
                    'total' => $itemTotal,
                ]);
                // No stock update for custom products
            } else {
                // Regular product
                $product = $this->findVisibleProduct((int) $item['product_id']);
                
                if (!$product) {
                    continue; // Skip if product not found (shouldn't happen after validation)
                }
                
                // Get unit_id from request or use product's base unit
                $unitId = $item['unit_id'] ?? $product->base_unit_id ?? $product->unit_id;
                
                $quantityInBaseUnit = $this->resolveQuantityInBaseUnit($product, (float) $item['quantity'], $unitId ? (int) $unitId : null);
                $currentQty = (float) $product->stock_quantity;
                if ($quantityInBaseUnit > ($currentQty + 0.000001)) {
                    throw new \Exception("Insufficient stock for {$product->name}. Available: {$product->stock_quantity}");
                }
                
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_id' => $unitId,
                    'quantity_in_base_unit' => $quantityInBaseUnit,
                    'unit_price' => $item['selling_price'],
                    'discount' => $discount,
                    'tax' => 0,
                    'total' => $itemTotal,
                ]);

                // Update product stock using base unit quantity
                $newQty = $product->decrementStock($quantityInBaseUnit, null, [
                    'source_type' => 'sale',
                    'source_id' => $sale->id,
                    'reason' => 'POS sale',
                ]);

                if (! isset($stockChangeTracker[$product->id])) {
                    $stockChangeTracker[$product->id] = [
                        'name' => $product->name,
                        'oldQty' => $currentQty,
                        'newQty' => $newQty,
                    ];
                } else {
                    $stockChangeTracker[$product->id]['newQty'] = $newQty;
                }
            }
        }

        foreach ($stockChangeTracker as $productId => $change) {
            $oldQty = (float) $change['oldQty'];
            $newQty = (float) $change['newQty'];
            $alertLevel = null;
            if ($oldQty > 0 && $newQty <= 0) {
                $alertLevel = 'out';
            } elseif ($oldQty > 5 && $newQty <= 5) {
                $alertLevel = 'low';
            }
            if ($alertLevel === null) {
                continue;
            }
            $stockAlerts[] = [
                'product_id' => (int) $productId,
                'name' => $change['name'],
                'remaining' => round(max(0, $newQty), 2),
                'level' => $alertLevel,
            ];
        }

        // If customer paid extra (more than current sale amount), create adjustment record
        // This reduces previous balance by applying to pending sales
        if ($extraPayment > 0 && !empty($validated['customer_id'])) {
            // Create ADJ bill record
            $adjSale = Sale::create([
                'sale_number' => Sale::generateSaleNumber('ADJ', \App\Support\CurrentBranch::id()),
                'customer_id' => $validated['customer_id'],
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
            $pendingSales = Sale::where('customer_id', $validated['customer_id'])
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

                $currentPaid = $pendingSale->paid_amount ?? 0;
                $remainingBalance = $pendingSale->total_amount - $currentPaid;
                
                if ($remainingBalance > 0) {
                    $paymentToApply = min($remainingPayment, $remainingBalance);
                    $newPaidAmount = $currentPaid + $paymentToApply;
                    
                    // Determine payment status
                    $newPaymentStatus = 'partial';
                    if ($newPaidAmount >= $pendingSale->total_amount) {
                        $newPaymentStatus = 'paid';
                    }
                    
                    // Update the sale
                    $pendingSale->update([
                        'paid_amount' => $newPaidAmount,
                        'payment_status' => $newPaymentStatus,
                    ]);
                    
                    // Log payment applied to old sale
                    \App\Models\CustomerPaymentLog::create([
                        'customer_id' => $validated['customer_id'],
                        'user_id' => auth()->id(),
                        'log_type' => 'payment',
                        'sale_id' => $pendingSale->id,
                        'reference_number' => $pendingSale->sale_number,
                        'amount' => $paymentToApply,
                        'previous_amount' => $currentPaid,
                        'new_amount' => $newPaidAmount,
                        'payment_status' => $newPaymentStatus,
                        'description' => "Previous balance payment from Sale: {$sale->sale_number}. Applied PKR " . number_format($paymentToApply, 2) . " to Sale: {$pendingSale->sale_number}",
                        'comment' => $validated['comment'] ?? null,
                    ]);
                    
                    $remainingPayment -= $paymentToApply;
                }
            }
        }

        DB::commit();

        // Recalculate previous balance with correct sale ID (excluding ADJ bills)
        $previousBalance = 0;
        if (!empty($validated['customer_id'])) {
            $previousBalance = $this->calculateCustomerBalanceSummary((int) $validated['customer_id'], (int) $sale->id)['unpaid_amount'];
        }

        $isEditing = !empty($validated['order_id']);

        if ($request->expectsJson()) {
            // For receipt: use actual amount paid and correct remaining balance from backend
            $totalPaidAmount = round($request->input('paid_amount', $totalAmount), 2);
            $receiptRemainingBalance = max(0, round($grandTotal - $totalPaidAmount, 2));

            return response()->json([
                'success' => true,
                'message' => $isEditing ? 'Order updated successfully.' : 'Sale processed successfully.',
                'sale_number' => $sale->sale_number,
                'sale_id' => $sale->id,
                'previous_balance' => round($previousBalance, 2),
                'previous_balance_payment' => round($extraPayment, 2),
                'paid_amount' => $totalPaidAmount,
                'remaining_balance' => $receiptRemainingBalance,
                'grand_total' => round($grandTotal, 2),
                'subtotal' => round($totalAmount, 2),
                'is_edit' => $isEditing,
                'stock_alerts' => $stockAlerts,
            ]);
        }

        if ($isEditing) {
            return redirect()->route('orders.completed')->with('success', 'Order updated successfully. Sale Number: ' . $sale->sale_number);
        }

        return redirect()->route('sales.pos.index')->with('success', 'Sale processed successfully. Sale Number: ' . $sale->sale_number);
        
        } catch (InsufficientStockException $e) {
            DB::rollBack();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 400);
            }

            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while processing the sale: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()->with('error', 'An error occurred while processing the sale.');
        }
    }

    public function hold(Request $request)
    {
        try {
            $validated = $request->validate([
                'customer_id' => ['nullable', BranchRules::exists('customers')],
                'customer_name' => 'nullable|string|max:255',
                'items' => 'required|array|min:1',
                // Allow nullable product_id here to support custom products; additional per-item checks below
                'items.*.product_id' => ['nullable', 'integer', BranchRules::existsVisibleProduct()],
                'items.*.quantity' => 'required|numeric|min:0.01',
                'items.*.unit_id' => 'nullable|exists:units,id',
                'items.*.selling_price' => 'required|numeric|min:0',
                'items.*.discount_type' => 'nullable|in:percentage,fixed',
                'items.*.discount' => 'nullable|numeric|min:0',
            ]);

            // Additional item-level validation to mirror process() custom product support
            foreach ($request->input('items', []) as $index => $item) {
                $isCustom = isset($item['is_custom']) && $item['is_custom'] == '1';

                if ($isCustom) {
                    if (empty($item['product_name'])) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            "items.{$index}.product_name" => ['Product name is required for custom products.']
                        ]);
                    }
                } else {
                    if (empty($item['product_id'])) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            "items.{$index}.product_id" => ['Product ID is required for regular products.']
                        ]);
                    }

                    if (!$this->findVisibleProduct((int) $item['product_id'])) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            "items.{$index}.product_id" => ['Product not found.']
                        ]);
                    }
                }
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }
            throw $e;
        }

        // Calculate totals
        $subtotal = 0;
        $totalDiscount = 0;

        foreach ($request->input('items', []) as $item) {
            $isCustom = isset($item['is_custom']) && $item['is_custom'] == '1';

            if (!$isCustom) {
                $product = $this->findVisibleProduct((int) $item['product_id']);

                if (!$product) {
                    if ($request->expectsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => "Product not found with ID: {$item['product_id']}"
                        ], 404);
                    }
                    return redirect()->back()->with('error', "Product not found");
                }
            }

            $itemTotal = round($item['quantity'] * $item['selling_price'], 2);
            
            if (isset($item['discount']) && $item['discount'] > 0) {
                if ($item['discount_type'] === 'percentage') {
                    $discount = round($itemTotal * ($item['discount'] / 100), 2);
                } else {
                    $discount = round($item['discount'], 2);
                }
                $itemTotal = round($itemTotal - $discount, 2);
                $totalDiscount += $discount;
            }
            
            $subtotal += $itemTotal;
        }

        $taxAmount = 0;
        $totalAmount = round($subtotal + $taxAmount, 2);
        $subtotal = round($subtotal, 2);
        $totalDiscount = round($totalDiscount, 2);

        // Use database transaction
        try {
            DB::beginTransaction();

            // Get customer name, default to "Walk-in Customer" if not provided
            $customerName = !empty($validated['customer_name']) ? trim($validated['customer_name']) : 'Walk-in Customer';
            
            // Create sale with on_hold status (don't update stock for held orders)
            // Try 'on_hold' first, fallback to 'draft' if enum doesn't support it yet
            $status = 'on_hold';
            try {
                // Try to create with on_hold status
                $sale = Sale::create([
                    'sale_number' => Sale::generateSaleNumber('HOLD', \App\Support\CurrentBranch::id()),
                    'customer_id' => $validated['customer_id'] ?? null,
                    'user_id' => auth()->id(),
                    'sale_date' => now(),
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'discount_amount' => $totalDiscount,
                    'total_amount' => $totalAmount,
                    'payment_status' => 'pending',
                    'status' => 'on_hold',
                    'notes' => "Customer: {$customerName} (Held Order)",
                ]);
            } catch (\Exception $e) {
                // If on_hold fails (enum doesn't support it), use 'draft' as fallback
                $status = 'draft';
                $sale = Sale::create([
                    'sale_number' => Sale::generateSaleNumber('HOLD', \App\Support\CurrentBranch::id()),
                    'customer_id' => $validated['customer_id'] ?? null,
                    'user_id' => auth()->id(),
                    'sale_date' => now(),
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'discount_amount' => $totalDiscount,
                    'total_amount' => $totalAmount,
                    'payment_status' => 'pending',
                    'status' => 'draft',
                    'notes' => "Customer: {$customerName} (Held Order)",
                ]);
            }

            // Create sale items (but don't update stock)
            foreach ($request->input('items', []) as $item) {
                $isCustom = isset($item['is_custom']) && $item['is_custom'] == '1';

                $itemTotal = $item['quantity'] * $item['selling_price'];
                $discount = 0;

                if (isset($item['discount']) && $item['discount'] > 0) {
                    if ($item['discount_type'] === 'percentage') {
                        $discount = $itemTotal * ($item['discount'] / 100);
                    } else {
                        $discount = $item['discount'];
                    }
                    $itemTotal -= $discount;
                }

                if ($isCustom) {
                    // Custom product - store with null product_id and product_name
                    // Get unit_id from request
                    $unitId = $item['unit_id'] ?? null;
                    
                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => null,
                        'product_name' => $item['product_name'] ?? 'Custom Product',
                        'quantity' => $item['quantity'],
                        'unit_id' => $unitId,
                        'quantity_in_base_unit' => $item['quantity'], // For custom products, quantity = quantity_in_base_unit
                        'unit_price' => $item['selling_price'],
                        'discount' => $discount,
                        'tax' => 0,
                        'total' => $itemTotal,
                    ]);
                } else {
                    $product = $this->findVisibleProduct((int) $item['product_id']);

                    if (!$product) {
                        continue;
                    }

                    // Get unit_id from request or use product's base unit
                    $unitId = $item['unit_id'] ?? $product->base_unit_id ?? $product->unit_id;
                    
                    // Calculate quantity in base unit
                    $quantityInBaseUnit = $this->resolveQuantityInBaseUnit($product, (float) $item['quantity'], $unitId ? (int) $unitId : null);
                    
                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_id' => $unitId,
                        'quantity_in_base_unit' => $quantityInBaseUnit,
                        'unit_price' => $item['selling_price'],
                        'discount' => $discount,
                        'tax' => 0,
                        'total' => $itemTotal,
                    ]);
                }

                // Don't update stock for held orders
            }

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Order held successfully.',
                    'sale_number' => $sale->sale_number,
                    'sale_id' => $sale->id
                ]);
            }

            return redirect()->route('sales.pos.index')->with('success', 'Order held successfully. Sale Number: ' . $sale->sale_number);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while holding the order: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()->with('error', 'An error occurred while holding the order.');
        }
    }

    public function getHoldOrders(Request $request)
    {
        // Get orders with status = 'on_hold' or 'draft' with "Held Order" in notes
        $holdOrders = Sale::where(function($q) {
                $q->where('status', 'on_hold')
                  ->orWhere(function($subQ) {
                      $subQ->where('status', 'draft')
                           ->where('notes', 'like', '%Held Order%');
                  });
            })
            ->with(['customer', 'items.product.unit'])
            ->latest()
            ->get()
            ->map(function($sale) {
                return [
                    'id' => $sale->id,
                    'sale_number' => $sale->sale_number,
                    'customer_name' => $sale->customer->name ?? 'Walk-in Customer',
                    'customer_id' => $sale->customer_id,
                    'total_amount' => $sale->total_amount,
                    'item_count' => $sale->items->count(),
                    'created_at' => $sale->created_at->format('Y-m-d h:i A'),
                    'items' => $sale->items->map(function($item) {
                        $product = $item->product;
                        $isCustom = is_null($item->product_id) && !empty($item->product_name);
                        $productName = $isCustom ? $item->product_name : ($product->name ?? 'Unknown');
                        return [
                            'product_id' => $item->product_id,
                            'product_name' => $productName,
                            'is_custom' => $isCustom,
                            'quantity' => $item->quantity,
                            'unit_id' => $item->unit_id ?? $product->unit_id ?? null,
                            'unit_name' => ($item->unit_id && ($unit = Unit::find($item->unit_id))) ? $unit->short_name : ($product->unit->short_name ?? 'Pcs'),
                            'selling_price' => $item->unit_price,
                            'discount' => $item->discount,
                            'discount_type' => $item->discount > 0 ? ($item->discount / ($item->quantity * $item->unit_price) * 100 > 50 ? 'fixed' : 'percentage') : 'percentage',
                            'stock_quantity' => $product->stock_quantity ?? 0,
                            'selling_type' => $product->selling_type ?? 'retail',
                            'retail_price' => $product->retail_price ?? $product->selling_price ?? 0,
                            'wholesale_price' => $product->wholesale_price ?? $product->selling_price ?? 0,
                        ];
                    })
                ];
            });

        return response()->json([
            'success' => true,
            'hold_orders' => $holdOrders
        ]);
    }

    public function loadHoldOrder($id)
    {
        $sale = Sale::with(['items.product.unit', 'items.unit', 'customer'])
            ->where(function($q) use ($id) {
                $q->where('id', $id)
                  ->where(function($subQ) {
                      $subQ->where('status', 'on_hold')
                           ->orWhere(function($q2) {
                               $q2->where('status', 'draft')
                                  ->where('notes', 'like', '%Held Order%');
                           });
                  });
            })
            ->first();

        if (!$sale) {
            return response()->json([
                'success' => false,
                'message' => 'Hold order not found'
            ], 404);
        }

        $items = $sale->items->map(function($item) {
            $product = $item->product;
            $isCustom = is_null($item->product_id) && !empty($item->product_name);
            
            // Calculate discount type and value
            $discountType = 'percentage';
            $discountValue = 0;
            
            if ($item->discount > 0) {
                $itemTotal = $item->quantity * $item->unit_price;
                $discountPercent = ($item->discount / $itemTotal) * 100;
                
                // If discount is more than 50% of total, likely it's a fixed amount
                if ($discountPercent > 50) {
                    $discountType = 'fixed';
                    $discountValue = $item->discount;
                } else {
                    $discountType = 'percentage';
                    $discountValue = $discountPercent;
                }
            }

            if ($isCustom) {
                // Custom product - use stored product_name and unit_id
                $unitId = $item->unit_id ?? null;
                $unit = $unitId ? Unit::find($unitId) : null;
                
                return [
                    'product_id' => null,
                    'name' => $item->product_name ?? 'Custom Product',
                    'is_custom' => true,
                    'quantity' => $item->quantity,
                    'unit_id' => $unitId,
                    'unit_name' => $unit ? $unit->short_name : 'Pcs',
                    'selling_price' => $item->unit_price,
                    'purchase_price' => 0,
                    'retail_price' => $item->unit_price,
                    'wholesale_price' => $item->unit_price,
                    'selling_type' => 'retail',
                    'price_type' => 'retail',
                    'discount_type' => $discountType,
                    'discount' => $discountValue,
                    'stock_quantity' => 0,
                ];
            }

            // Get unit_id and unit_name
            $unitId = $item->unit_id ?? $product->base_unit_id ?? $product->unit_id ?? null;
            $unitName = 'Pcs';
            
            if ($unitId) {
                // Try to get unit from item relationship first
                if ($item->unit) {
                    $unitName = $item->unit->short_name;
                } elseif ($product && $product->unit && $product->unit->id == $unitId) {
                    $unitName = $product->unit->short_name;
                } else {
                    // Load unit if not loaded
                    $unit = Unit::find($unitId);
                    if ($unit) {
                        $unitName = $unit->short_name;
                    }
                }
            } elseif ($product && $product->unit) {
                $unitName = $product->unit->short_name;
            }
            
            return [
                'product_id' => $item->product_id,
                'name' => $product->name ?? 'Unknown',
                'quantity' => $item->quantity,
                'unit_id' => $unitId,
                'unit_name' => $unitName,
                'selling_price' => $item->unit_price,
                'purchase_price' => $product->purchase_price ?? 0,
                'retail_price' => $product->retail_price ?? $product->selling_price ?? 0,
                'wholesale_price' => $product->wholesale_price ?? $product->selling_price ?? 0,
                'selling_type' => $product->selling_type ?? 'retail',
                'price_type' => $product->selling_type === 'both' ? 'retail' : ($product->selling_type ?? 'retail'),
                'discount_type' => $discountType,
                'discount' => $discountValue,
                'stock_quantity' => $product->stock_quantity ?? 0,
            ];
        });

        return response()->json([
            'success' => true,
            'sale_number' => $sale->sale_number,
            'customer_id' => $sale->customer_id,
            'customer_name' => $sale->customer->name ?? 'Walk-in Customer',
            'customer_type' => $sale->customer?->customer_type ?? '',
            'items' => $items
        ]);
    }

    public function deleteHoldOrder($id)
    {
        try {
            $sale = Sale::where(function($q) use ($id) {
                $q->where('id', $id)
                  ->where(function($subQ) {
                      $subQ->where('status', 'on_hold')
                           ->orWhere(function($q2) {
                               $q2->where('status', 'draft')
                                  ->where('notes', 'like', '%Held Order%');
                           });
                  });
            })
            ->first();

            if (!$sale) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hold order not found'
                ], 404);
            }

            // Delete the sale (cascade will delete sale items)
            $sale->delete();

            return response()->json([
                'success' => true,
                'message' => 'Hold order deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting hold order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Last sold price of a product for a customer (current branch via Sale global scope).
     */
    public function getLastProductPrice(Request $request, $customerId, $productId)
    {
        try {
            $unitId = $request->integer('unit_id') ?: null;
            $branchId = CurrentBranch::requireId();

            $baseQuery = function () use ($customerId, $productId, $branchId) {
                return SaleItem::query()
                    ->select('sale_items.*')
                    ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                    ->where('sales.branch_id', $branchId)
                    ->where('sales.customer_id', $customerId)
                    ->where('sale_items.product_id', $productId)
                    ->where('sales.status', 'completed')
                    ->where('sales.sale_number', 'not like', 'ADJ-%')
                    ->with(['sale', 'unit']);
            };

            $query = $baseQuery();
            if ($unitId) {
                $query->where('sale_items.unit_id', $unitId);
            }

            $item = $query
                ->orderByDesc('sales.sale_date')
                ->orderByDesc('sales.id')
                ->first();

            // Fallback: any unit if exact unit has no history
            if (!$item && $unitId) {
                $item = $baseQuery()
                    ->orderByDesc('sales.sale_date')
                    ->orderByDesc('sales.id')
                    ->first();
            }

            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'No previous price found for this customer and product.',
                ], 404);
            }

            $sale = $item->sale;
            $unitName = $item->unit?->short_name
                ?? ($item->unit_id ? (Unit::find($item->unit_id)?->short_name) : null)
                ?? 'Pcs';

            return response()->json([
                'success' => true,
                'unit_price' => (float) $item->unit_price,
                'quantity' => (float) $item->quantity,
                'unit_id' => $item->unit_id,
                'unit_name' => $unitName,
                'sale_number' => $sale?->sale_number,
                'sale_date' => $sale?->sale_date?->format('d M Y'),
                'matched_unit' => $unitId ? ((int) $item->unit_id === (int) $unitId) : true,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching last price: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getLastOrderItems($customerId)
    {
        try {
            // Get the last completed sale for this customer (excluding ADJ bills and held orders)
            $lastSale = Sale::where('customer_id', $customerId)
                ->where('status', 'completed')
                ->where('sale_number', 'not like', 'ADJ-%')
                ->with(['items.product.unit', 'items.unit'])
                ->latest('sale_date')
                ->latest('id')
                ->first();

            if (!$lastSale || $lastSale->items->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No previous order found for this customer.'
                ], 404);
            }

            // Format items for frontend
            $items = $lastSale->items->map(function($item) {
                $product = $item->product;
                $isCustom = is_null($item->product_id) && !empty($item->product_name);
                
                // Get unit information
                $unitId = $item->unit_id ?? ($product ? ($product->base_unit_id ?? $product->unit_id) : null);
                $unit = $unitId ? Unit::find($unitId) : ($item->unit ?? ($product && $product->unit ? $product->unit : null));
                $unitName = $unit ? $unit->short_name : 'Pcs';

                if ($isCustom) {
                    // Custom product
                    return [
                        'product_id' => null,
                        'name' => $item->product_name ?? 'Custom Product',
                        'is_custom' => true,
                        'quantity' => (float)$item->quantity,
                        'unit_id' => $unitId,
                        'unit_name' => $unitName,
                        'selling_price' => (float)$item->unit_price,
                        'retail_price' => (float)$item->unit_price,
                        'wholesale_price' => (float)$item->unit_price,
                        'selling_type' => 'retail',
                        'price_type' => 'retail',
                        'discount_type' => $item->discount > 0 ? 'fixed' : 'percentage',
                        'discount' => (float)$item->discount,
                        'stock_quantity' => 999999, // Unlimited for custom products
                    ];
                }

                // Regular product
                return [
                    'product_id' => $item->product_id,
                    'name' => $product->name ?? 'Unknown',
                    'is_custom' => false,
                    'quantity' => (float)$item->quantity,
                    'unit_id' => $unitId,
                    'unit_name' => $unitName,
                    'selling_price' => (float)$item->unit_price,
                    'retail_price' => (float)($product->retail_price ?? $product->selling_price ?? 0),
                    'wholesale_price' => (float)($product->wholesale_price ?? $product->selling_price ?? 0),
                    'selling_type' => $product->selling_type ?? 'retail',
                    'price_type' => $product->selling_type === 'both' ? 'retail' : ($product->selling_type ?? 'retail'),
                    'discount_type' => $item->discount > 0 ? 'fixed' : 'percentage',
                    'discount' => (float)$item->discount,
                    'stock_quantity' => (float)($product->stock_quantity ?? 0),
                    'purchase_price' => (float)($product->purchase_price ?? 0),
                    'base_unit_id' => $product->base_unit_id ?? $product->unit_id,
                ];
            });

            return response()->json([
                'success' => true,
                'sale_number' => $lastSale->sale_number,
                'sale_date' => $lastSale->sale_date->format('Y-m-d'),
                'items' => $items
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching last order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Convert requested quantity to product base unit.
     * We intentionally do not fallback to raw quantity when conversion is missing,
     * because that leads to incorrect stock decrement for cross-unit sales.
     */
    protected function resolveQuantityInBaseUnit(Product $product, float $quantity, ?int $unitId = null): float
    {
        return app(UnitConversionService::class)->toBaseQuantity($product, $quantity, $unitId);
    }

    /**
     * Keep POS customer previous balance aligned with customer detail/list logic.
     * Excludes ADJ invoices from totals, applies ADJ payments to parent sales, and caps paid at cumulative total.
     */
    protected function calculateCustomerBalanceSummary(int $customerId, ?int $excludeSaleId = null): array
    {
        return app(CustomerBalanceService::class)->calculateCustomerBalanceSummary($customerId, $excludeSaleId);
    }
}
