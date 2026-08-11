<x-app-layout>
    <x-slot name="header">
        Pending Orders
    </x-slot>

    <!-- Breadcrumb -->
    <div class="mb-4">
        <nav class="text-sm text-gray-600">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-900">Dashboard</a>
            <span class="mx-2">></span>
            <span class="text-gray-900 font-medium">Pending order</span>
        </nav>
    </div>

    <!-- Search and Filters -->
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <form method="GET" action="{{ route('orders.pending') }}" class="flex flex-col md:flex-row md:items-center gap-4" id="search-form">
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
                <a href="{{ route('orders.pending') }}" class="px-4 py-2 text-gray-600 hover:text-gray-900">
                    Clear
                </a>
            @endif
        </form>
    </div>

    <!-- Pending Orders Table -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden flex flex-col" style="height: calc(100vh - 250px);">
        <div class="overflow-x-auto overflow-y-auto flex-1">
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
                    @php
                        $hasAdjBill = isset($order->adj_bill_number);
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <input type="checkbox" class="order-checkbox rounded border-gray-300 text-orange-600 focus:ring-orange-500" value="{{ $order->id }}">
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-medium text-gray-900">{{ $order->sale_number ?? $order->order_number ?? 'N/A' }}</span>
                            @if($hasAdjBill)
                                <div class="mt-1">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800" title="Includes adjustment payment: {{ $order->adj_bill_number }}">
                                        + ADJ
                                    </span>
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-900">{{ $order->customer->name ?? 'Walk-in Customer' }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-900">{{ $order->items->sum('quantity') }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm">
                                <div class="font-medium text-gray-900">PKR {{ number_format($order->total_amount, 2) }}</div>
                                @php
                                    $paidAmount = $order->paid_amount ?? 0;
                                    $previousBalance = $order->previous_balance ?? 0;
                                    $grandTotal = $order->total_amount + $previousBalance;
                                    $balance = max(0, $grandTotal - $paidAmount);
                                @endphp
                                @if($hasAdjBill)
                                    <div class="text-xs text-gray-500 mt-1">
                                        (incl. ADJ: {{ number_format($order->adj_paid_amount ?? 0, 2) }})
                                    </div>
                                @endif
                                @if($balance > 0)
                                    <div class="text-xs text-red-600">Remaining Balance: PKR {{ number_format($balance, 2) }}</div>
                                @endif
                            </div>
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
                                <a href="{{ route('orders.edit', $order) }}" class="text-orange-600 hover:text-orange-900" title="Edit">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>
                                @php
                                    $currentBill = $order->total_amount ?? 0;
                                    $previousBalance = $order->previous_balance ?? 0;
                                    $grandTotal = $currentBill + $previousBalance;
                                    // paid_amount is already merged with ADJ in controller for this list.
                                    $paidAmount = ($order->paid_amount ?? 0);
                                    $balance = max(0, $grandTotal - $paidAmount);
                                @endphp
                                <!-- Print Bill: use same amounts as Pay Balance popup so receipt matches -->
                                <button onclick="printOrderBill({{ $order->id }}, { currentBill: {{ $currentBill }}, previousBalance: {{ $previousBalance }}, grandTotal: {{ $grandTotal }}, totalPaidByCustomer: {{ $paidAmount }}, balance: {{ max(0, $balance) }} })" class="text-purple-600 hover:text-purple-900" title="Print Bill">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                                    </svg>
                                </button>
                                @if($balance > 0)
                                    <button onclick="openPaymentModal({{ $order->id }}, {{ $currentBill }}, {{ $previousBalance }}, {{ $grandTotal }}, {{ $paidAmount }}, {{ $balance }}, {{ json_encode($order->sale_number ?? 'N/A') }})" 
                                            class="text-green-600 hover:text-green-900" title="Pay Balance">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                            No pending orders found.
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
                <form method="GET" action="{{ route('orders.pending') }}" class="ml-2">
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

    <!-- Payment Modal -->
    <div id="payment-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-2xl max-w-md w-full">
            <!-- Modal Header -->
            <div class="bg-gray-800 text-white px-6 py-4 flex justify-between items-center rounded-t-lg">
                <h3 class="text-xl font-bold">Pay Balance</h3>
                <button onclick="closePaymentModal()" class="text-white hover:text-gray-300 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Payment Content -->
            <form id="payment-form" class="p-6">
                @csrf
                <input type="hidden" id="payment-sale-id" name="sale_id">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Order/Sale Number</label>
                    <div class="text-lg font-semibold text-gray-900" id="payment-sale-number"></div>
                </div>
                
                <div class="mb-4 space-y-2 bg-gray-50 p-4 rounded-lg">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Subtotal (Current Bill):</span>
                        <span class="font-semibold text-gray-900" id="payment-current-bill">PKR 0.00</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Remaining Previous Balance:</span>
                        <span class="font-semibold text-gray-900" id="payment-previous-balance">PKR 0.00</span>
                    </div>
                    <div class="border-t border-gray-200 pt-2 flex justify-between font-bold">
                        <span class="text-gray-700">Total Payable:</span>
                        <span class="text-gray-900" id="payment-total-amount">PKR 0.00</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Amount Paid:</span>
                        <span class="text-green-600 font-semibold" id="payment-paid-amount">PKR 0.00</span>
                    </div>
                    <div class="flex justify-between text-sm font-bold">
                        <span class="text-gray-700">Amount Remaining:</span>
                        <span class="text-red-600" id="payment-balance">PKR 0.00</span>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="payment-amount" class="block text-sm font-medium text-gray-700 mb-2">Payment Amount (PKR)</label>
                    <input type="number" 
                           id="payment-amount" 
                           name="amount"
                           step="0.01" 
                           min="0.01" 
                           required
                           class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="0.00"
                           onkeyup="calculatePaymentRemaining()"
                           oninput="calculatePaymentRemaining()">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Remaining After Payment</label>
                    <div class="text-lg font-semibold" id="payment-remaining">
                        <span class="text-red-600">PKR 0.00</span>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="payment-comment" class="block text-sm font-medium text-gray-700 mb-2">Comment <span class="text-red-500">*</span></label>
                    <textarea id="payment-comment" 
                              name="comment"
                              rows="3"
                              required
                              minlength="1"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                              placeholder="Add a comment about this payment..."></textarea>
                </div>
                
                <div class="flex space-x-3">
                    <button type="button" onclick="closePaymentModal()" 
                            class="flex-1 px-4 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg font-semibold transition">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="flex-1 px-4 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold transition">
                        Pay Amount
                    </button>
                </div>
            </form>
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

        // Payment Modal Functions
        let currentPaymentData = {};
        
        function openPaymentModal(saleId, currentBill, previousBalance, totalPayable, paidAmount, balance, saleNumber) {
            currentPaymentData = {
                saleId: saleId,
                orderId: saleId,
                currentBill: parseFloat(currentBill) || 0,
                previousBalance: parseFloat(previousBalance) || 0,
                totalAmount: parseFloat(totalPayable) || 0,
                paidAmount: parseFloat(paidAmount) || 0,
                balance: parseFloat(balance) || 0,
                saleNumber: saleNumber
            };
            
            document.getElementById('payment-sale-id').value = saleId;
            document.getElementById('payment-sale-number').textContent = saleNumber;
            document.getElementById('payment-current-bill').textContent = 'PKR ' + parseFloat(currentBill).toFixed(2);
            document.getElementById('payment-previous-balance').textContent = 'PKR ' + parseFloat(previousBalance).toFixed(2);
            document.getElementById('payment-total-amount').textContent = 'PKR ' + parseFloat(totalPayable).toFixed(2);
            document.getElementById('payment-paid-amount').textContent = 'PKR ' + parseFloat(paidAmount).toFixed(2);
            document.getElementById('payment-balance').textContent = 'PKR ' + parseFloat(balance).toFixed(2);
            document.getElementById('payment-amount').value = balance.toFixed(2);
            // Remove max limit to allow paying for old bills together
            document.getElementById('payment-amount').removeAttribute('max');
            
            calculatePaymentRemaining();
            document.getElementById('payment-modal').classList.remove('hidden');
            document.getElementById('payment-amount').focus();
            document.getElementById('payment-amount').select();
        }
        
        function closePaymentModal() {
            document.getElementById('payment-modal').classList.add('hidden');
            currentPaymentData = {};
        }
        
        function calculatePaymentRemaining() {
            const paymentAmount = parseFloat(document.getElementById('payment-amount').value) || 0;
            const balance = currentPaymentData.balance || 0;
            const remaining = balance - paymentAmount;
            
            const remainingDisplay = document.getElementById('payment-remaining');
            if (remaining <= 0) {
                remainingDisplay.innerHTML = '<span class="text-green-600">PKR 0.00 (Fully Paid)</span>';
                if (paymentAmount > balance) {
                    const extra = paymentAmount - balance;
                    remainingDisplay.innerHTML += '<br><span class="text-blue-600 text-sm">(Extra PKR ' + extra.toFixed(2) + ' will be applied to old bills)</span>';
                }
            } else {
                remainingDisplay.innerHTML = '<span class="text-red-600">PKR ' + remaining.toFixed(2) + '</span>';
            }
        }
        
        document.getElementById('payment-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const paymentAmount = parseFloat(formData.get('amount')) || 0;
            const balance = currentPaymentData.balance || 0;
            
            if (paymentAmount <= 0) {
                alert('Payment amount must be greater than 0');
                return;
            }
            
            // Allow payment to exceed current sale's balance - extra will go to old bills
            // Validation is now handled on the backend
            
            fetch('{{ route("sales.payment") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(async response => {
                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || 'Failed to process payment');
                }
                return data;
            })
            .then(data => {
                if (data.success) {
                    const orderId = currentPaymentData.orderId;
                    // Use modal amounts for receipt so receipt matches what user saw (Total Payable correct, Amount Paid = previous + this payment)
                    const paymentAmount = parseFloat(document.getElementById('payment-amount').value) || 0;
                    const paymentComment = (document.getElementById('payment-comment') && document.getElementById('payment-comment').value) ? document.getElementById('payment-comment').value.trim() : '';
                    const receiptAmounts = {
                        currentBill: currentPaymentData.currentBill,
                        previousBalance: currentPaymentData.previousBalance,
                        grandTotal: currentPaymentData.totalAmount,
                        totalPaidByCustomer: currentPaymentData.paidAmount + paymentAmount,
                        balance: 0,
                        comment: paymentComment,
                        currentPaymentAmount: paymentAmount
                    };
                    closePaymentModal();
                    alert('Payment processed successfully!');
                    // Print bill after alert is dismissed, passing modal amounts so receipt matches Pay Balance
                    if (orderId) {
                        setTimeout(() => {
                            printOrderBill(orderId, receiptAmounts);
                            // Delay reload to allow print dialog to open
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                        }, 100);
                    } else {
                        window.location.reload();
                    }
                } else {
                    alert('Error: ' + (data.message || 'Failed to process payment'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred: ' + error.message);
            });
        });

        // Print order bill. If modalAmounts is passed (after Pay Balance), use those so receipt matches the modal.
        async function printOrderBill(orderId, modalAmounts) {
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
                const receiptComment = (modalAmounts && modalAmounts.comment && String(modalAmounts.comment).trim()) ? String(modalAmounts.comment).trim() : '';
                const commentHtml = receiptComment ? `<div style="margin-top: 4px; font-size: 9px;"><span>Comment:</span><span>${receiptComment.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;')}</span></div>` : '';
                
                let printContent = `
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <style>
                            @media print { 
                                @page { margin: 5mm; }
                                * { color: #000 !important; }
                            }
                            body { font-family: 'Arial', sans-serif; padding: 8px; max-width: 58mm; margin: 0 auto; font-size: 10px; }
                            * { color: #000 !important; }
                            .header { text-align: center; margin-bottom: 8px; border-bottom: 1px solid #000; padding-bottom: 6px; }
                            .header h2 { margin: 0; font-size: 14px; font-weight: bold; }
                            .header p { margin: 4px 0 0 0; font-size: 9px; }
                            .business-info {
                                padding-top: 6px;
                                font-size: 8px;
                            }
                            .business-service {
                                font-weight: bold;
                                margin-bottom: 4px;
                                font-size: 8px;
                            }
                            .business-service i {
                                font-style: italic;
                            }
                            .business-contact {
                                display: flex;
                                justify-content: space-between;
                                align-items: flex-start;
                                margin-top: 4px;
                                font-size: 7px;
                            }
                            .business-contact-left {
                                text-align: left;
                            }
                            .business-contact-right {
                                text-align: right;
                            }
                            .order-info { margin-bottom: 8px; font-size: 9px; }
                            .order-info div { display: flex; justify-content: space-between; margin-bottom: 2px; }
                            table { width: 100%; border-collapse: collapse; margin-bottom: 8px; font-size: 9px; }
                            th, td { padding: 3px 1px; text-align: left; border-bottom: 1px solid #ddd; }
                            th { font-weight: bold; border-bottom: 1px solid #000; }
                            td:nth-child(2), td:nth-child(3), td:nth-child(4) { text-align: right; }
                            .total-section { text-align: right; font-weight: bold; font-size: 11px; margin-top: 8px; padding-top: 6px; border-top: 1px solid #000; }
                            .footer { text-align: center; margin-top: 10px; padding-top: 8px; border-top: 1px solid #000; font-size: 8px; }
                        </style>
                    </head>
                    <body>
${(window.FTReceipt && window.FTReceipt.headerHtml) ? window.FTReceipt.headerHtml('Order Receipt') : ''}
                        <div class="order-info">
                            <div><span>Sale Number:</span><span><strong>${saleNumber}</strong></span></div>
                            <div><span>Date:</span><span>${formattedDateTime}</span></div>
                            <div><span>Customer:</span><span>${customerName}</span></div>
                            <div><span>Payment:</span><span>${paymentMethod}</span></div>
                            ${commentHtml}
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
                // Use modal amounts when provided (after Pay Balance) so receipt matches what user saw
                let previousBalance, grandTotal, totalPaidByCustomer, balance;
                if (modalAmounts && typeof modalAmounts.previousBalance === 'number') {
                    previousBalance = modalAmounts.previousBalance;
                    grandTotal = modalAmounts.grandTotal;
                    totalPaidByCustomer = modalAmounts.totalPaidByCustomer;
                } else {
                    previousBalance = parseFloat(order.previous_balance || 0);
                    grandTotal = currentSaleTotal + previousBalance;
                    const paidAmount = parseFloat(order.paid_amount || 0);
                    const adjPaidAmount = parseFloat(order.adj_paid_amount || 0);
                    totalPaidByCustomer = parseFloat(order.total_paid_by_customer ?? (paidAmount + adjPaidAmount));
                }
                // Always derive Amount Remaining from Total Payable - Amount Paid so receipt is never wrong
                balance = Math.max(0, grandTotal - totalPaidByCustomer);
                const amountPaidOnReceipt = (modalAmounts && typeof modalAmounts.currentPaymentAmount === 'number') ? modalAmounts.currentPaymentAmount : totalPaidByCustomer;
                printContent += `
                            </tbody>
                        </table>
                        <div class="total-section">
                            <p style="font-size: 10px; margin-bottom: 3px; display: flex; justify-content: space-between;"><span>Subtotal (Current Bill):</span><span>PKR ${currentSaleTotal.toFixed(2)}</span></p>
                            <p style="font-size: 10px; margin-bottom: 3px; display: flex; justify-content: space-between;"><span>Remaining Previous Balance:</span><span>PKR ${previousBalance.toFixed(2)}</span></p>
                            <p style="border-top: 1px solid #ddd; margin: 5px 0; padding-top: 5px;"></p>
                            <p style="font-size: 12px; margin-bottom: 3px; display: flex; justify-content: space-between; font-weight: bold;"><span>Total Payable:</span><span>PKR ${grandTotal.toFixed(2)}</span></p>
                            <p style="font-size: 10px; margin-bottom: 3px; display: flex; justify-content: space-between;"><span>Amount Paid:</span><span>PKR ${amountPaidOnReceipt.toFixed(2)}</span></p>
                            <p style="border-top: 1px solid #ddd; margin: 5px 0; padding-top: 5px;"></p>
                            <p style="font-size: 12px; margin-top: 5px; display: flex; justify-content: space-between; font-weight: bold; color: ${balance > 0 ? '#000' : '#22c55e'}"><span>Amount Remaining:</span><span>PKR ${Math.max(0, balance).toFixed(2)}</span></p>
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



