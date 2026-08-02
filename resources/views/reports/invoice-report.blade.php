<x-app-layout>
    <x-slot name="header">
        Invoice Report
    </x-slot>

    <!-- Breadcrumb -->
    <div class="mb-4">
        <nav class="text-sm text-gray-600">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-900">Reports</a>
            <span class="mx-2">></span>
            <span class="text-gray-900 font-medium">Invoice Report</span>
        </nav>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <!-- Total Amount Card -->
        <div class="bg-green-50 border border-green-200 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-medium text-gray-600 mb-2">Total Amount</h3>
                    <p class="text-2xl font-bold text-gray-900">${{ number_format($totalAmount, 2) }}</p>
                </div>
                <div class="bg-green-100 rounded-full p-3">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Total Paid Card -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-medium text-gray-600 mb-2">Total Paid</h3>
                    <p class="text-2xl font-bold text-gray-900">${{ number_format($totalPaid, 2) }}</p>
                </div>
                <div class="bg-blue-100 rounded-full p-3">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Total Unpaid Card -->
        <div class="bg-orange-50 border border-orange-200 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-medium text-gray-600 mb-2">Total Unpaid</h3>
                    <p class="text-2xl font-bold text-gray-900">${{ number_format($totalUnpaid, 2) }}</p>
                </div>
                <div class="bg-orange-100 rounded-full p-3">
                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Overdue Card -->
        <div class="bg-red-50 border border-red-200 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-medium text-gray-600 mb-2">Overdue</h3>
                    <p class="text-2xl font-bold text-gray-900">${{ number_format($overdueAmount, 2) }}</p>
                </div>
                <div class="bg-red-100 rounded-full p-3">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form method="GET" action="{{ route('reports.invoice-report') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Date Range -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Choose Date</label>
                <div class="relative">
                    <input type="date" name="start_date" value="{{ $startDate }}" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                    <span class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 pointer-events-none">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </span>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">To Date</label>
                <div class="relative">
                    <input type="date" name="end_date" value="{{ $endDate }}" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                </div>
            </div>

            <!-- Customer Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Customer</label>
                <select name="customer_id" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                    <option value="all" {{ $customerId === 'all' ? 'selected' : '' }}>All</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" {{ $customerId == $customer->id ? 'selected' : '' }}>
                            {{ $customer->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Status Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All</option>
                    <option value="draft" {{ $status === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="sent" {{ $status === 'sent' ? 'selected' : '' }}>Sent</option>
                    <option value="paid" {{ $status === 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="overdue" {{ $status === 'overdue' ? 'selected' : '' }}>Overdue</option>
                    <option value="cancelled" {{ $status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>

            <!-- Generate Report Button -->
            <div class="md:col-span-4 flex justify-end">
                <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white px-6 py-2 rounded-md font-medium">
                    Generate Report
                </button>
            </div>
        </form>
    </div>

    <!-- Invoice Report Table -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-900">Invoice Report</h3>
            <button class="text-gray-500 hover:text-gray-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice Number</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Paid</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount Due</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($invoices as $invoice)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-medium text-orange-600">{{ $invoice->invoice_number }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-900">{{ $invoice->customer->name ?? 'Walk-in Customer' }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-900">{{ $invoice->created_at ? $invoice->created_at->format('Y-m-d h:i A') : ($invoice->invoice_date ? $invoice->invoice_date->format('Y-m-d') : 'N/A') }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-900">{{ $invoice->due_date ? $invoice->due_date->format('Y-m-d') : 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-medium text-gray-900">${{ number_format($invoice->total_amount, 2) }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-900">${{ number_format($invoice->calculated_paid_amount ?? ($invoice->paid_amount ?? 0), 2) }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $remainingAmount = (float) ($invoice->remaining_balance_due ?? max(0, ($invoice->total_amount ?? 0) - ($invoice->paid_amount ?? 0)));
                            @endphp
                            <span class="text-sm font-medium {{ $remainingAmount > 0 ? 'text-red-600' : 'text-green-600' }}">
                                ${{ number_format($remainingAmount, 2) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($invoice->status === 'paid')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <span class="w-1.5 h-1.5 mr-1.5 bg-green-500 rounded-full"></span>
                                    Paid
                                </span>
                            @elseif($invoice->status === 'overdue' || ($invoice->due_date && $invoice->due_date->isPast() && $invoice->status !== 'paid'))
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    <span class="w-1.5 h-1.5 mr-1.5 bg-red-500 rounded-full"></span>
                                    Unpaid
                                </span>
                            @elseif($invoice->status === 'sent')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    <span class="w-1.5 h-1.5 mr-1.5 bg-yellow-500 rounded-full"></span>
                                    Sent
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    <span class="w-1.5 h-1.5 mr-1.5 bg-gray-500 rounded-full"></span>
                                    {{ ucfirst($invoice->status) }}
                                </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                            No invoices found for the selected period.
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
                <select id="per_page" class="ml-2 px-3 py-1 border border-gray-300 rounded-md text-sm focus:ring-orange-500 focus:border-orange-500">
                    <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10 Entries</option>
                    <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25 Entries</option>
                    <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50 Entries</option>
                    <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100 Entries</option>
                </select>
            </div>
            <div class="flex items-center space-x-2">
                @if($invoices->hasPages())
                    <div class="flex items-center space-x-1">
                        @if($invoices->onFirstPage())
                            <span class="px-3 py-1 text-gray-400 cursor-not-allowed">&lt;</span>
                        @else
                            <a href="{{ $invoices->previousPageUrl() }}" class="px-3 py-1 text-gray-700 hover:text-gray-900">&lt;</a>
                        @endif
                        
                        @foreach($invoices->getUrlRange(1, min(5, $invoices->lastPage())) as $page => $url)
                            @if($page == $invoices->currentPage())
                                <span class="px-3 py-1 bg-orange-500 text-white rounded">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="px-3 py-1 text-gray-700 hover:text-gray-900">{{ $page }}</a>
                            @endif
                        @endforeach
                        
                        @if($invoices->hasMorePages())
                            <span class="px-2 py-1 text-gray-500">...</span>
                            <a href="{{ $invoices->url($invoices->lastPage()) }}" class="px-3 py-1 text-gray-700 hover:text-gray-900">{{ $invoices->lastPage() }}</a>
                        @endif
                        
                        @if($invoices->hasMorePages())
                            <a href="{{ $invoices->nextPageUrl() }}" class="px-3 py-1 text-gray-700 hover:text-gray-900">&gt;</a>
                        @else
                            <span class="px-3 py-1 text-gray-400 cursor-not-allowed">&gt;</span>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>


    <script>
        // Handle per page change
        document.getElementById('per_page').addEventListener('change', function() {
            const url = new URL(window.location.href);
            url.searchParams.set('per_page', this.value);
            window.location.href = url.toString();
        });
    </script>
</x-app-layout>













