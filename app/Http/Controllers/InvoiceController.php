<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::with('customer', 'sale');
        
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($customerQuery) use ($search) {
                      $customerQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }
        
        $invoices = $query->latest()->paginate(15);
        Invoice::attachCalculatedBalances($invoices->getCollection());

        return view('invoices.index', compact('invoices'));
    }

    public function create()
    {
        return view('invoices.create');
    }

    public function store(Request $request)
    {
        // Implementation
        return redirect()->route('sales.invoices.index')->with('success', 'Invoice created successfully.');
    }

    public function show(Request $request, Invoice $invoice)
    {
        $invoice->load('sale.items.product.unit', 'customer');
        Invoice::attachCalculatedBalances(collect([$invoice]));
        
        // If JSON request, return JSON response for printing
        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'invoice' => [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number ?? 'INV-' . str_pad($invoice->id, 6, '0', STR_PAD_LEFT),
                    'invoice_date' => $invoice->created_at ? $invoice->created_at->format('Y-m-d h:i A') : ($invoice->invoice_date ? $invoice->invoice_date->format('Y-m-d') : null),
                    'due_date' => $invoice->due_date ? $invoice->due_date->format('Y-m-d') : null,
                    'customer_name' => $invoice->customer ? $invoice->customer->name : 'N/A',
                    'subtotal' => $invoice->subtotal ?? 0,
                    'discount_amount' => $invoice->discount_amount ?? 0,
                    'tax_amount' => $invoice->tax_amount ?? 0,
                    'total_amount' => $invoice->total_amount ?? 0,
                    'paid_amount' => $invoice->calculated_paid_amount ?? ($invoice->paid_amount ?? 0),
                    'db_paid_amount' => $invoice->db_paid_amount ?? ($invoice->paid_amount ?? 0),
                    'adj_paid_amount' => $invoice->adj_paid_amount ?? 0,
                    'previous_balance' => $invoice->invoice_previous_balance ?? 0,
                    'total_payable' => $invoice->total_payable ?? ($invoice->total_amount ?? 0),
                    'remaining_amount' => $invoice->remaining_balance_due ?? max(0, ($invoice->total_amount ?? 0) - ($invoice->paid_amount ?? 0)),
                    'status' => $invoice->status ?? 'draft',
                    'notes' => $invoice->notes ?? null,
                    'items' => $invoice->sale ? $invoice->sale->items->map(function($item) {
                        return [
                            'product_name' => $item->product_name ?? ($item->product->name ?? 'N/A'),
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unit_price,
                            'discount' => $item->discount ?? 0,
                            'tax' => $item->tax ?? 0,
                            'total' => $item->total ?? 0,
                        ];
                    }) : []
                ]
            ]);
        }
        
        return view('invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice)
    {
        $invoice->load('sale.items.product.unit', 'customer');
        return view('invoices.edit', compact('invoice'));
    }

    public function update(Request $request, Invoice $invoice)
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
            'status' => 'nullable|in:draft,sent,paid,overdue,cancelled',
            'notes' => 'nullable|string',
            'change_comment' => 'required|string|min:1|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $sale = $invoice->sale;
            if (!$sale) {
                return redirect()->back()->with('error', 'Sale not found for this invoice.');
            }

            // Get existing item IDs
            $existingItemIds = $sale->items->pluck('id')->toArray();
            $submittedItemIds = array_filter(array_column($validated['items'], 'id'));

            // Delete removed items (for returns)
            $itemsToDelete = array_diff($existingItemIds, $submittedItemIds);
            if (!empty($itemsToDelete)) {
                foreach ($itemsToDelete as $itemId) {
                    $item = SaleItem::find($itemId);
                    if ($item) {
                        // Restore stock if product exists
                        if ($item->product_id && $item->product) {
                            $item->product->increment('stock_quantity', $item->quantity);
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
                $itemTotal = ($itemData['quantity'] * $itemData['unit_price']) - ($itemData['discount'] ?? 0);
                
                if (isset($itemData['id']) && $itemData['id']) {
                    // Update existing item
                    $item = SaleItem::find($itemData['id']);
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

                        // Adjust stock if product changed or quantity changed
                        if ($oldProductId && $item->product) {
                            $item->product->increment('stock_quantity', $oldQuantity);
                        }
                        if ($itemData['product_id'] && $itemData['product_id'] != $oldProductId) {
                            $newProduct = Product::find($itemData['product_id']);
                            if ($newProduct) {
                                $newProduct->decrement('stock_quantity', $itemData['quantity']);
                            }
                        } elseif ($itemData['product_id'] && $itemData['product_id'] == $oldProductId) {
                            $quantityDiff = $oldQuantity - $itemData['quantity'];
                            if ($quantityDiff > 0) {
                                $item->product->increment('stock_quantity', $quantityDiff);
                            } elseif ($quantityDiff < 0) {
                                $item->product->decrement('stock_quantity', abs($quantityDiff));
                            }
                        }
                    }
                } else {
                    // Create new item
                    SaleItem::create([
                        'sale_id' => $sale->id,
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
                        $product = Product::find($itemData['product_id']);
                        if ($product) {
                            $product->decrement('stock_quantity', $itemData['quantity']);
                        }
                    }
                }

                $subtotal += $itemTotal;
                $totalDiscount += ($itemData['discount'] ?? 0);
                $totalTax += ($itemData['tax'] ?? 0);
            }

            $totalAmount = $subtotal + $totalTax;

            // Store old values for logging
            $oldTotalAmount = $invoice->total_amount;
            $oldStatus = $invoice->status;
            $oldSubtotal = $invoice->subtotal;
            $oldTax = $invoice->tax_amount;
            $oldDiscount = $invoice->discount_amount;
            
            // Track changes
            $changes = [];
            if ($oldTotalAmount != $totalAmount) {
                $changes['total_amount'] = ['old' => $oldTotalAmount, 'new' => $totalAmount];
            }
            if ($oldSubtotal != $subtotal) {
                $changes['subtotal'] = ['old' => $oldSubtotal, 'new' => $subtotal];
            }
            if ($oldTax != $totalTax) {
                $changes['tax_amount'] = ['old' => $oldTax, 'new' => $totalTax];
            }
            if ($oldDiscount != $totalDiscount) {
                $changes['discount_amount'] = ['old' => $oldDiscount, 'new' => $totalDiscount];
            }
            $newStatus = $validated['status'] ?? $invoice->status;
            if ($oldStatus != $newStatus) {
                $changes['status'] = ['old' => $oldStatus, 'new' => $newStatus];
            }
            if (isset($validated['notes']) && $invoice->notes != $validated['notes']) {
                $changes['notes'] = ['old' => $invoice->notes, 'new' => $validated['notes']];
            }

            // Update sale
            $sale->update([
                'subtotal' => $subtotal,
                'discount_amount' => $totalDiscount,
                'tax_amount' => $totalTax,
                'total_amount' => $totalAmount,
            ]);

            // Update invoice
            $invoice->update([
                'subtotal' => $subtotal,
                'discount_amount' => $totalDiscount,
                'tax_amount' => $totalTax,
                'total_amount' => $totalAmount,
                'status' => $newStatus,
                'notes' => $validated['notes'] ?? $invoice->notes,
            ]);

            // Log invoice changes if there are any changes and customer exists
            if (!empty($changes) && $invoice->customer_id) {
                $changeDescriptions = [];
                foreach ($changes as $field => $change) {
                    if ($field === 'status') {
                        $changeDescriptions[] = "Status changed from '{$change['old']}' to '{$change['new']}'";
                    } elseif ($field === 'total_amount') {
                        $changeDescriptions[] = "Total amount changed from PKR " . number_format($change['old'], 2) . " to PKR " . number_format($change['new'], 2);
                    } elseif ($field === 'subtotal') {
                        $changeDescriptions[] = "Subtotal changed from PKR " . number_format($change['old'], 2) . " to PKR " . number_format($change['new'], 2);
                    } elseif ($field === 'tax_amount') {
                        $changeDescriptions[] = "Tax changed from PKR " . number_format($change['old'], 2) . " to PKR " . number_format($change['new'], 2);
                    } elseif ($field === 'discount_amount') {
                        $changeDescriptions[] = "Discount changed from PKR " . number_format($change['old'], 2) . " to PKR " . number_format($change['new'], 2);
                    } else {
                        $changeDescriptions[] = ucfirst(str_replace('_', ' ', $field)) . " changed";
                    }
                }
                
                \App\Models\CustomerPaymentLog::create([
                    'customer_id' => $invoice->customer_id,
                    'user_id' => auth()->id(),
                    'log_type' => 'invoice_change',
                    'sale_id' => $sale->id,
                    'invoice_id' => $invoice->id,
                    'reference_number' => $invoice->invoice_number,
                    'amount' => $totalAmount,
                    'previous_amount' => $oldTotalAmount,
                    'new_amount' => $totalAmount,
                    'description' => "Invoice updated: " . implode(', ', $changeDescriptions),
                    'comment' => $validated['change_comment'] ?? null,
                    'changes' => $changes,
                ]);
            }

            DB::commit();

            return redirect()->route('sales.invoices.show', $invoice)->with('success', 'Invoice updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error updating invoice: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(Invoice $invoice)
    {
        try {
            DB::beginTransaction();

            // Restore stock for all items
            if ($invoice->sale) {
                foreach ($invoice->sale->items as $item) {
                    if ($item->product_id && $item->product) {
                        $item->product->increment('stock_quantity', $item->quantity);
                    }
                }
            }

            $invoice->delete();
            
            DB::commit();

            return redirect()->route('sales.invoices.index')->with('success', 'Invoice deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error deleting invoice: ' . $e->getMessage());
        }
    }
}
