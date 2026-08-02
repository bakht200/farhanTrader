<x-app-layout>
    <x-slot name="header">Edit Order</x-slot>
    
    <div class="mb-4">
        <nav class="text-sm text-gray-600">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-900">Dashboard</a>
            <span class="mx-2">></span>
            <a href="{{ route('orders.index') }}" class="hover:text-gray-900">Orders</a>
            <span class="mx-2">></span>
            <span class="text-gray-900 font-medium">Edit Order</span>
        </nav>
    </div>

    <div class="bg-white shadow-sm rounded-lg p-6">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ $order->sale_number ?? $order->order_number ?? 'N/A' }}</h2>
                <p class="text-sm text-gray-500">Customer: {{ $order->customer->name ?? 'Walk-in Customer' }}</p>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('orders.show', $order) }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-md">Cancel</a>
            </div>
        </div>

        <form method="POST" action="{{ route('orders.update', $order) }}" id="order-edit-form">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                        <option value="pending" {{ $order->status === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="completed" {{ $order->status === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="on_hold" {{ $order->status === 'on_hold' ? 'selected' : '' }}>On Hold</option>
                        <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Payment Status</label>
                    <select name="payment_status" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                        <option value="pending" {{ ($order->payment_status ?? 'pending') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="paid" {{ ($order->payment_status ?? '') === 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="partial" {{ ($order->payment_status ?? '') === 'partial' ? 'selected' : '' }}>Partial</option>
                    </select>
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                <textarea name="notes" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">{{ $order->notes }}</textarea>
            </div>

            <!-- Items Section -->
            <div class="mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">Items</h3>
                    <button type="button" onclick="addNewItem()" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md text-sm">
                        Add Item
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200" id="items-table">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Unit Price</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Discount</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tax</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="items-tbody">
                            @if($order->items && $order->items->count() > 0)
                                @foreach($order->items as $item)
                                <tr class="item-row" data-item-id="{{ $item->id }}">
                                    <td class="px-4 py-3">
                                        <input type="hidden" name="items[{{ $loop->index }}][id]" value="{{ $item->id }}">
                                        <input type="text" name="items[{{ $loop->index }}][product_name]" 
                                               value="{{ $item->product_name ?? ($item->product->name ?? '') }}" 
                                               class="w-full px-2 py-1 border border-gray-300 rounded text-sm" 
                                               placeholder="Product Name" required>
                                        <input type="hidden" name="items[{{ $loop->index }}][product_id]" value="{{ $item->product_id ?? '' }}">
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="number" name="items[{{ $loop->index }}][quantity]" 
                                               value="{{ $item->quantity }}" 
                                               step="0.01" min="0.01" 
                                               class="item-quantity w-full px-2 py-1 border border-gray-300 rounded text-sm" 
                                               required onchange="calculateItemTotal(this)">
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="number" name="items[{{ $loop->index }}][unit_price]" 
                                               value="{{ $item->unit_price }}" 
                                               step="0.01" min="0" 
                                               class="item-unit-price w-full px-2 py-1 border border-gray-300 rounded text-sm" 
                                               required onchange="calculateItemTotal(this)">
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="number" name="items[{{ $loop->index }}][discount]" 
                                               value="{{ $item->discount ?? 0 }}" 
                                               step="0.01" min="0" 
                                               class="item-discount w-full px-2 py-1 border border-gray-300 rounded text-sm" 
                                               onchange="calculateItemTotal(this)">
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="number" name="items[{{ $loop->index }}][tax]" 
                                               value="{{ $item->tax ?? 0 }}" 
                                               step="0.01" min="0" 
                                               class="item-tax w-full px-2 py-1 border border-gray-300 rounded text-sm" 
                                               onchange="calculateItemTotal(this)">
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="item-total text-sm font-medium">{{ number_format($item->total ?? 0, 2) }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <button type="button" onclick="removeItem(this)" class="text-red-600 hover:text-red-900">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex space-x-2">
                <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white px-6 py-2 rounded-md">Update Order</button>
                <a href="{{ route('orders.show', $order) }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2 rounded-md">Cancel</a>
            </div>
        </form>
    </div>

    <script>
        let itemIndex = {{ $order->items && $order->items->count() ? $order->items->count() : 0 }};

        function addNewItem() {
            const tbody = document.getElementById('items-tbody');
            const row = document.createElement('tr');
            row.className = 'item-row';
            row.innerHTML = `
                <td class="px-4 py-3">
                    <input type="hidden" name="items[${itemIndex}][id]" value="">
                    <input type="text" name="items[${itemIndex}][product_name]" 
                           class="w-full px-2 py-1 border border-gray-300 rounded text-sm" 
                           placeholder="Product Name" required>
                    <input type="hidden" name="items[${itemIndex}][product_id]" value="">
                </td>
                <td class="px-4 py-3">
                    <input type="number" name="items[${itemIndex}][quantity]" 
                           value="1" step="0.01" min="0.01" 
                           class="item-quantity w-full px-2 py-1 border border-gray-300 rounded text-sm" 
                           required onchange="calculateItemTotal(this)">
                </td>
                <td class="px-4 py-3">
                    <input type="number" name="items[${itemIndex}][unit_price]" 
                           value="0" step="0.01" min="0" 
                           class="item-unit-price w-full px-2 py-1 border border-gray-300 rounded text-sm" 
                           required onchange="calculateItemTotal(this)">
                </td>
                <td class="px-4 py-3">
                    <input type="number" name="items[${itemIndex}][discount]" 
                           value="0" step="0.01" min="0" 
                           class="item-discount w-full px-2 py-1 border border-gray-300 rounded text-sm" 
                           onchange="calculateItemTotal(this)">
                </td>
                <td class="px-4 py-3">
                    <input type="number" name="items[${itemIndex}][tax]" 
                           value="0" step="0.01" min="0" 
                           class="item-tax w-full px-2 py-1 border border-gray-300 rounded text-sm" 
                           onchange="calculateItemTotal(this)">
                </td>
                <td class="px-4 py-3">
                    <span class="item-total text-sm font-medium">0.00</span>
                </td>
                <td class="px-4 py-3">
                    <button type="button" onclick="removeItem(this)" class="text-red-600 hover:text-red-900">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </td>
            `;
            tbody.appendChild(row);
            itemIndex++;
        }

        function removeItem(button) {
            if (confirm('Are you sure you want to remove this item? This will return the product to stock.')) {
                const row = button.closest('tr');
                row.remove();
            }
        }

        function calculateItemTotal(input) {
            const row = input.closest('tr');
            const quantity = parseFloat(row.querySelector('.item-quantity').value) || 0;
            const unitPrice = parseFloat(row.querySelector('.item-unit-price').value) || 0;
            const discount = parseFloat(row.querySelector('.item-discount').value) || 0;
            const tax = parseFloat(row.querySelector('.item-tax').value) || 0;
            
            const total = (quantity * unitPrice) - discount + tax;
            row.querySelector('.item-total').textContent = total.toFixed(2);
        }
    </script>
</x-app-layout>
