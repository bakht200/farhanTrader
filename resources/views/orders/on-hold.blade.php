<x-app-layout>
    <x-slot name="header">
        On-Hold Orders
    </x-slot>

    <!-- Breadcrumb -->
    <div class="mb-4">
        <nav class="text-sm text-gray-600">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-900">Dashboard</a>
            <span class="mx-2">></span>
            <span class="text-gray-900 font-medium">on-hold order</span>
        </nav>
    </div>

    <!-- Search and Filters -->
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <form method="GET" action="{{ route('orders.on-hold') }}" class="flex flex-col md:flex-row md:items-center gap-4" id="search-form">
            <!-- Search -->
            <div class="flex-1">
                <div class="relative">
                    <input type="text" 
                           id="search-input"
                           name="search" 
                           value="{{ request('search') }}" 
                           placeholder="Search" 
                           class="w-full px-4 py-2 pl-10 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
                           oninput="handleSearchInput()">
                    <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
            </div>

            <!-- Category Filter -->
            <div>
                <select name="category_id" onchange="this.form.submit()" class="px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                    <option value="all" {{ request('category_id') === 'all' || !request('category_id') ? 'selected' : '' }}>Category</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Start Date Filter -->
            <div>
                <input type="date" 
                       name="start_date" 
                       value="{{ request('start_date') }}" 
                       onchange="this.form.submit()"
                       class="px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
                       placeholder="Start Date">
            </div>

            <!-- End Date Filter -->
            <div>
                <input type="date" 
                       name="end_date" 
                       value="{{ request('end_date') }}" 
                       onchange="this.form.submit()"
                       class="px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
                       placeholder="End Date">
            </div>

            <!-- Clear Filters -->
            @if(request('search') || request('category_id') !== 'all' || request('start_date') || request('end_date'))
                <a href="{{ route('orders.on-hold') }}" class="px-4 py-2 text-gray-600 hover:text-gray-900">
                    Clear
                </a>
            @endif
        </form>
    </div>

    <!-- On-Hold Orders Table -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left">
                            <input type="checkbox" class="rounded border-gray-300 text-orange-600 focus:ring-orange-500" id="select-all">
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order Number</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($orders as $order)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <input type="checkbox" class="order-checkbox rounded border-gray-300 text-orange-600 focus:ring-orange-500" value="{{ $order->id }}">
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-medium text-gray-900">{{ $order->sale_number ?? $order->order_number ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-900">{{ $order->customer->name ?? 'Walk-in Customer' }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-900">{{ $order->items->sum('quantity') }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-medium text-gray-900">PKR {{ number_format($order->total_amount, 2) }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-900">{{ $order->created_at->format('jS M, Y') }}</span>
                            <span class="text-xs text-gray-500 block">{{ $order->created_at->format('H:i') }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center space-x-3">
                                <a href="{{ route('orders.show', $order) }}" class="text-blue-600 hover:text-blue-900" title="View">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </a>
                                <a href="{{ route('orders.edit', $order) }}" class="text-green-600 hover:text-green-900" title="Edit">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>
                                <button onclick="printOrderBill({{ $order->id }})" class="text-purple-600 hover:text-purple-900" title="Print Bill">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                                    </svg>
                                </button>
                                <form action="{{ route('orders.destroy', $order) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this order?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900" title="Delete">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                            No on-hold orders found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
            <div class="flex items-center">
                <span class="text-sm text-gray-700">Row Per Page</span>
                <form method="GET" action="{{ route('orders.on-hold') }}" class="ml-2">
                    @if(request('search'))
                        <input type="hidden" name="search" value="{{ request('search') }}">
                    @endif
                    @if(request('category_id'))
                        <input type="hidden" name="category_id" value="{{ request('category_id') }}">
                    @endif
                    @if(request('start_date'))
                        <input type="hidden" name="start_date" value="{{ request('start_date') }}">
                    @endif
                    @if(request('end_date'))
                        <input type="hidden" name="end_date" value="{{ request('end_date') }}">
                    @endif
                    <select name="per_page" onchange="this.form.submit()" class="px-3 py-1 border border-gray-300 rounded-md text-sm focus:ring-orange-500 focus:border-orange-500">
                        <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10 Entries</option>
                        <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25 Entries</option>
                        <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50 Entries</option>
                        <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100 Entries</option>
                    </select>
                </form>
            </div>
            <div class="flex items-center space-x-2">
                @if($orders->hasPages())
                    <div class="flex items-center space-x-1">
                        @if($orders->onFirstPage())
                            <span class="px-3 py-1 text-gray-400 cursor-not-allowed">&lt;</span>
                        @else
                            <a href="{{ $orders->previousPageUrl() }}" class="px-3 py-1 text-gray-700 hover:text-gray-900">&lt;</a>
                        @endif
                        
                        @foreach($orders->getUrlRange(1, min(5, $orders->lastPage())) as $page => $url)
                            @if($page == $orders->currentPage())
                                <span class="px-3 py-1 bg-orange-500 text-white rounded">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="px-3 py-1 text-gray-700 hover:text-gray-900">{{ $page }}</a>
                            @endif
                        @endforeach
                        
                        @if($orders->hasMorePages())
                            <span class="px-2 py-1 text-gray-500">...</span>
                            <a href="{{ $orders->url($orders->lastPage()) }}" class="px-3 py-1 text-gray-700 hover:text-gray-900">{{ $orders->lastPage() }}</a>
                        @endif
                        
                        @if($orders->hasMorePages())
                            <a href="{{ $orders->nextPageUrl() }}" class="px-3 py-1 text-gray-700 hover:text-gray-900">&gt;</a>
                        @else
                            <span class="px-3 py-1 text-gray-400 cursor-not-allowed">&gt;</span>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        // Auto-search on keypress with debounce
        let searchTimeout;
        function handleSearchInput() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                const form = document.getElementById('search-form');
                if (form) {
                    form.submit();
                }
            }, 500); // Wait 500ms after user stops typing
        }

        // Select all checkbox functionality
        document.getElementById('select-all').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.order-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });

        // Print order bill
        async function printOrderBill(orderId) {
            try {
                if (window.FTReceipt?.requireConfigured) {
                    await window.FTReceipt.requireConfigured();
                }
            } catch (e) {
                return;
            }
            const receiptHeader = (window.FTReceipt && window.FTReceipt.headerHtml)
                ? window.FTReceipt.headerHtml(typeof receiptDocTitle !== 'undefined' ? receiptDocTitle : 'Order Receipt')
                : '';
            fetch(`/orders/${orderId}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert('Error: ' + data.error);
                    return;
                }
                
                const order = data.order;
                const units = order.units || [];
                const printWindow = window.open('', '_blank');
                
                let customerName = 'Walk-in Customer';
                if (order.customer && order.customer.name) {
                    customerName = order.customer.name;
                } else if (order.notes) {
                    const match = order.notes.match(/Customer:\s*(.+?)(?:\s*\(|$)/);
                    if (match) {
                        customerName = match[1].trim();
                    }
                }
                
                // Use formatted date if available, otherwise format it
                let formattedDateTime;
                if (order.created_at && order.created_at.match(/^\d{4}-\d{2}-\d{2} \d{2}:\d{2} (AM|PM)$/)) {
                    // Already formatted, use directly
                    formattedDateTime = order.created_at;
                } else {
                    const dateSource = order.created_at || order.sale_date || order.order_date || new Date().toISOString();
                    const dt = new Date(dateSource);
                    // Format: 2026-02-01 05:23 PM
                    const year = dt.getFullYear();
                    const month = String(dt.getMonth() + 1).padStart(2, '0');
                    const day = String(dt.getDate()).padStart(2, '0');
                    let hours = dt.getHours();
                    const minutes = String(dt.getMinutes()).padStart(2, '0');
                    const ampm = hours >= 12 ? 'PM' : 'AM';
                    hours = hours % 12;
                    hours = hours ? hours : 12; // the hour '0' should be '12'
                    const formattedHours = String(hours).padStart(2, '0');
                    formattedDateTime = `${year}-${month}-${day} ${formattedHours}:${minutes} ${ampm}`;
                }
                const orderDate = order.sale_date || order.order_date || (order.created_at ? order.created_at.split(' ')[0] : new Date().toISOString().split('T')[0]);
                const saleNumber = order.sale_number || order.order_number || 'N/A';
                const paymentMethod = order.payment_status === 'paid' ? 'Cash' : (order.payment_status || 'Pending');
                
                let printContent = `
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <style>
                            @media print { 
                                @page { margin: 5mm; }
                                * { color: #000 !important; }
                            }
                            * { color: #000 !important; }
                            body { font-family: 'Arial', sans-serif; padding: 8px; max-width: 58mm; margin: 0 auto; font-size: 11px; }
                            .header { text-align: center; margin-bottom: 8px; border-bottom: 1px solid #000; padding-bottom: 6px; }
                            .header h2 { margin: 0; font-size: 14px; font-weight: bold; }
                            .header p { margin: 4px 0 0 0; font-size: 10px; }
                            .business-info {
                                padding-top: 6px;
                                font-size: 9px;
                            }
                            .business-service {
                                font-weight: bold;
                                margin-bottom: 4px;
                                font-size: 9px;
                            }
                            .business-service i {
                                font-style: italic;
                            }
                            .business-contact {
                                display: flex;
                                justify-content: space-between;
                                align-items: flex-start;
                                margin-top: 4px;
                                font-size: 8px;
                            }
                            .business-contact-left {
                                text-align: left;
                            }
                            .business-contact-right {
                                text-align: right;
                            }
                            .order-info { margin-bottom: 8px; font-size: 10px; }
                            .order-info div { display: flex; justify-content: space-between; margin-bottom: 2px; }
                            table { width: 100%; border-collapse: collapse; margin-bottom: 8px; font-size: 10px; }
                            th, td { padding: 3px 1px; text-align: left; border-bottom: 1px solid #ddd; }
                            th { font-weight: bold; border-bottom: 1px solid #000; }
                            td:nth-child(2), td:nth-child(3), td:nth-child(4) { text-align: right; }
                            .total-section { text-align: right; font-weight: bold; font-size: 11px; margin-top: 8px; padding-top: 6px; border-top: 1px solid #000; }
                            .footer { text-align: center; margin-top: 10px; padding-top: 8px; border-top: 1px solid #000; font-size: 9px; }
                        </style>
                    </head>
                    <body>
${(window.FTReceipt && window.FTReceipt.headerHtml) ? window.FTReceipt.headerHtml('Order Receipt') : ''}
                        <div class="order-info">
                            <div><span>Sale Number:</span><span><strong>${saleNumber}</strong></span></div>
                            <div><span>Date:</span><span>${formattedDateTime}</span></div>
                            <div><span>Customer:</span><span>${customerName}</span></div>
                            <div><span>Payment:</span><span>${paymentMethod}</span></div>
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th style="text-align: right;">Qty</th>
                                    <th style="text-align: right;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                if (order.items && order.items.length > 0) {
                    order.items.forEach(item => {
                        // Prefer explicit product_name (for custom products or when product is missing),
                        // then fallback to related product name
                        const itemName = item.product_name || item.product?.name || 'N/A';
                        
                        // Get unit name - try multiple sources
                        let unitName = 'Pcs'; // Default
                        
                        // First try: Use unit_id to find in units array (same as POS)
                        if (item.unit_id && units && units.length > 0) {
                            const selectedUnit = units.find(u => parseInt(u.id) === parseInt(item.unit_id));
                            if (selectedUnit && selectedUnit.short_name) {
                                unitName = selectedUnit.short_name;
                            }
                        }
                        
                        // Second try: Use product.unit if available
                        if (unitName === 'Pcs' && item.product && item.product.unit && item.product.unit.short_name) {
                            unitName = item.product.unit.short_name;
                        }
                        
                        // Third try: Use unit_name or unit_short_name from item
                        if (unitName === 'Pcs' && (item.unit_name || item.unit_short_name)) {
                            unitName = item.unit_name || item.unit_short_name;
                        }
                        
                        const quantity = parseFloat(item.quantity || 0);
                        const unitPrice = parseFloat(item.unit_price || 0);
                        const discount = parseFloat(item.discount || 0);
                        let itemTotal = (quantity * unitPrice) - discount;
                        
                        printContent += `
                            <tr>
                                <td>${itemName}</td>
                                <td style="text-align: right;">${quantity.toFixed(2)} ${unitName}</td>
                                <td style="text-align: right;">PKR ${itemTotal.toFixed(2)}</td>
                            </tr>
                        `;
                    });
                }
                
                const currentSaleTotal = parseFloat(order.total_amount || 0);
                const previousBalance = parseFloat(order.previous_balance || 0);
                const grandTotal = currentSaleTotal + previousBalance; // Grand Total = Current Sale + Previous Balance
                const paidAmount = parseFloat(order.paid_amount || 0);
                const regularPaidAmount = parseFloat(order.regular_paid_amount || order.paid_amount || 0);
                const adjPaidAmount = parseFloat(order.adj_paid_amount || 0);
                const adjBillNumber = order.adj_bill_number || null;
                const balance = grandTotal - paidAmount; // Remaining Balance = Grand Total - Paid
                const previousBalancePayment = parseFloat(order.previous_balance_payment || 0);
                
                printContent += `
                            </tbody>
                        </table>
                        <div class="total-section">
                            <p style="font-size: 11px; margin-bottom: 3px; display: flex; justify-content: space-between;">
                                <span>Subtotal:</span>
                                <span>PKR ${currentSaleTotal.toFixed(2)}</span>
                            </p>
                            ${previousBalance > 0 ? '<p style="font-size: 11px; margin-bottom: 3px; display: flex; justify-content: space-between;"><span>Previous Balance:</span><span>PKR ' + previousBalance.toFixed(2) + '</span></p>' : ''}
                            <p style="border-top: 1px solid #ddd; margin: 5px 0; padding-top: 5px;"></p>
                            <p style="font-size: 12px; margin-bottom: 3px; display: flex; justify-content: space-between; font-weight: bold;">
                                <span>Total Payable:</span>
                                <span>PKR ${grandTotal.toFixed(2)}</span>
                            </p>
                            ${regularPaidAmount > 0 ? '<p style="font-size: 11px; margin-bottom: 3px; display: flex; justify-content: space-between;"><span>Amount Paid:</span><span>PKR ' + regularPaidAmount.toFixed(2) + '</span></p>' : ''}
                            ${adjPaidAmount > 0 && adjBillNumber ? '<p style="font-size: 11px; margin-bottom: 3px; display: flex; justify-content: space-between;"><span>Previous Balance Paid (' + adjBillNumber + '):</span><span>PKR ' + adjPaidAmount.toFixed(2) + '</span></p>' : ''}
                            ${paidAmount > 0 && (regularPaidAmount > 0 || adjPaidAmount > 0) ? '<p style="font-size: 11px; margin-bottom: 3px; display: flex; justify-content: space-between; font-weight: bold; border-top: 1px solid #ddd; padding-top: 3px; margin-top: 3px;"><span>Total Paid:</span><span>PKR ' + paidAmount.toFixed(2) + '</span></p>' : ''}
                            ${balance > 0 ? '<p style="border-top: 1px solid #ddd; margin: 5px 0; padding-top: 5px;"></p><p style="font-size: 12px; margin-top: 5px; display: flex; justify-content: space-between; font-weight: bold; color: #000;"><span>Remaining Balance:</span><span>PKR ' + balance.toFixed(2) + '</span></p>' : ''}
                        </div>
                        <div class="footer">
                            <p>Thank you for your business!</p>
                            <p>This is a computer-generated receipt.</p>
                        </div>
                    </body>
                    </html>
                `;
                
                printWindow.document.write(printContent);
                printWindow.document.close();
                printWindow.print();
            })
            .catch(error => {
                console.error('Error:', error);
                window.open(`/orders/${orderId}`, '_blank').print();
            });
        }
    </script>
</x-app-layout>



