<x-app-layout>
    <x-slot name="header">
        Customer
    </x-slot>

    <!-- Breadcrumb -->
    <div class="mb-4">
        <nav class="text-sm text-gray-600">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-900">Dashboard</a>
            <span class="mx-2">></span>
            <span class="text-gray-900 font-medium">Customer</span>
        </nav>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Price</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2">PKR {{ number_format($grandTotalPrice ?? 0, 2) }}</p>
                </div>
                <div class="bg-blue-100 rounded-full p-3">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Paid</p>
                    <p class="text-2xl font-bold text-green-600 mt-2">PKR {{ number_format($grandPaidAmount ?? 0, 2) }}</p>
                </div>
                <div class="bg-green-100 rounded-full p-3">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Remaining</p>
                    <p class="text-2xl font-bold {{ ($grandRemaining ?? 0) > 0 ? 'text-red-600' : 'text-green-600' }} mt-2">PKR {{ number_format($grandRemaining ?? 0, 2) }}</p>
                </div>
                <div class="rounded-full p-3 {{ ($grandRemaining ?? 0) > 0 ? 'bg-red-100' : 'bg-green-100' }}">
                    <svg class="w-6 h-6 {{ ($grandRemaining ?? 0) > 0 ? 'text-red-600' : 'text-green-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Balance</p>
                    <p class="text-2xl font-bold {{ ($grandRemaining ?? 0) > 0 ? 'text-red-600' : 'text-green-600' }} mt-2">PKR {{ number_format($grandRemaining ?? 0, 2) }}</p>
                </div>
                <div class="rounded-full p-3 {{ ($grandRemaining ?? 0) > 0 ? 'bg-red-100' : 'bg-green-100' }}">
                    <svg class="w-6 h-6 {{ ($grandRemaining ?? 0) > 0 ? 'text-red-600' : 'text-green-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Search & customer type filter -->
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <form method="GET" action="{{ route('customers.index') }}" class="flex flex-col sm:flex-row sm:items-center gap-4" id="search-form">
            @if(request('per_page'))
                <input type="hidden" name="per_page" value="{{ request('per_page') }}">
            @endif
            <div class="flex-1 relative min-w-0">
                <input type="text" 
                       id="search-input"
                       name="search" 
                       value="{{ request('search') }}" 
                       placeholder="Q Search" 
                       class="w-full px-4 py-2 pl-10 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
                       oninput="handleSearchInput()">
                <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <div class="flex items-center gap-2 sm:flex-shrink-0">
                <label for="customer-type-filter" class="text-sm text-gray-600 whitespace-nowrap">Customer type</label>
                <select id="customer-type-filter"
                        name="customer_type"
                        onchange="document.getElementById('search-form').submit()"
                        class="min-w-[10rem] px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-orange-500 focus:border-orange-500">
                    <option value="">All types</option>
                    <option value="__none__" {{ request('customer_type') === '__none__' ? 'selected' : '' }}>No type</option>
                    @foreach($customerTypesForFilter as $type)
                        <option value="{{ $type }}" {{ request('customer_type') === $type ? 'selected' : '' }}>{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            @if(request('search') || request()->filled('customer_type'))
                <a href="{{ route('customers.index', array_filter(['per_page' => request('per_page')])) }}" class="px-4 py-2 text-gray-600 hover:text-gray-900 whitespace-nowrap">
                    Clear filters
                </a>
            @endif
        </form>
    </div>

    <!-- Customer Table -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="p-4 border-b border-gray-200 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <h3 class="text-lg font-semibold">All Customers</h3>
            <div class="flex flex-col md:flex-row md:items-center gap-3">
                <div class="flex items-center gap-2">
                    <div class="flex flex-col">
                        <label for="customer-from-date" class="text-xs text-gray-600">From Date</label>
                        <input id="customer-from-date" type="date" class="px-2 py-1 border border-gray-300 rounded-md text-sm">
                    </div>
                    <div class="flex flex-col">
                        <label for="customer-to-date" class="text-xs text-gray-600">To Date</label>
                        <input id="customer-to-date" type="date" class="px-2 py-1 border border-gray-300 rounded-md text-sm">
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button onclick="printAllCustomersReport()" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md font-medium inline-flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                        </svg>
                        Detailed Report
                    </button>
                    <a href="{{ route('customers.create') }}" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-md">
                        Add Customer
                    </a>
                </div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left">
                            <input type="checkbox" class="rounded border-gray-300 text-orange-600 focus:ring-orange-500" id="select-all">
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer Id</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sale Number</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($customers as $customer)
                    <tr class="hover:bg-gray-50 {{ isset($customer->is_walk_in) && $customer->is_walk_in ? 'bg-gray-50' : '' }}">
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if(isset($customer->is_walk_in) && $customer->is_walk_in)
                                <input type="checkbox" class="customer-checkbox rounded border-gray-300 text-orange-600 focus:ring-orange-500" value="" disabled>
                            @else
                                <input type="checkbox" class="customer-checkbox rounded border-gray-300 text-orange-600 focus:ring-orange-500" value="{{ $customer->id }}">
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-medium text-gray-900">{{ $customer->customer_id ?? 'CN-' . str_pad($customer->id, 3, '0', STR_PAD_LEFT) }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-900">{{ $customer->name }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if(isset($customer->is_walk_in) && $customer->is_walk_in)
                                <span class="text-sm text-gray-400">—</span>
                            @else
                                <span class="text-sm text-gray-700">{{ $customer->customer_type ? $customer->customer_type : '—' }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($customer->latest_order)
                                <span class="text-sm text-gray-900">{{ $customer->latest_order->sale_number }}</span>
                            @else
                                <span class="text-sm text-gray-400">No sales</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm">
                                <div class="font-medium text-gray-900">PKR {{ number_format($customer->total_price ?? 0, 2) }}</div>
                                @if(($customer->unpaid_amount ?? 0) > 0)
                                    <div class="text-xs text-red-600">Balance: PKR {{ number_format($customer->unpaid_amount, 2) }}</div>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $hasUnpaid = ($customer->unpaid_amount ?? 0) > 0;
                            @endphp
                            @if($hasUnpaid)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    <span class="w-1.5 h-1.5 mr-1.5 bg-red-500 rounded-full"></span>
                                    Un paid
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <span class="w-1.5 h-1.5 mr-1.5 bg-green-500 rounded-full"></span>
                                    Paid
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center space-x-3">
                                @if(isset($customer->is_walk_in) && $customer->is_walk_in)
                                    <span class="text-gray-400" title="Walk-in Customer - View sales in Sales section">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </span>
                                @else
                                    <button onclick="openPreviousBalanceModal({{ $customer->id }}, '{{ $customer->name }}')" class="text-green-600 hover:text-green-900" title="Add Previous Balance">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                    </button>
                                    <a href="{{ route('customers.show', $customer) }}" class="text-blue-600 hover:text-blue-900" title="View">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </a>
                                    <form action="{{ route('customers.destroy', $customer) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this customer?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900" title="Delete">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                            No customers found.
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
                <form method="GET" action="{{ route('customers.index') }}" class="ml-2">
                    @if(request('search'))
                        <input type="hidden" name="search" value="{{ request('search') }}">
                    @endif
                    @if(request()->filled('customer_type'))
                        <input type="hidden" name="customer_type" value="{{ request('customer_type') }}">
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
                @if($customers->hasPages())
                    <div class="flex items-center space-x-1">
                        @if($customers->onFirstPage())
                            <span class="px-3 py-1 text-gray-400 cursor-not-allowed">&lt;</span>
                        @else
                            <a href="{{ $customers->previousPageUrl() }}" class="px-3 py-1 text-gray-700 hover:text-gray-900">&lt;</a>
                        @endif
                        
                        @foreach($customers->getUrlRange(1, min(5, $customers->lastPage())) as $page => $url)
                            @if($page == $customers->currentPage())
                                <span class="px-3 py-1 bg-orange-500 text-white rounded">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="px-3 py-1 text-gray-700 hover:text-gray-900">{{ $page }}</a>
                            @endif
                        @endforeach
                        
                        @if($customers->hasMorePages())
                            <span class="px-2 py-1 text-gray-500">...</span>
                            <a href="{{ $customers->url($customers->lastPage()) }}" class="px-3 py-1 text-gray-700 hover:text-gray-900">{{ $customers->lastPage() }}</a>
                        @endif
                        
                        @if($customers->hasMorePages())
                            <a href="{{ $customers->nextPageUrl() }}" class="px-3 py-1 text-gray-700 hover:text-gray-900">&gt;</a>
                        @else
                            <span class="px-3 py-1 text-gray-400 cursor-not-allowed">&gt;</span>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Previous Balance Modal -->
    <div id="previousBalanceModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Add Previous Balance</h3>
                    <button onclick="closePreviousBalanceModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="mb-4">
                    <p class="text-sm text-gray-600">Customer: <span id="modalCustomerName" class="font-semibold text-gray-900"></span></p>
                </div>
                <form id="previousBalanceForm">
                    @csrf
                    <input type="hidden" id="modalCustomerId" name="customer_id">
                    <div class="mb-4">
                        <label for="previousBalanceAmount" class="block text-sm font-medium text-gray-700 mb-2">Amount (PKR)</label>
                        <input type="number" 
                               id="previousBalanceAmount" 
                               name="amount" 
                               step="0.01" 
                               min="0.01" 
                               required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
                               placeholder="Enter amount">
                    </div>
                    <div class="flex items-center justify-end space-x-3">
                        <button type="button" 
                                onclick="closePreviousBalanceModal()" 
                                class="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-orange-500 text-white rounded-md hover:bg-orange-600">
                            Add Balance
                        </button>
                    </div>
                </form>
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
            const checkboxes = document.querySelectorAll('.customer-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });

        // Previous Balance Modal Functions
        function openPreviousBalanceModal(customerId, customerName) {
            document.getElementById('modalCustomerId').value = customerId;
            document.getElementById('modalCustomerName').textContent = customerName;
            document.getElementById('previousBalanceAmount').value = '';
            document.getElementById('previousBalanceModal').classList.remove('hidden');
        }

        function closePreviousBalanceModal() {
            document.getElementById('previousBalanceModal').classList.add('hidden');
        }

        // Handle form submission
        document.getElementById('previousBalanceForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const customerId = document.getElementById('modalCustomerId').value;
            const amount = document.getElementById('previousBalanceAmount').value;
            const formData = new FormData(this);
            
            // Get CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || 
                             formData.get('_token');
            
            // Disable submit button
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Adding...';
            
            fetch(`/customers/${customerId}/previous-balance`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closePreviousBalanceModal();
                    // Reload page to show updated balance
                    window.location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to add previous balance'));
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding previous balance. Please try again.');
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            });
        });

        // Close modal when clicking outside
        document.getElementById('previousBalanceModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePreviousBalanceModal();
            }
        });

        async function printAllCustomersReport() {
            try {
                if (window.FTReceipt?.requireConfigured) {
                    await window.FTReceipt.requireConfigured();
                }
            } catch (e) {
                return;
            }

            // Read optional From/To dates from inputs above the button
            const fromDateInput = document.getElementById('customer-from-date')?.value || '';
            const toDateInput = document.getElementById('customer-to-date')?.value || '';

            const printWindow = window.open('', '_blank');
            
            // Show loading message
            printWindow.document.write('<html><head><title>Loading...</title></head><body><h1>Loading report...</h1></body></html>');
            printWindow.document.close();
            
            // Get CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || 
                             document.querySelector('input[name="_token"]')?.value || '';
            
            // Build query string with optional dates
            const params = new URLSearchParams();
            if (fromDateInput && fromDateInput.trim()) {
                params.append('from_date', fromDateInput.trim());
            }
            if (toDateInput && toDateInput.trim()) {
                params.append('to_date', toDateInput.trim());
            }

            const url = `/customers/print-all-report` + (params.toString() ? `?${params.toString()}` : '');

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
                    const customers = data.customers || [];
                    const fromDate = data.from_date || null;
                    const toDate = data.to_date || null;
                    const grandTotals = data.grand_totals || { total_price: 0, paid_amount: 0, remaining: 0 };
                    
                    let printContent = `
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <title>All Customers Detailed Report</title>
                            <style>
                                @media print {
                                    @page { margin: 10mm; }
                                    .page-break { page-break-after: always; }
                                    .customer-section { page-break-inside: avoid; }
                                }
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
                                    font-size: 7px;
                                    color: #000 !important;
                                }
                                .customer-section {
                                    margin-bottom: 30px;
                                    border: 1px solid #000;
                                    padding: 15px;
                                    page-break-inside: avoid;
                                }
                                .customer-header {
                                    background-color: #f0f0f0;
                                    padding: 15px;
                                    margin-bottom: 15px;
                                    border-bottom: 2px solid #000;
                                }
                                .customer-name {
                                    font-weight: bold;
                                    font-size: 18px;
                                    margin-bottom: 10px;
                                    color: #000 !important;
                                    text-transform: uppercase;
                                }
                                .customer-summary {
                                    background-color: #f9f9f9;
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
                                .summary-total {
                                    font-weight: bold;
                                    font-size: 14px;
                                    margin-top: 10px;
                                    padding-top: 10px;
                                    border-top: 2px solid #000;
                                }
                            </style>
                        </head>
                        <body>
                            ${(window.FTReceipt && window.FTReceipt.headerHtml) ? window.FTReceipt.headerHtml('All Customers Detailed Report') : ''}
                            <p style="text-align:center;font-size:10px;margin:4px 0 12px;">
                                <strong>From Date:</strong> ${fromDate ? new Date(fromDate).toLocaleDateString('en-US') : 'All Time'}
                                &nbsp; | &nbsp;
                                <strong>To Date:</strong> ${toDate ? new Date(toDate).toLocaleDateString('en-US') : 'All Time'}
                                <br>Generated on: ${new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' })}
                            </p>

                            <!-- Grand Totals Section -->
                            <div style="border: 3px solid #000; padding: 20px; margin-bottom: 30px; background-color: #f9f9f9;">
                                <div style="font-weight: bold; font-size: 18px; margin-bottom: 15px; text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px;">
                                    ALL CUSTOMERS SUMMARY
                                </div>
                                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">
                                    <div style="border: 2px solid #000; padding: 15px; text-align: center; background-color: #fff;">
                                        <div style="font-size: 12px; color: #000 !important; margin-bottom: 8px; font-weight: bold;">Total Price</div>
                                        <div style="font-size: 20px; font-weight: bold; color: #000 !important;">PKR ${parseFloat(grandTotals.total_price || 0).toFixed(2)}</div>
                                    </div>
                                    <div style="border: 2px solid #000; padding: 15px; text-align: center; background-color: #fff;">
                                        <div style="font-size: 12px; color: #000 !important; margin-bottom: 8px; font-weight: bold;">Paid</div>
                                        <div style="font-size: 20px; font-weight: bold; color: #000 !important;">PKR ${parseFloat(grandTotals.paid_amount || 0).toFixed(2)}</div>
                                    </div>
                                    <div style="border: 2px solid #000; padding: 15px; text-align: center; background-color: #fff;">
                                        <div style="font-size: 12px; color: #000 !important; margin-bottom: 8px; font-weight: bold;">Remaining</div>
                                        <div style="font-size: 20px; font-weight: bold; color: #000 !important;">PKR ${parseFloat(grandTotals.remaining || 0).toFixed(2)}</div>
                                    </div>
                                </div>
                            </div>
                    `;
                    
                    customers.forEach((customerData, index) => {
                        const customer = customerData.customer;
                        const summary = customerData.summary;
                        const sales = customerData.sales || [];
                        
                        printContent += `
                            <div class="customer-section">
                                <div class="customer-header">
                                    <div class="customer-name">${customer.name}</div>
                                    <div style="font-size: 11px; line-height: 1.6;">
                                        ${customer.customer_id ? `<div><strong>Customer ID:</strong> ${customer.customer_id}</div>` : ''}
                                        ${customer.email ? `<div><strong>Email:</strong> ${customer.email}</div>` : ''}
                                        ${customer.phone ? `<div><strong>Phone:</strong> ${customer.phone}</div>` : ''}
                                        ${customer.address ? `<div><strong>Address:</strong> ${customer.address}${customer.city ? ', ' + customer.city : ''}${customer.state ? ', ' + customer.state : ''}${customer.country ? ', ' + customer.country : ''}</div>` : ''}
                                    </div>
                                </div>
                                
                                <div class="customer-summary">
                                    <div style="font-weight: bold; font-size: 14px; margin-bottom: 15px; text-align: center; border-bottom: 2px solid #000; padding-bottom: 8px;">
                                        ACCOUNT SUMMARY
                                    </div>
                                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 15px;">
                                        <div style="border: 2px solid #000; padding: 12px; text-align: center; background-color: #f9f9f9;">
                                            <div style="font-size: 11px; color: #000 !important; margin-bottom: 5px; font-weight: bold;">Total Price</div>
                                            <div style="font-size: 18px; font-weight: bold; color: #000 !important;">PKR ${parseFloat(summary.total_price || 0).toFixed(2)}</div>
                                        </div>
                                        <div style="border: 2px solid #000; padding: 12px; text-align: center; background-color: #f9f9f9;">
                                            <div style="font-size: 11px; color: #000 !important; margin-bottom: 5px; font-weight: bold;">Paid</div>
                                            <div style="font-size: 18px; font-weight: bold; color: #000 !important;">PKR ${parseFloat(summary.paid_amount || 0).toFixed(2)}</div>
                                        </div>
                                        <div style="border: 2px solid #000; padding: 12px; text-align: center; background-color: #f9f9f9;">
                                            <div style="font-size: 11px; color: #000 !important; margin-bottom: 5px; font-weight: bold;">Remaining</div>
                                            <div style="font-size: 18px; font-weight: bold; color: #000 !important;">PKR ${parseFloat(summary.remaining || 0).toFixed(2)}</div>
                                        </div>
                                    </div>
                                    <div style="border-top: 2px solid #000; padding-top: 10px; margin-top: 10px;">
                                        <div style="display: flex; justify-content: space-between; align-items: center;">
                                            <span style="font-weight: bold; font-size: 12px;">Total Sales:</span>
                                            <span style="font-weight: bold; font-size: 14px;">${summary.total_sales || 0}</span>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 8px;">
                                            <span style="font-weight: bold; font-size: 12px;">Status:</span>
                                            <span style="color: #000 !important; font-size: 16px; font-weight: bold;">
                                                ${(summary.remaining || 0) > 0 ? 'UNPAID' : 'PAID'}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div style="margin-top: 15px;">
                                    <div style="font-weight: bold; font-size: 13px; margin-bottom: 8px; border-bottom: 1px solid #000; padding-bottom: 4px;">
                                        PURCHASE HISTORY (Within Selected Date Range)
                                    </div>`;

                        if (sales.length === 0) {
                            printContent += `
                                    <p style="font-size: 11px; font-style: italic;">No purchases found in this date range.</p>
                                </div>
                            </div>
                            `;
                        } else {
                            printContent += `
                                    <table style="width: 100%; border-collapse: collapse; font-size: 10px; margin-top: 5px;">
                                        <thead>
                                            <tr>
                                                <th style="border: 1px solid #000; padding: 4px; text-align: left;">Sale Date</th>
                                                <th style="border: 1px solid #000; padding: 4px; text-align: left;">Sale No.</th>
                                                <th style="border: 1px solid #000; padding: 4px; text-align: right;">Total</th>
                                                <th style="border: 1px solid #000; padding: 4px; text-align: right;">Paid</th>
                                                <th style="border: 1px solid #000; padding: 4px; text-align: right;">Remaining</th>
                                                <th style="border: 1px solid #000; padding: 4px; text-align: center;">Payment Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>`;

                            sales.forEach(sale => {
                                printContent += `
                                            <tr>
                                                <td style="border: 1px solid #ccc; padding: 3px;">${sale.sale_date || ''}</td>
                                                <td style="border: 1px solid #ccc; padding: 3px;">${sale.sale_number || ''}</td>
                                                <td style="border: 1px solid #ccc; padding: 3px; text-align: right;">PKR ${parseFloat(sale.total_amount || 0).toFixed(2)}</td>
                                                <td style="border: 1px solid #ccc; padding: 3px; text-align: right;">PKR ${parseFloat(sale.paid_amount || 0).toFixed(2)}</td>
                                                <td style="border: 1px solid #ccc; padding: 3px; text-align: right;">PKR ${parseFloat(sale.remaining || 0).toFixed(2)}</td>
                                                <td style="border: 1px solid #ccc; padding: 3px; text-align: center;">${(sale.payment_status || '').toUpperCase()}</td>
                                            </tr>`;
                            });

                            printContent += `
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            `;
                        }
                    });
                    
                    printContent += `
                            <div style="text-align: center; margin-top: 20px; padding-top: 15px; border-top: 2px solid #000; font-size: 10px; color: #000 !important;">
                                <p>This is a computer-generated comprehensive report.</p>
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
                                <h1 style="color: red;">Error Loading Report</h1>
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
                                <h1 style="color: red;">Error Loading Report</h1>
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
    </script>
</x-app-layout>

