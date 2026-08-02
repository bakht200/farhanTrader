<x-app-layout>
    <x-slot name="header">Order Details</x-slot>
    
    <div class="mb-4">
        <nav class="text-sm text-gray-600">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-900">Dashboard</a>
            <span class="mx-2">></span>
            <a href="{{ route('orders.index') }}" class="hover:text-gray-900">Orders</a>
            <span class="mx-2">></span>
            <span class="text-gray-900 font-medium">{{ $order->sale_number ?? $order->order_number ?? 'N/A' }}</span>
        </nav>
    </div>

    <div class="bg-white shadow-sm rounded-lg p-6">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ $order->sale_number ?? $order->order_number ?? 'N/A' }}</h2>
                <p class="text-sm text-gray-500">Date: {{ ($order->sale_date ?? $order->order_date ?? now())->format('Y-m-d') }}</p>
            </div>
            <div class="flex space-x-2">
                <button onclick="printOrderBill({{ $order->id }})" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md inline-flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                    </svg>
                    Print Bill
                </button>
                <a href="{{ route('orders.edit', $order) }}" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-md">Edit</a>
                <a href="{{ route('orders.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-md">Back</a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <h3 class="text-lg font-semibold mb-4">Order Information</h3>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Order Number</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $order->sale_number ?? $order->order_number ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Customer</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $order->customer->name ?? 'Walk-in Customer' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Order Date</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ ($order->sale_date ?? $order->order_date ?? now())->format('Y-m-d') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Expected Delivery</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ ($order->expected_delivery_date ?? null) ? $order->expected_delivery_date->format('Y-m-d') : 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                {{ $order->status === 'completed' ? 'bg-green-100 text-green-800' : 
                                   ($order->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                   ($order->status === 'on_hold' ? 'bg-orange-100 text-orange-800' : 'bg-gray-100 text-gray-800')) }}">
                                {{ ucfirst($order->status) }}
                            </span>
                        </dd>
                    </div>
                </dl>
            </div>
            <div>
                <h3 class="text-lg font-semibold mb-4">Financial Summary</h3>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Subtotal</dt>
                        <dd class="mt-1 text-sm text-gray-900">PKR {{ number_format($order->subtotal ?? 0, 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Tax Amount</dt>
                        <dd class="mt-1 text-sm text-gray-900">PKR {{ number_format($order->tax_amount ?? 0, 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Discount</dt>
                        <dd class="mt-1 text-sm text-gray-900">PKR {{ number_format($order->discount_amount ?? 0, 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Total Amount</dt>
                        <dd class="mt-1 text-lg font-bold text-gray-900">PKR {{ number_format($order->total_amount ?? 0, 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Paid Amount</dt>
                        <dd class="mt-1 text-sm text-green-600">PKR {{ number_format($order->paid_amount ?? 0, 2) }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        @if($order->items->count() > 0)
        <div class="mt-6">
            <h3 class="text-lg font-semibold mb-4">Order Items</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Unit Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($order->items as $item)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $item->product_name ?? ($item->product->name ?? 'N/A') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($item->quantity, 2) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">PKR {{ number_format($item->unit_price ?? 0, 2) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">PKR {{ number_format($item->total ?? 0, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    <script>
        // Print order bill - same function as pending/completed/on-hold pages
        function printOrderBill(orderId) {
            // Fetch order data
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
                            .order-info { 
                                margin-bottom: 8px; 
                                font-size: 10px;
                            }
                            .order-info div { 
                                display: flex; 
                                justify-content: space-between; 
                                margin-bottom: 2px;
                            }
                            table { 
                                width: 100%; 
                                border-collapse: collapse; 
                                margin-bottom: 8px; 
                                font-size: 10px;
                            }
                            th, td { 
                                padding: 3px 1px; 
                                text-align: left; 
                                border-bottom: 1px solid #ddd;
                            }
                            th { 
                                font-weight: bold; 
                                border-bottom: 1px solid #000;
                            }
                            td:nth-child(2), td:nth-child(3) {
                                text-align: right;
                            }
                            .total-section { 
                                text-align: right; 
                                font-weight: bold; 
                                font-size: 11px; 
                                margin-top: 8px; 
                                padding-top: 6px; 
                                border-top: 1px solid #000; 
                            }
                            .footer { 
                                text-align: center; 
                                margin-top: 10px; 
                                padding-top: 8px; 
                                border-top: 1px solid #000; 
                                font-size: 9px;
                            }
                        </style>
                    </head>
                    <body>
                        <div class="header">
                            <h2>FARHAN TRADERS</h2>
                            <div class="business-info">
                                <div class="business-service">
                                    Deals In Food Chemicals / Non Food Chemicals
                                </div>
                                <div class="business-contact">
                                    <div class="business-contact-left">
                                        <div>Ph: 091-2561301</div>
                                        <div>Mob: 0313-9829984</div>
                                        <div>Mob: 0313-6777811</div>
                                    </div>
                                    <div class="business-contact-right">
                                        <div>Email: farhan.akhtar90@yahoo.com</div>
                                    </div>
                                </div>
                            </div>
                            <p style="margin-top: 10px;">Order Receipt</p>
                        </div>
                        <div class="order-info">
                            <div>
                                <span>Sale Number:</span>
                                <span><strong>${saleNumber}</strong></span>
                            </div>
                            <div>
                                <span>Date:</span>
                                <span>${formattedDateTime}</span>
                            </div>
                            <div>
                                <span>Customer:</span>
                                <span>${customerName}</span>
                            </div>
                            <div>
                                <span>Payment:</span>
                                <span>${paymentMethod.charAt(0).toUpperCase() + paymentMethod.slice(1)}</span>
                            </div>
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
                
                setTimeout(() => {
                    printWindow.print();
                }, 500);
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading order data: ' + error.message);
            });
        }
    </script>
</x-app-layout>
