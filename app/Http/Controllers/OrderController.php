<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        // Use Sale model since POS creates Sales, not Orders
        $query = Sale::with('customer', 'user', 'items.product');
        
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

        // Brand filter (filter by products in order items)
        if ($request->filled('brand') && $request->brand !== 'all') {
            $query->whereHas('items', function($itemQuery) use ($request) {
                $itemQuery->whereHas('product', function($productQuery) use ($request) {
                    $productQuery->where('brand', $request->brand);
                });
            });
        }

        // Category filter (filter by products in order items)
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

        $orders = $query->latest()
            ->paginate($request->get('per_page', 15))
            ->appends($request->query());
        Sale::attachCalculatedBalances($orders->getCollection());

        // Get brands and categories for filters
        $brands = \App\Models\Product::whereNotNull('brand')
            ->where('brand', '!=', '')
            ->distinct()
            ->pluck('brand')
            ->sort()
            ->values();

        $categories = \App\Models\Category::where('is_active', true)->orderBy('name')->get();

        return view('orders.index', compact('orders', 'brands', 'categories'));
    }

    public function completed(Request $request)
    {
        $query = Sale::where('status', 'completed')
            ->with('customer', 'user', 'items.product');
        
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
                // `sales` table only has `sale_number`, not `order_number`
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

        // Brand filter (filter by products in order items)
        // Only apply if brand filter is selected and not 'all'
        if ($request->filled('brand') && $request->brand !== 'all') {
            $query->whereHas('items', function($itemQuery) use ($request) {
                $itemQuery->whereHas('product', function($productQuery) use ($request) {
                    $productQuery->where('brand', $request->brand);
                });
            });
        }

        // Category filter (filter by products in order items)
        // Only apply if category filter is selected and not 'all'
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

        $orders = $query->latest()
            ->paginate($request->get('per_page', 10))
            ->appends($request->query());
        Sale::attachCalculatedBalances($orders->getCollection());

        // Get brands and categories for filters
        $brands = \App\Models\Product::whereNotNull('brand')
            ->where('brand', '!=', '')
            ->distinct()
            ->pluck('brand')
            ->sort()
            ->values();

        $categories = \App\Models\Category::where('is_active', true)->orderBy('name')->get();

        return view('orders.completed', compact('orders', 'brands', 'categories'));
    }

    public function pending(Request $request)
    {
        // Get sales with pending or partial payment status
        $query = Sale::whereIn('payment_status', ['pending', 'partial'])
            ->where('status', 'completed')
            ->with('customer', 'user', 'items');
        
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
                // `sales` table only has `sale_number`, not `order_number`
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

        // Brand filter (filter by products in order items)
        if ($request->filled('brand') && $request->brand !== 'all') {
            $query->whereHas('items.product', function($productQuery) use ($request) {
                $productQuery->where('brand', $request->brand);
            });
        }

        // Category filter (filter by products in order items)
        if ($request->filled('category_id') && $request->category_id !== 'all') {
            $query->whereHas('items.product', function($productQuery) use ($request) {
                $productQuery->where('category_id', $request->category_id);
            });
        }

        // Date range filter
        if ($request->filled('start_date')) {
            $query->whereDate('sale_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('sale_date', '<=', $request->end_date);
        }

        $orders = $query->latest()
            ->paginate($request->get('per_page', 10))
            ->appends($request->query());
        Sale::attachCalculatedBalances($orders->getCollection());

        // Get brands and categories for filters
        $brands = \App\Models\Product::whereNotNull('brand')
            ->where('brand', '!=', '')
            ->distinct()
            ->pluck('brand')
            ->sort()
            ->values();

        $categories = \App\Models\Category::where('is_active', true)->orderBy('name')->get();

        return view('orders.pending', compact('orders', 'brands', 'categories'));
    }

    public function onHold(Request $request)
    {
        // Get orders with status = 'on_hold' or 'draft' with "Held Order" in notes (fallback)
        $query = Sale::where(function($q) {
                $q->where('status', 'on_hold')
                  ->orWhere(function($subQ) {
                      $subQ->where('status', 'draft')
                           ->where('notes', 'like', '%Held Order%');
                  });
            })
            ->with('customer', 'user', 'items');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                // `sales` table only has `sale_number`, not `order_number`
                $q->where('sale_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($customerQuery) use ($search) {
                      $customerQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Brand filter (filter by products in order items)
        if ($request->filled('brand') && $request->brand !== 'all') {
            $query->whereHas('items.product', function($productQuery) use ($request) {
                $productQuery->where('brand', $request->brand);
            });
        }

        // Category filter (filter by products in order items)
        if ($request->filled('category_id') && $request->category_id !== 'all') {
            $query->whereHas('items.product', function($productQuery) use ($request) {
                $productQuery->where('category_id', $request->category_id);
            });
        }

        // Date range filter
        if ($request->filled('start_date')) {
            $query->whereDate('sale_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('sale_date', '<=', $request->end_date);
        }

        $orders = $query->latest()
            ->paginate($request->get('per_page', 10))
            ->appends($request->query());

        // Get brands and categories for filters
        $brands = \App\Models\Product::whereNotNull('brand')
            ->where('brand', '!=', '')
            ->distinct()
            ->pluck('brand')
            ->sort()
            ->values();

        $categories = \App\Models\Category::where('is_active', true)->orderBy('name')->get();

        return view('orders.on-hold', compact('orders', 'brands', 'categories'));
    }

    public function create()
    {
        return view('orders.create');
    }

    public function store(Request $request)
    {
        // Implementation
        return redirect()->route('orders.index')->with('success', 'Order created successfully.');
    }

    public function show(Request $request, $id)
    {
        // Try to find as Sale first, then Order
        // Load unit relationship on sale_items as well
        $order = Sale::with(['customer', 'user', 'items.product.unit', 'items.unit'])->find($id);
        if (!$order) {
            $order = Order::with(['customer', 'user', 'items.product.unit', 'items.unit'])->find($id);
        }
        
        if (!$order) {
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json(['error' => 'Order not found.'], 404);
            }
            return redirect()->route('orders.index')->with('error', 'Order not found.');
        }
        
        // If JSON request, return JSON response
        if ($request->expectsJson() || $request->wantsJson()) {
            // Ensure product->unit and item->unit relationships are loaded for all items
            $order->load(['items.product.unit', 'items.unit']);
            
            // Get all units for lookup (same as POS)
            $units = \App\Models\Unit::where('is_active', true)->get(['id', 'short_name'])->map(function($unit) {
                return [
                    'id' => $unit->id,
                    'short_name' => $unit->short_name
                ];
            })->toArray();
            
            // Create a units lookup map for faster access
            $unitsMap = [];
            foreach ($units as $unit) {
                $unitsMap[$unit['id']] = $unit['short_name'];
            }
            
            // Use centralized balance logic everywhere.
            $previousBalance = 0.0;
            $previousBalancePayment = 0.0;
            $adjPaidAmount = 0.0;
            $adjBillNumber = null;
            $regularPaidAmount = (float) ($order->paid_amount ?? 0);

            if ($order instanceof \App\Models\Sale && $order->customer_id) {
                Sale::attachCalculatedBalances(collect([$order]));

                $previousBalance = (float) ($order->previous_balance ?? 0);
                $adjPaidAmount = (float) ($order->adj_paid_amount ?? 0);
                $adjBillNumber = $order->adj_bill_number ?? null;
                $regularPaidAmount = max(
                    0,
                    (float) ($order->db_paid_amount ?? (($order->paid_amount ?? 0) - $adjPaidAmount))
                );
                $previousBalancePayment = $adjPaidAmount;
            }
            
            return response()->json([
                'order' => [
                    'id' => $order->id,
                    'sale_number' => $order->sale_number ?? null,
                    'order_number' => $order->order_number ?? null,
                    'sale_date' => $order->sale_date ? $order->sale_date->format('Y-m-d') : null,
                    'order_date' => $order->order_date ? $order->order_date->format('Y-m-d') : null,
                    'created_at' => $order->created_at ? $order->created_at->format('Y-m-d h:i A') : null,
                    'total_amount' => $order->total_amount ?? 0,
                    'subtotal' => $order->subtotal ?? 0,
                    'tax_amount' => $order->tax_amount ?? 0,
                    'discount_amount' => $order->discount_amount ?? 0,
                    'paid_amount' => $order->paid_amount ?? 0,
                    'regular_paid_amount' => $regularPaidAmount,
                    'adj_paid_amount' => $adjPaidAmount,
                    'adj_bill_number' => $adjBillNumber,
                    'payment_method' => $order->payment_method ?? 'cash',
                    'payment_status' => $order->payment_status ?? 'pending',
                    'status' => $order->status ?? 'draft',
                    'notes' => $order->notes ?? null,
                    'customer' => $order->customer ? [
                        'id' => $order->customer->id,
                        'name' => $order->customer->name
                    ] : null,
                    'previous_balance' => round($previousBalance, 2),
                    'previous_balance_payment' => round($previousBalancePayment, 2),
                    'units' => $units,
                    'items' => $order->items->map(function($item) use ($unitsMap) {
                        // Prioritize unit_id from sale_item, then fallback to product unit
                        $unitId = $item->unit_id ?? ($item->product ? ($item->product->base_unit_id ?? $item->product->unit_id) : null);
                        $unitShortName = 'Pcs'; // Default
                        
                        if ($unitId) {
                            // Try to get unit name from units map
                            if (isset($unitsMap[$unitId])) {
                                $unitShortName = $unitsMap[$unitId];
                            } elseif ($item->product && $item->product->unit && $item->product->unit->id == $unitId) {
                                // Fallback to loaded product unit relationship
                                $unitShortName = $item->product->unit->short_name;
                            }
                        } elseif ($item->product && $item->product->unit) {
                            // Final fallback to product unit
                            $unitId = $item->product->unit_id;
                            $unitShortName = $item->product->unit->short_name;
                        }
                        
                        return [
                            'id' => $item->id,
                            'product_id' => $item->product_id,
                            // Prefer stored product_name (for custom products or deleted products),
                            // fallback to related product name if available
                            'product_name' => $item->product_name ?? ($item->product->name ?? null),
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unit_price,
                            'discount' => $item->discount ?? 0,
                            'total' => $item->total ?? 0,
                            'unit_id' => $unitId,
                            'product' => $item->product ? [
                                'id' => $item->product->id,
                                'name' => $item->product->name,
                                'unit_id' => $item->product->unit_id,
                                'base_unit_id' => $item->product->base_unit_id ?? $item->product->unit_id,
                                'unit' => $item->product->unit ? [
                                    'id' => $item->product->unit->id,
                                    'short_name' => $item->product->unit->short_name
                                ] : null
                            ] : null,
                            'unit_name' => $unitShortName,
                            'unit_short_name' => $unitShortName
                        ];
                    })
                ]
            ]);
        }
        
        return view('orders.show', compact('order'));
    }

    public function edit($id)
    {
        // Try to find as Sale first, then Order
        $order = Sale::with('items.product.unit', 'customer')->find($id);
        if (!$order) {
            $order = Order::with('items.product.unit', 'customer')->find($id);
        }
        
        if (!$order) {
            return redirect()->route('orders.index')->with('error', 'Order not found.');
        }
        
        // Redirect to POS page with order ID to load items there
        return redirect()->route('sales.pos.index', ['edit_order_id' => $order->id]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:sale_items,id',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.product_name' => 'nullable|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.tax' => 'nullable|numeric|min:0',
            'status' => 'nullable|string',
            'payment_status' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Try to find as Sale first, then Order
            $order = Sale::with('items')->find($id);
            $isSale = true;
            if (!$order) {
                $order = Order::with('items')->find($id);
                $isSale = false;
            }
            
            if (!$order) {
                return redirect()->route('orders.index')->with('error', 'Order not found.');
            }

            // Get existing item IDs
            $existingItemIds = $order->items->pluck('id')->toArray();
            $submittedItemIds = array_filter(array_column($validated['items'], 'id'));

            // Delete removed items (for returns)
            $itemsToDelete = array_diff($existingItemIds, $submittedItemIds);
            if (!empty($itemsToDelete)) {
                foreach ($itemsToDelete as $itemId) {
                    $item = \App\Models\SaleItem::find($itemId);
                    if ($item) {
                        // Restore stock if product exists
                        if ($item->product_id && $item->product) {
                            $item->product->incrementStock( $item->quantity);
                        }
                        $item->delete();
                    }
                }
            }

            // Calculate new totals
            $subtotal = 0;
            $totalDiscount = 0;
            $totalTax = 0;

            // Update or create items
            foreach ($validated['items'] as $itemData) {
                $itemTotal = round(round($itemData['quantity'] * $itemData['unit_price'], 2) - round($itemData['discount'] ?? 0, 2) + round($itemData['tax'] ?? 0, 2), 2);
                
                if (isset($itemData['id']) && $itemData['id']) {
                    // Update existing item
                    $item = \App\Models\SaleItem::find($itemData['id']);
                    if ($item) {
                        $oldQuantity = $item->quantity;
                        $oldProductId = $item->product_id;
                        
                        $item->update([
                            'product_id' => $itemData['product_id'] ?? null,
                            'product_name' => $itemData['product_name'] ?? null,
                            'quantity' => $itemData['quantity'],
                            'unit_price' => $itemData['unit_price'],
                            'discount' => $itemData['discount'] ?? 0,
                            'tax' => $itemData['tax'] ?? 0,
                            'total' => $itemTotal,
                        ]);

                        // Adjust stock for quantity / product change (diff only — do not restore+diff)
                        if ($oldProductId && (int) $oldProductId === (int) ($itemData['product_id'] ?? 0)) {
                            $quantityDiff = (float) $oldQuantity - (float) $itemData['quantity'];
                            if ($quantityDiff > 0.000001) {
                                $item->product?->incrementStock($quantityDiff);
                            } elseif ($quantityDiff < -0.000001) {
                                $item->product?->decrementStock(abs($quantityDiff));
                            }
                        } else {
                            if ($oldProductId) {
                                $oldProduct = \App\Models\Product::find($oldProductId);
                                $oldProduct?->incrementStock((float) $oldQuantity);
                            }
                            if (! empty($itemData['product_id'])) {
                                $newProduct = \App\Models\Product::find($itemData['product_id']);
                                $newProduct?->decrementStock((float) $itemData['quantity']);
                            }
                        }
                    }
                } else {
                    // Create new item
                    \App\Models\SaleItem::create([
                        'sale_id' => $order->id,
                        'product_id' => $itemData['product_id'] ?? null,
                        'product_name' => $itemData['product_name'] ?? null,
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $itemData['unit_price'],
                        'discount' => $itemData['discount'] ?? 0,
                        'tax' => $itemData['tax'] ?? 0,
                        'total' => $itemTotal,
                    ]);

                    // Decrease stock if product exists
                    if ($itemData['product_id']) {
                        $product = \App\Models\Product::find($itemData['product_id']);
                        if ($product) {
                            $product->decrementStock( $itemData['quantity']);
                        }
                    }
                }

                $subtotal += $itemTotal;
                $totalDiscount += ($itemData['discount'] ?? 0);
                $totalTax += ($itemData['tax'] ?? 0);
            }

            $totalAmount = round($subtotal + $totalTax, 2);
            $subtotal = round($subtotal, 2);
            $totalDiscount = round($totalDiscount, 2);
            $totalTax = round($totalTax, 2);

            // Update order/sale
            $order->update([
                'subtotal' => $subtotal,
                'discount_amount' => $totalDiscount,
                'tax_amount' => $totalTax,
                'total_amount' => $totalAmount,
                'status' => $validated['status'] ?? $order->status,
                'payment_status' => $validated['payment_status'] ?? $order->payment_status,
                'notes' => $validated['notes'] ?? $order->notes,
            ]);

            DB::commit();

            return redirect()->route('orders.show', $order)->with('success', 'Order updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error updating order: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy($id)
    {
        // Try to find as Sale first, then Order
        $order = Sale::find($id);
        if (!$order) {
            $order = Order::find($id);
        }
        
        if (!$order) {
            return redirect()->route('orders.index')->with('error', 'Order not found.');
        }
        
        $order->delete();
        return redirect()->route('orders.index')->with('success', 'Order deleted successfully.');
    }
}
