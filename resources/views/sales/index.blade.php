<x-app-layout>
    <x-slot name="header">Sales</x-slot>
    
    <div class="mb-4">
        <nav class="text-sm text-gray-600">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-900">Dashboard</a>
            <span class="mx-2">></span>
            <span class="text-gray-900 font-medium">Sales</span>
        </nav>
    </div>

    <!-- Search and Filters -->
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <form method="GET" action="{{ route('sales.index') }}" class="flex flex-col md:flex-row md:items-center gap-4" id="search-form">
            <!-- Search -->
            <div class="flex-1">
                <div class="relative">
                    <input type="text" 
                           id="search-input"
                           name="search" 
                           value="{{ request('search') }}" 
                           placeholder="Q Search by sale number, customer name..." 
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
                <a href="{{ route('sales.index') }}" class="px-4 py-2 text-gray-600 hover:text-gray-900">
                    Clear
                </a>
            @endif
        </form>
    </div>

    <div class="bg-white shadow-sm rounded-lg p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">All Sales</h3>
            <div class="flex items-center gap-3">
                <button onclick="printSalesReport()" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md font-medium inline-flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                    </svg>
                    Print Report
                </button>
                <a href="{{ route('sales.create') }}" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-md">Create Sale</a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sale Number</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paid</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Balance</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($sales ?? [] as $sale)
                    @php
                        $paidAmount = $sale->paid_amount ?? 0;
                        $previousBalance = $sale->previous_balance ?? 0;
                        $grandTotal = $sale->total_amount + $previousBalance;
                        $balance = (float) ($sale->remaining_balance_due ?? max(0, $grandTotal - $paidAmount));
                        // Calculate current bill pending and remaining
                        $currentBill = $sale->total_amount;
                        $paidTowardsCurrentBill = max(0, $paidAmount - $previousBalance);
                        $currentBillPending = max(0, $currentBill - $paidTowardsCurrentBill);
                        $currentBillRemaining = max(0, $currentBill - $paidAmount);
                        $hasAdjBill = isset($sale->adj_bill_number);
                    @endphp
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ $sale->sale_number }}
                            @if($hasAdjBill)
                                <div class="mt-1">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800" title="Includes adjustment payment: {{ $sale->adj_bill_number }}">
                                        + ADJ
                                    </span>
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $sale->customer->name ?? 'Walk-in' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm">{{ $sale->created_at->format('Y-m-d h:i A') }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">PKR {{ number_format($sale->total_amount, 2) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-green-600">PKR {{ number_format($paidAmount, 2) }}</span>
                            @if($hasAdjBill)
                                <div class="text-xs text-gray-500 mt-1">
                                    (incl. ADJ: {{ number_format($sale->adj_paid_amount ?? 0, 2) }})
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($balance > 0)
                                <span class="text-red-600 font-semibold">PKR {{ number_format($balance, 2) }}</span>
                            @else
                                <span class="text-green-600">PKR 0.00</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($sale->payment_status == 'paid')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Paid</span>
                            @elseif($sale->payment_status == 'partial')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Partial</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Pending</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center space-x-3">
                                <a href="{{ route('sales.show', $sale) }}" class="text-blue-600 hover:text-blue-900" title="View">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </a>
                                <button onclick="printSaleInvoice({{ $sale->id }})" class="text-orange-600 hover:text-orange-900" title="Print Invoice">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                                    </svg>
                                </button>
                                @if($balance > 0)
                                    <button onclick="openPaymentModal({{ $sale->id }}, {{ $currentBill }}, {{ $currentBillPending }}, {{ $currentBillRemaining }}, {{ $grandTotal }}, {{ $paidAmount }}, {{ $balance }}, '{{ $sale->sale_number }}')" 
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
                    <tr><td colspan="8" class="px-6 py-4 text-center text-gray-500">No sales found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($sales->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
            <div class="flex items-center">
                <span class="text-sm text-gray-700">Row Per Page</span>
                <form method="GET" action="{{ route('sales.index') }}" class="ml-2">
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
                        <option value="15" {{ request('per_page', 15) == 15 ? 'selected' : '' }}>15 Entries</option>
                        <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25 Entries</option>
                        <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50 Entries</option>
                        <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100 Entries</option>
                    </select>
                </form>
                <span class="text-sm text-gray-700 ml-4">Showing {{ $sales->firstItem() ?? 0 }} to {{ $sales->lastItem() ?? 0 }} of {{ $sales->total() }} results</span>
            </div>
            <div class="flex items-center space-x-2">
                @if($sales->hasPages())
                    <div class="flex items-center space-x-1">
                        @if($sales->onFirstPage())
                            <span class="px-3 py-1 text-gray-400 cursor-not-allowed">&lt;</span>
                        @else
                            <a href="{{ $sales->previousPageUrl() }}" class="px-3 py-1 text-gray-700 hover:text-gray-900">&lt;</a>
                        @endif
                        
                        @foreach($sales->getUrlRange(1, min(5, $sales->lastPage())) as $page => $url)
                            @if($page == $sales->currentPage())
                                <span class="px-3 py-1 bg-orange-500 text-white rounded">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="px-3 py-1 text-gray-700 hover:text-gray-900">{{ $page }}</a>
                            @endif
                        @endforeach
                        
                        @if($sales->hasMorePages())
                            <span class="px-2 py-1 text-gray-500">...</span>
                            <a href="{{ $sales->url($sales->lastPage()) }}" class="px-3 py-1 text-gray-700 hover:text-gray-900">{{ $sales->lastPage() }}</a>
                        @endif
                        
                        @if($sales->hasMorePages())
                            <a href="{{ $sales->nextPageUrl() }}" class="px-3 py-1 text-gray-700 hover:text-gray-900">&gt;</a>
                        @else
                            <span class="px-3 py-1 text-gray-400 cursor-not-allowed">&gt;</span>
                        @endif
                    </div>
                @endif
            </div>
        </div>
        @endif
    </div>

    <!-- Payment Modal -->
    <div id="payment-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-2xl max-w-xl w-full">
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
                
                <!-- Error message display -->
                <div id="payment-error-message" class="hidden mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded"></div>
                
                <div class="flex gap-8 mb-6">
                    <!-- Left Column -->
                    <div class="flex-1 space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sale Number</label>
                            <div class="text-base font-semibold text-gray-900" id="payment-sale-number"></div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Current Bill</label>
                            <div class="text-base font-semibold text-gray-900" id="payment-current-bill">PKR 0.00</div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Current Bill Pending</label>
                            <div class="text-base font-semibold text-orange-600" id="payment-current-bill-pending">PKR 0.00</div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Current Bill Remaining</label>
                            <div class="text-base font-semibold text-red-600" id="payment-current-bill-remaining">PKR 0.00</div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Total Amount</label>
                            <div class="text-base font-semibold text-gray-900" id="payment-total-amount">PKR 0.00</div>
                        </div>
                    </div>
                    
                    <!-- Divider -->
                    <div class="w-px bg-gray-300"></div>
                    
                    <!-- Right Column -->
                    <div class="flex-1 space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Already Paid</label>
                            <div class="text-base text-green-600" id="payment-paid-amount">PKR 0.00</div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Remaining Balance</label>
                            <div class="text-base font-semibold text-red-600" id="payment-balance">PKR 0.00</div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Remaining After Payment</label>
                            <div class="text-base font-semibold" id="payment-remaining">
                                <span class="text-red-600">PKR 0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Payment Input Section - Full Width -->
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
                
                <!-- Comment Section - Full Width -->
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
        // Format date time: 2026-02-01 05:23 PM
        function formatDateTime(dateString) {
            if (!dateString) return '';
            // If already formatted (matches pattern Y-m-d h:i A), return as is
            if (typeof dateString === 'string' && dateString.match(/^\d{4}-\d{2}-\d{2} \d{2}:\d{2} (AM|PM)$/)) {
                return dateString;
            }
            const dt = new Date(dateString);
            // Check if date is valid
            if (isNaN(dt.getTime())) {
                return dateString; // Return original if invalid
            }
            const year = dt.getFullYear();
            const month = String(dt.getMonth() + 1).padStart(2, '0');
            const day = String(dt.getDate()).padStart(2, '0');
            let hours = dt.getHours();
            const minutes = String(dt.getMinutes()).padStart(2, '0');
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? hours : 12; // the hour '0' should be '12'
            const formattedHours = String(hours).padStart(2, '0');
            return `${year}-${month}-${day} ${formattedHours}:${minutes} ${ampm}`;
        }

        // Print sale invoice receipt
        function printSaleInvoice(saleId) {
            fetch(`/sales/${saleId}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Failed to fetch sale data');
                }
                return response.json();
            })
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Failed to load sale data');
                }
                
                const sale = data.sale;
                const printWindow = window.open('', '_blank');
                
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
                            body { 
                                font-family: 'Arial', sans-serif; 
                                padding: 8px; 
                                max-width: 58mm; 
                                margin: 0 auto; 
                                font-size: 10px;
                            }
                            .header { 
                                text-align: center; 
                                margin-bottom: 8px; 
                                border-bottom: 1px solid #000; 
                                padding-bottom: 6px; 
                            }
                            .header h2 { 
                                margin: 0; 
                                font-size: 14px; 
                                font-weight: bold;
                            }
                            .header p {
                                margin: 4px 0 0 0;
                                font-size: 9px;
                            }
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
                            .order-info { 
                                margin-bottom: 8px; 
                                font-size: 9px;
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
                                font-size: 9px;
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
                                font-size: 8px;
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
                                <span><strong>${sale.sale_number || ''}</strong></span>
                            </div>
                            <div>
                                <span>Date:</span>
                                <span>${sale.sale_date ? formatDateTime(sale.sale_date) : (sale.created_at ? formatDateTime(sale.created_at) : '')}</span>
                            </div>
                            <div>
                                <span>Customer:</span>
                                <span>${sale.customer_name || 'Walk-in Customer'}</span>
                            </div>
                            <div>
                                <span>Payment:</span>
                                <span>${(sale.payment_method || 'Cash').charAt(0).toUpperCase() + (sale.payment_method || 'Cash').slice(1)}</span>
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
                
                if (sale.items && sale.items.length > 0) {
                    sale.items.forEach(item => {
                        const unitName = item.unit_name || item.unit_short_name || 'Pcs';
                        const itemTotal = parseFloat(item.total || 0);
                        
                        printContent += `
                            <tr>
                                <td>${item.product_name || 'N/A'}</td>
                                <td style="text-align: right;">${parseFloat(item.quantity || 0).toFixed(2)} ${unitName}</td>
                                <td style="text-align: right;">PKR ${itemTotal.toFixed(2)}</td>
                            </tr>
                        `;
                    });
                }
                
                const currentSaleTotal = parseFloat(sale.total_amount || 0);
                const previousBalance = parseFloat(sale.previous_balance || 0);
                const grandTotal = currentSaleTotal + previousBalance; // Grand Total = Current Sale + Previous Balance
                const paidAmount = parseFloat(sale.paid_amount || 0);
                const regularPaidAmount = parseFloat(sale.regular_paid_amount || sale.paid_amount || 0);
                const adjPaidAmount = parseFloat(sale.adj_paid_amount || 0);
                const adjBillNumber = sale.adj_bill_number || null;
                const balance = grandTotal - paidAmount; // Remaining Balance = Grand Total - Paid
                const previousBalancePayment = parseFloat(sale.previous_balance_payment || 0);
                
                printContent += `
                            </tbody>
                        </table>
                        <div class="total-section">
                            <p style="font-size: 10px; margin-bottom: 3px; display: flex; justify-content: space-between;">
                                <span>Subtotal:</span>
                                <span>PKR ${currentSaleTotal.toFixed(2)}</span>
                            </p>
                            ${previousBalance > 0 ? '<p style="font-size: 10px; margin-bottom: 3px; display: flex; justify-content: space-between;"><span>Previous Balance:</span><span>PKR ' + previousBalance.toFixed(2) + '</span></p>' : ''}
                            <p style="border-top: 1px solid #ddd; margin: 5px 0; padding-top: 5px;"></p>
                            <p style="font-size: 12px; margin-bottom: 3px; display: flex; justify-content: space-between; font-weight: bold;">
                                <span>Total Payable:</span>
                                <span>PKR ${grandTotal.toFixed(2)}</span>
                            </p>
                            ${regularPaidAmount > 0 ? '<p style="font-size: 10px; margin-bottom: 3px; display: flex; justify-content: space-between;"><span>Amount Paid:</span><span>PKR ' + regularPaidAmount.toFixed(2) + '</span></p>' : ''}
                            ${adjPaidAmount > 0 && adjBillNumber ? '<p style="font-size: 10px; margin-bottom: 3px; display: flex; justify-content: space-between;"><span>Previous Balance Paid (' + adjBillNumber + '):</span><span>PKR ' + adjPaidAmount.toFixed(2) + '</span></p>' : ''}
                            ${paidAmount > 0 && (regularPaidAmount > 0 || adjPaidAmount > 0) ? '<p style="font-size: 10px; margin-bottom: 3px; display: flex; justify-content: space-between; font-weight: bold; border-top: 1px solid #ddd; padding-top: 3px; margin-top: 3px;"><span>Total Paid:</span><span>PKR ' + paidAmount.toFixed(2) + '</span></p>' : ''}
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
                // Fallback: open sale page and use browser print
                window.open(`/sales/${saleId}`, '_blank').print();
            });
        }
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

        // Print Sales Report
        function printSalesReport() {
            // Get current filter values from the form
            const form = document.getElementById('search-form');
            const formData = new FormData(form);
            
            // Build query string from form data
            const params = new URLSearchParams();
            for (let [key, value] of formData.entries()) {
                if (value && value !== 'all') {
                    params.append(key, value);
                }
            }

            const printWindow = window.open('', '_blank');
            
            // Show loading message
            printWindow.document.write('<html><head><title>Loading...</title></head><body><h1>Loading report...</h1></body></html>');
            printWindow.document.close();
            
            // Get CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || 
                             document.querySelector('input[name="_token"]')?.value || '';
            
            const url = `/sales/print-report` + (params.toString() ? `?${params.toString()}` : '');

            fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {})
                }
            })
            .then(async response => {
                const contentType = response.headers.get('content-type');
                if (!response.ok) {
                    const text = await response.text();
                    console.error('Error response:', text);
                    throw new Error(`HTTP ${response.status}: ${text.substring(0, 200)}`);
                }
                if (contentType && contentType.includes('application/json')) {
                    return response.json();
                } else {
                    const text = await response.text();
                    throw new Error('Expected JSON response but got: ' + contentType);
                }
            })
            .then(data => {
                if (!data) {
                    throw new Error('No data received');
                }
                if (data.success) {
                    const sales = data.sales || [];
                    const filters = data.filters || {};
                    const totals = data.totals || {};
                    
                    // Build filter description
                    let filterDesc = 'All Sales';
                    const filterParts = [];
                    if (filters.search) filterParts.push(`Search: ${filters.search}`);
                    if (filters.status && filters.status !== 'all') filterParts.push(`Status: ${filters.status}`);
                    if (filters.payment_status && filters.payment_status !== 'all') filterParts.push(`Payment: ${filters.payment_status}`);
                    if (filters.category_name) filterParts.push(`Category: ${filters.category_name}`);
                    if (filters.start_date) filterParts.push(`From: ${filters.start_date}`);
                    if (filters.end_date) filterParts.push(`To: ${filters.end_date}`);
                    if (filterParts.length > 0) {
                        filterDesc = filterParts.join(' | ');
                    }
                    
                    let printContent = `
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <title>Sales Report</title>
                            <style>
                                @media print {
                                    @page { margin: 10mm; }
                                    * { color: #000 !important; }
                                    .page-break { page-break-after: always; }
                                    .sale-row { page-break-inside: avoid; }
                                }
                                * { color: #000 !important; }
                                * {
                                    color: #000 !important;
                                }
                                body { 
                                    font-family: 'Arial', sans-serif; 
                                    padding: 20px; 
                                    font-size: 11px;
                                    color: #000 !important;
                                }
                                .header { 
                                    text-align: center; 
                                    margin-bottom: 25px; 
                                    border-bottom: 3px solid #000; 
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
                                    color: #000 !important;
                                }
                                .business-service {
                                    font-weight: bold;
                                    margin-bottom: 6px;
                                    font-size: 10px;
                                    color: #000 !important;
                                }
                                .business-contact {
                                    display: flex;
                                    justify-content: space-between;
                                    align-items: flex-start;
                                    margin-top: 6px;
                                    font-size: 9px;
                                    color: #000 !important;
                                }
                                .filter-info {
                                    background-color: #f9f9f9;
                                    padding: 10px;
                                    margin-bottom: 15px;
                                    border: 1px solid #000;
                                    font-size: 10px;
                                }
                                .summary {
                                    background-color: #f0f0f0;
                                    padding: 15px;
                                    margin-bottom: 15px;
                                    border: 1px solid #000;
                                }
                                .summary-grid {
                                    display: grid;
                                    grid-template-columns: repeat(2, 1fr);
                                    gap: 10px;
                                    font-size: 11px;
                                }
                                .summary-item {
                                    display: flex;
                                    justify-content: space-between;
                                    padding: 5px 0;
                                    border-bottom: 1px dotted #ccc;
                                }
                                table {
                                    width: 100%;
                                    border-collapse: collapse;
                                    font-size: 10px;
                                    margin-top: 10px;
                                }
                                th, td {
                                    border: 1px solid #000;
                                    padding: 4px;
                                    text-align: left;
                                }
                                th {
                                    background-color: #f0f0f0;
                                    font-weight: bold;
                                    text-align: center;
                                }
                                td:nth-child(4), td:nth-child(5), td:nth-child(6), td:nth-child(7) {
                                    text-align: right;
                                }
                                .footer {
                                    text-align: center;
                                    margin-top: 20px;
                                    padding-top: 15px;
                                    border-top: 2px solid #000;
                                    font-size: 10px;
                                    color: #000 !important;
                                }
                            </style>
                        </head>
                        <body>
                            <div class="header">
                                <h1>FARHAN TRADERS</h1>
                                <div class="business-info">
                                    <div class="business-service">
                                        Deals In Food Chemicals / Non Food Chemicals
                                    </div>
                                    <div class="business-contact">
                                        <div>
                                            <div>Ph: 091-2561301</div>
                                            <div>Mob: 0313-9829984, 0313-6777811</div>
                                        </div>
                                        <div>
                                            <div>Email: farhan.akhtar90@yahoo.com</div>
                                        </div>
                                    </div>
                                </div>
                                <p style="margin-top: 10px;">Sales Report</p>
                                <p>Generated on: ${formatDateTime(new Date().toISOString())}</p>
                            </div>
                            
                            <div class="filter-info">
                                <strong>Filters Applied:</strong> ${filterDesc}
                            </div>
                            
                            <div class="summary">
                                <div style="font-weight: bold; font-size: 14px; margin-bottom: 10px; text-align: center; border-bottom: 1px solid #000; padding-bottom: 5px;">
                                    SUMMARY
                                </div>
                                <div class="summary-grid">
                                    <div class="summary-item">
                                        <span>Total Sales:</span>
                                        <span><strong>${totals.total_sales || 0}</strong></span>
                                    </div>
                                    <div class="summary-item">
                                        <span>Total Amount:</span>
                                        <span><strong>PKR ${parseFloat(totals.total_amount || 0).toFixed(2)}</strong></span>
                                    </div>
                                    <div class="summary-item">
                                        <span>Total Paid:</span>
                                        <span><strong>PKR ${parseFloat(totals.total_paid || 0).toFixed(2)}</strong></span>
                                    </div>
                                    <div class="summary-item">
                                        <span>Total Remaining:</span>
                                        <span><strong>PKR ${parseFloat(totals.total_remaining || 0).toFixed(2)}</strong></span>
                                    </div>
                                </div>
                            </div>

                            <table>
                                <thead>
                                    <tr>
                                        <th>Sale #</th>
                                        <th>Date</th>
                                        <th>Customer</th>
                                        <th>Total</th>
                                        <th>Paid</th>
                                        <th>Remaining</th>
                                        <th>Payment Status</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                    `;
                    
                    if (sales.length === 0) {
                        printContent += `
                                    <tr>
                                        <td colspan="8" style="text-align: center; padding: 10px;">No sales found with the applied filters.</td>
                                    </tr>
                        `;
                    } else {
                        sales.forEach(sale => {
                            printContent += `
                                        <tr class="sale-row">
                                            <td>${sale.sale_number || ''}</td>
                                            <td>${sale.sale_date || ''}</td>
                                            <td>${sale.customer_name || 'Walk-in'}</td>
                                            <td>PKR ${parseFloat(sale.total_amount || 0).toFixed(2)}</td>
                                            <td>PKR ${parseFloat(sale.paid_amount || 0).toFixed(2)}</td>
                                            <td>PKR ${parseFloat(sale.remaining || 0).toFixed(2)}</td>
                                            <td style="text-align: center;">${(sale.payment_status || '').toUpperCase()}</td>
                                            <td style="text-align: center;">${(sale.status || '').toUpperCase()}</td>
                                        </tr>
                            `;
                        });
                    }
                    
                    printContent += `
                                </tbody>
                            </table>
                            
                            <div class="footer">
                                <p>This is a computer-generated report.</p>
                                <p>End of Report</p>
                            </div>
                        </body>
                        </html>
                    `;
                    
                    printWindow.document.write(printContent);
                    printWindow.document.close();
                    printWindow.print();
                } else {
                    printWindow.document.write(`
                        <html>
                            <head><title>Error</title></head>
                            <body>
                                <h1 style="color: #000;">Error Loading Report</h1>
                                <p>${data.message || 'Failed to load report'}</p>
                            </body>
                        </html>
                    `);
                    printWindow.document.close();
                    alert('Error: ' + (data.message || 'Failed to load report'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const errorMessage = error.message || 'Unknown error occurred';
                if (printWindow && !printWindow.closed) {
                    printWindow.document.write(`
                        <html>
                            <head><title>Error</title></head>
                            <body>
                                <h1 style="color: #000;">Error Loading Report</h1>
                                <p>${errorMessage}</p>
                                <p>Please check the browser console for more details.</p>
                            </body>
                        </html>
                    `);
                    printWindow.document.close();
                }
                alert('Error loading report: ' + errorMessage);
            });
        }

        let currentPaymentData = {};
        
        function openPaymentModal(saleId, currentBill, currentBillPending, currentBillRemaining, totalAmount, paidAmount, balance, saleNumber) {
            currentPaymentData = {
                saleId: saleId,
                currentBill: currentBill,
                currentBillPending: currentBillPending,
                currentBillRemaining: currentBillRemaining,
                totalAmount: totalAmount,
                paidAmount: paidAmount,
                balance: balance,
                saleNumber: saleNumber
            };
            
            // Hide error message
            document.getElementById('payment-error-message').classList.add('hidden');
            
            document.getElementById('payment-sale-id').value = saleId;
            document.getElementById('payment-sale-number').textContent = saleNumber;
            document.getElementById('payment-current-bill').textContent = 'PKR ' + currentBill.toFixed(2);
            document.getElementById('payment-current-bill-pending').textContent = 'PKR ' + currentBillPending.toFixed(2);
            document.getElementById('payment-current-bill-remaining').textContent = 'PKR ' + currentBillRemaining.toFixed(2);
            document.getElementById('payment-total-amount').textContent = 'PKR ' + totalAmount.toFixed(2);
            document.getElementById('payment-paid-amount').textContent = 'PKR ' + paidAmount.toFixed(2);
            document.getElementById('payment-balance').textContent = 'PKR ' + balance.toFixed(2);
            // Set default payment amount to Current Bill Remaining instead of total Remaining Balance
            document.getElementById('payment-amount').value = currentBillRemaining.toFixed(2);
            // Remove max limit to allow paying for old bills together
            document.getElementById('payment-amount').removeAttribute('max');
            
            calculatePaymentRemaining();
            document.getElementById('payment-modal').classList.remove('hidden');
            document.getElementById('payment-amount').focus();
            document.getElementById('payment-amount').select();
        }
        
        function closePaymentModal() {
            document.getElementById('payment-modal').classList.add('hidden');
            document.getElementById('payment-error-message').classList.add('hidden');
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
                    // Show error in modal
                    const errorMsg = document.getElementById('payment-error-message');
                    errorMsg.textContent = 'An error occurred: ' + (data.message || 'Failed to process payment');
                    errorMsg.classList.remove('hidden');
                    errorMsg.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
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
                    const errorMsg = document.getElementById('payment-error-message');
                    errorMsg.textContent = 'Error: ' + (data.message || 'Failed to process payment');
                    errorMsg.classList.remove('hidden');
                    errorMsg.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const errorMsg = document.getElementById('payment-error-message');
                errorMsg.textContent = 'An error occurred: ' + error.message;
                errorMsg.classList.remove('hidden');
                errorMsg.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            });
        });
    </script>
</x-app-layout>



