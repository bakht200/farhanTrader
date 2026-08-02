<x-app-layout>
    <x-slot name="header">
        Orders
    </x-slot>

    <!-- Breadcrumb -->
    <div class="mb-4">
        <nav class="text-sm text-gray-600">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-900">Dashboard</a>
            <span class="mx-2">></span>
            <span class="text-gray-900 font-medium">Orders</span>
        </nav>
    </div>

    <!-- Quick Links -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <a href="{{ route('orders.completed') }}" class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition">
            <div class="flex items-center">
                <div class="bg-green-100 rounded-full p-3 mr-4">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Completed Orders</h3>
                    <p class="text-sm text-gray-500">View all completed orders</p>
                </div>
            </div>
        </a>
        <a href="{{ route('orders.pending') }}" class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition">
            <div class="flex items-center">
                <div class="bg-yellow-100 rounded-full p-3 mr-4">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Pending Orders</h3>
                    <p class="text-sm text-gray-500">View pending order amounts</p>
                </div>
            </div>
        </a>
        <a href="{{ route('orders.on-hold') }}" class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition">
            <div class="flex items-center">
                <div class="bg-orange-100 rounded-full p-3 mr-4">
                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">On-Hold Orders</h3>
                    <p class="text-sm text-gray-500">View orders on hold</p>
                </div>
            </div>
        </a>
    </div>

    <!-- Search and Filters -->
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <form method="GET" action="{{ route('orders.index') }}" class="flex flex-col md:flex-row md:items-center gap-4" id="search-form">
            <!-- Search -->
            <div class="flex-1">
                <div class="relative">
                    <input type="text" 
                           id="search-input"
                           name="search" 
                           value="{{ request('search') }}" 
                           placeholder="Search by order number, customer name..." 
                           class="w-full px-4 py-2 pl-10 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
                           oninput="handleSearchInput()">
                    <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
            </div>

            <!-- Status Filter -->
            <div>
                <select name="status" onchange="this.form.submit()" class="px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                    <option value="all" {{ request('status') === 'all' || !request('status') ? 'selected' : '' }}>All Status</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="on_hold" {{ request('status') == 'on_hold' ? 'selected' : '' }}>On Hold</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>

            <!-- Payment Status Filter -->
            <div>
                <select name="payment_status" onchange="this.form.submit()" class="px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                    <option value="all" {{ request('payment_status') === 'all' || !request('payment_status') ? 'selected' : '' }}>All Payment</option>
                    <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="partial" {{ request('payment_status') == 'partial' ? 'selected' : '' }}>Partial</option>
                    <option value="pending" {{ request('payment_status') == 'pending' ? 'selected' : '' }}>Pending</option>
                </select>
            </div>

            <!-- Category Filter -->
            <div>
                <select name="category_id" onchange="this.form.submit()" class="px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                    <option value="all" {{ request('category_id') === 'all' || !request('category_id') ? 'selected' : '' }}>All Categories</option>
                    @foreach($categories ?? [] as $category)
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
            @if(request('search') || request('status') !== 'all' || request('payment_status') !== 'all' || request('category_id') !== 'all' || request('start_date') || request('end_date'))
                <a href="{{ route('orders.index') }}" class="px-4 py-2 text-gray-600 hover:text-gray-900">
                    Clear
                </a>
            @endif
        </form>
    </div>

    <!-- Orders Table -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="p-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold">All Orders</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order Number</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date & Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paid</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Balance</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($orders as $order)
                    @php
                        $paidAmount = $order->paid_amount ?? 0;
                        $previousBalance = $order->previous_balance ?? 0;
                        $grandTotal = $order->total_amount + $previousBalance;
                        $balance = $grandTotal - $paidAmount;
                        $saleNumber = $order->sale_number ?? $order->order_number ?? 'N/A';
                    @endphp
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $saleNumber }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $order->customer->name ?? 'Walk-in Customer' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-900">{{ ($order->created_at ?? $order->sale_date ?? $order->order_date ?? now())->format('Y-m-d h:i A') }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">PKR {{ number_format($order->total_amount, 2) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-green-600">PKR {{ number_format($paidAmount, 2) }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($balance > 0)
                                <span class="text-red-600 font-semibold">PKR {{ number_format($balance, 2) }}</span>
                            @else
                                <span class="text-green-600">PKR 0.00</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                // Check if it's a draft order with "Held Order" in notes (should show as On Hold)
                                $isOnHold = $order->status === 'on_hold' || ($order->status === 'draft' && str_contains($order->notes ?? '', 'Held Order'));
                            @endphp
                            @if(isset($order->payment_status))
                                @if($order->payment_status == 'paid')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Paid</span>
                                @elseif($order->payment_status == 'partial')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Partial</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Pending</span>
                                @endif
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    {{ $order->status === 'completed' ? 'bg-green-100 text-green-800' : 
                                       ($order->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                       ($isOnHold ? 'bg-orange-100 text-orange-800' : 'bg-gray-100 text-gray-800')) }}">
                                    {{ $isOnHold ? 'On Hold' : ucfirst($order->status) }}
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
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
                                @if($balance > 0 && isset($order->id))
                                    <button onclick="openPaymentModal({{ $order->id }}, {{ $grandTotal }}, {{ $paidAmount }}, {{ $balance }}, '{{ $saleNumber }}')" 
                                            class="text-green-600 hover:text-green-900" title="Pay Balance">
                                        Pay
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="px-6 py-4 text-center text-gray-500">No orders found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
            <div class="flex items-center">
                <span class="text-sm text-gray-700">Row Per Page</span>
                <form method="GET" action="{{ route('orders.index') }}" class="ml-2">
                    @if(request('search'))
                        <input type="hidden" name="search" value="{{ request('search') }}">
                    @endif
                    @if(request('status'))
                        <input type="hidden" name="status" value="{{ request('status') }}">
                    @endif
                    @if(request('payment_status'))
                        <input type="hidden" name="payment_status" value="{{ request('payment_status') }}">
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
                        <option value="10" {{ request('per_page', 15) == 10 ? 'selected' : '' }}>10 Entries</option>
                        <option value="15" {{ request('per_page', 15) == 15 ? 'selected' : '' }}>15 Entries</option>
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
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Total Amount</label>
                    <div class="text-lg font-semibold text-gray-900" id="payment-total-amount">PKR 0.00</div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Already Paid</label>
                    <div class="text-md text-green-600" id="payment-paid-amount">PKR 0.00</div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Remaining Balance</label>
                    <div class="text-lg font-semibold text-red-600" id="payment-balance">PKR 0.00</div>
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
        let currentPaymentData = {};
        
        function openPaymentModal(saleId, totalAmount, paidAmount, balance, saleNumber) {
            currentPaymentData = {
                saleId: saleId,
                totalAmount: totalAmount,
                paidAmount: paidAmount,
                balance: balance,
                saleNumber: saleNumber
            };
            
            document.getElementById('payment-sale-id').value = saleId;
            document.getElementById('payment-sale-number').textContent = saleNumber;
            document.getElementById('payment-total-amount').textContent = 'PKR ' + totalAmount.toFixed(2);
            document.getElementById('payment-paid-amount').textContent = 'PKR ' + paidAmount.toFixed(2);
            document.getElementById('payment-balance').textContent = 'PKR ' + balance.toFixed(2);
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
                    alert('Payment processed successfully!');
                    closePaymentModal();
                    window.location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to process payment'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred: ' + error.message);
            });
        });
    </script>
</x-app-layout>


