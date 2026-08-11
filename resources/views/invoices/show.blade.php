<x-app-layout>
    <x-slot name="header">Invoice Details</x-slot>
    
    <div class="mb-4">
        <nav class="text-sm text-gray-600">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-900">Dashboard</a>
            <span class="mx-2">></span>
            <a href="{{ route('sales.invoices.index') }}" class="hover:text-gray-900">Invoices</a>
            <span class="mx-2">></span>
            <span class="text-gray-900 font-medium">{{ $invoice->invoice_number ?? 'INV-' . str_pad($invoice->id, 6, '0', STR_PAD_LEFT) }}</span>
        </nav>
    </div>

    <div class="bg-white shadow-sm rounded-lg p-6">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ $invoice->invoice_number ?? 'INV-' . str_pad($invoice->id, 6, '0', STR_PAD_LEFT) }}</h2>
                <p class="text-sm text-gray-500">Date: {{ $invoice->created_at ? $invoice->created_at->format('Y-m-d h:i A') : ($invoice->invoice_date ? $invoice->invoice_date->format('Y-m-d') : 'N/A') }}</p>
            </div>
            <div class="flex space-x-2">
                <button onclick="printInvoice({{ $invoice->id }})" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md inline-flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                    </svg>
                    Print Invoice
                </button>
                <a href="{{ route('sales.invoices.edit', $invoice) }}" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-md">Edit</a>
                <a href="{{ route('sales.invoices.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-md">Back</a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <h3 class="text-lg font-semibold mb-4">Invoice Information</h3>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Invoice Number</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $invoice->invoice_number ?? 'INV-' . str_pad($invoice->id, 6, '0', STR_PAD_LEFT) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Customer</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $invoice->customer->name ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Invoice Date</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $invoice->created_at ? $invoice->created_at->format('Y-m-d h:i A') : ($invoice->invoice_date ? $invoice->invoice_date->format('Y-m-d') : 'N/A') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Due Date</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $invoice->due_date ? $invoice->due_date->format('Y-m-d') : 'N/A' }}</dd>
                    </div>
                    @if($invoice->updated_at)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $invoice->updated_at->format('Y-m-d h:i A') }}</dd>
                    </div>
                    @endif
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1">
                            @php
                                $status = $invoice->status ?? 'pending';
                                $statusClass = match($status) {
                                    'paid' => 'bg-green-100 text-green-800',
                                    'partial' => 'bg-yellow-100 text-yellow-800',
                                    'overdue' => 'bg-red-100 text-red-800',
                                    default => 'bg-gray-100 text-gray-800'
                                };
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">
                                {{ ucfirst($status) }}
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
                        <dd class="mt-1 text-sm text-gray-900">PKR {{ number_format($invoice->subtotal ?? 0, 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Tax Amount</dt>
                        <dd class="mt-1 text-sm text-gray-900">PKR {{ number_format($invoice->tax_amount ?? 0, 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Discount</dt>
                        <dd class="mt-1 text-sm text-gray-900">PKR {{ number_format($invoice->discount_amount ?? 0, 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Total Amount</dt>
                        <dd class="mt-1 text-lg font-bold text-gray-900">PKR {{ number_format($invoice->total_amount ?? 0, 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Paid Amount</dt>
                        <dd class="mt-1 text-sm text-green-600">PKR {{ number_format($invoice->calculated_paid_amount ?? ($invoice->paid_amount ?? 0), 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Previous Balance</dt>
                        <dd class="mt-1 text-sm text-gray-900">PKR {{ number_format($invoice->invoice_previous_balance ?? 0, 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Total Payable</dt>
                        <dd class="mt-1 text-sm text-gray-900">PKR {{ number_format($invoice->total_payable ?? ($invoice->total_amount ?? 0), 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Remaining Amount</dt>
                        @php
                            $remainingAmount = (float) ($invoice->remaining_balance_due ?? max(0, ($invoice->total_amount ?? 0) - ($invoice->paid_amount ?? 0)));
                        @endphp
                        <dd class="mt-1 text-sm font-semibold {{ $remainingAmount > 0 ? 'text-red-600' : 'text-green-600' }}">
                            PKR {{ number_format($remainingAmount, 2) }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        @if($invoice->sale && $invoice->sale->items && $invoice->sale->items->count() > 0)
        <div class="mt-6">
            <h3 class="text-lg font-semibold mb-4">Invoice Items</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Unit Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Discount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tax</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($invoice->sale->items as $item)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $item->product_name ?? $item->product->name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($item->quantity, 2) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">PKR {{ number_format($item->unit_price ?? 0, 2) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">PKR {{ number_format($item->discount ?? 0, 2) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">PKR {{ number_format($item->tax ?? 0, 2) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">PKR {{ number_format($item->total ?? 0, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        @if($invoice->notes)
        <div class="mt-6">
            <h3 class="text-lg font-semibold mb-2">Notes</h3>
            <p class="text-sm text-gray-700">{{ $invoice->notes }}</p>
        </div>
        @endif
    </div>

    <script>
        async function printInvoice(invoiceId) {
            try {
                if (window.FTReceipt?.requireConfigured) {
                    await window.FTReceipt.requireConfigured();
                }
            } catch (e) {
                return;
            }

            fetch(`/sales/invoices/${invoiceId}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Failed to load invoice data');
                }

                const invoice = data.invoice;
                const printWindow = window.open('', '_blank');
                const receiptHeader = (window.FTReceipt && window.FTReceipt.headerHtml)
                    ? window.FTReceipt.headerHtml('INVOICE')
                    : '';

                let printContent = `
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>Invoice - ${invoice.invoice_number || 'INV-' + invoice.id}</title>
                        <style>
                            @media print {
                                @page { margin: 10mm; }
                            }
                            * {
                                color: #000 !important;
                            }
                            body { 
                                font-family: 'Arial', sans-serif; 
                                padding: 20px; 
                                max-width: 80mm; 
                                margin: 0 auto; 
                                font-size: 12px;
                                color: #000 !important;
                            }
                            .header { 
                                text-align: center; 
                                margin-bottom: 20px; 
                                border-bottom: 2px solid #000; 
                                padding-bottom: 15px; 
                            }
                            .header h1 { 
                                margin: 0; 
                                font-size: 24px; 
                                font-weight: bold;
                            }
                            .header p {
                                margin: 5px 0 0 0;
                                font-size: 11px;
                            }
                            .business-info {
                                padding-top: 10px;
                                font-size: 10px;
                            }
                            .invoice-info { 
                                margin-bottom: 20px; 
                                font-size: 11px;
                            }
                            .invoice-info div { 
                                display: flex; 
                                justify-content: space-between; 
                                margin-bottom: 5px;
                            }
                            table { 
                                width: 100%; 
                                border-collapse: collapse; 
                                margin-bottom: 15px; 
                                font-size: 11px;
                            }
                            th, td { 
                                padding: 8px 4px; 
                                text-align: left; 
                                border-bottom: 1px solid #ddd;
                            }
                            th { 
                                font-weight: bold; 
                                border-bottom: 2px solid #000;
                            }
                            td:nth-child(2), td:nth-child(3), td:nth-child(4), td:nth-child(5), td:nth-child(6) {
                                text-align: right;
                            }
                            .total-section { 
                                text-align: right; 
                                font-weight: bold; 
                                font-size: 14px; 
                                margin-top: 15px; 
                                padding-top: 10px; 
                                border-top: 2px solid #000; 
                            }
                            .footer { 
                                text-align: center; 
                                margin-top: 20px; 
                                padding-top: 15px; 
                                border-top: 1px solid #000; 
                                font-size: 10px; 
                            }
                        </style>
                    </head>
                    <body>
                        ${receiptHeader}
                        <div class="invoice-info">
                            <div><span>Invoice Number:</span><span><strong>${invoice.invoice_number || 'INV-' + invoice.id}</strong></span></div>
                            <div><span>Date:</span><span>${invoice.invoice_date || ''}</span></div>
                            <div><span>Customer:</span><span>${invoice.customer_name || 'N/A'}</span></div>
                            ${invoice.due_date ? `<div><span>Due Date:</span><span>${invoice.due_date}</span></div>` : ''}
                            <div><span>Status:</span><span>${(invoice.status || '').toUpperCase()}</span></div>
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th style="text-align: right;">Qty</th>
                                    <th style="text-align: right;">Price</th>
                                    <th style="text-align: right;">Discount</th>
                                    <th style="text-align: right;">Tax</th>
                                    <th style="text-align: right;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                if (invoice.items && invoice.items.length > 0) {
                    invoice.items.forEach(item => {
                        printContent += `
                            <tr>
                                <td>${item.product_name || 'N/A'}</td>
                                <td style="text-align: right;">${parseFloat(item.quantity || 0).toFixed(2)}</td>
                                <td style="text-align: right;">PKR ${parseFloat(item.unit_price || 0).toFixed(2)}</td>
                                <td style="text-align: right;">PKR ${parseFloat(item.discount || 0).toFixed(2)}</td>
                                <td style="text-align: right;">PKR ${parseFloat(item.tax || 0).toFixed(2)}</td>
                                <td style="text-align: right;">PKR ${parseFloat(item.total || 0).toFixed(2)}</td>
                            </tr>
                        `;
                    });
                }
                
                printContent += `
                            </tbody>
                        </table>
                        <div class="total-section">
                            <p>Subtotal: PKR ${parseFloat(invoice.subtotal || 0).toFixed(2)}</p>
                            ${parseFloat(invoice.discount_amount || 0) > 0 ? `<p>Discount: PKR ${parseFloat(invoice.discount_amount || 0).toFixed(2)}</p>` : ''}
                            ${parseFloat(invoice.tax_amount || 0) > 0 ? `<p>Tax: PKR ${parseFloat(invoice.tax_amount || 0).toFixed(2)}</p>` : ''}
                            <p style="font-size: 16px; margin-top: 10px;">Total: PKR ${parseFloat(invoice.total_amount || 0).toFixed(2)}</p>
                            ${parseFloat(invoice.paid_amount || 0) > 0 ? `<p style="font-size: 12px; margin-top: 5px;">Paid: PKR ${parseFloat(invoice.paid_amount || 0).toFixed(2)}</p>` : ''}
                            ${parseFloat(invoice.remaining_amount || 0) > 0 ? `<p style="font-size: 12px; margin-top: 5px;">Remaining: PKR ${parseFloat(invoice.remaining_amount || 0).toFixed(2)}</p>` : ''}
                        </div>
                        ${invoice.notes ? `<div style="margin-top: 15px; font-size: 10px;"><strong>Notes:</strong> ${invoice.notes}</div>` : ''}
                        <div class="footer">
                            <p>Thank you for your business!</p>
                            <p>This is a computer-generated invoice.</p>
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
                alert('Error loading invoice: ' + error.message);
            });
        }
    </script>
</x-app-layout>




