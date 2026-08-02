<x-app-layout>
    <x-slot name="header">
        Invoices
    </x-slot>

    <!-- Breadcrumb -->
    <div class="mb-4">
        <nav class="text-sm text-gray-600">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-900">Dashboard</a>
            <span class="mx-2">></span>
            <a href="{{ route('sales.index') }}" class="hover:text-gray-900">Sales</a>
            <span class="mx-2">></span>
            <span class="text-gray-900 font-medium">Invoices</span>
        </nav>
    </div>

    <!-- Search Bar -->
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <form method="GET" action="{{ route('sales.invoices.index') }}" class="flex items-center gap-4" id="search-form">
            <div class="flex-1 relative">
                <input type="text" 
                       id="search-input"
                       name="search" 
                       value="{{ request('search') }}" 
                       placeholder="Q Search by invoice number, customer name..." 
                       class="w-full px-4 py-2 pl-10 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
                       oninput="handleSearchInput()">
                <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            @if(request('search'))
                <a href="{{ route('sales.invoices.index') }}" class="px-4 py-2 text-gray-600 hover:text-gray-900">
                    Clear
                </a>
            @endif
        </form>
    </div>

    <!-- Invoices Table -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice Number</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Paid Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Remaining Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($invoices as $invoice)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-medium text-gray-900">{{ $invoice->invoice_number ?? 'INV-' . str_pad($invoice->id, 6, '0', STR_PAD_LEFT) }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-900">{{ $invoice->customer->name ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-900">{{ $invoice->created_at ? $invoice->created_at->format('Y-m-d h:i A') : ($invoice->invoice_date ? $invoice->invoice_date->format('Y-m-d') : 'N/A') }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-900">{{ $invoice->due_date ? $invoice->due_date->format('Y-m-d') : 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-medium text-gray-900">PKR {{ number_format($invoice->total_amount ?? 0, 2) }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-900">PKR {{ number_format($invoice->calculated_paid_amount ?? ($invoice->paid_amount ?? 0), 2) }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $remainingAmount = (float) ($invoice->remaining_balance_due ?? max(0, ($invoice->total_amount ?? 0) - ($invoice->paid_amount ?? 0)));
                            @endphp
                            <span class="text-sm font-medium {{ $remainingAmount > 0 ? 'text-red-600' : 'text-green-600' }}">
                                PKR {{ number_format($remainingAmount, 2) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $status = $invoice->status ?? 'pending';
                                $statusClass = match($status) {
                                    'paid' => 'bg-green-100 text-green-800',
                                    'partial' => 'bg-yellow-100 text-yellow-800',
                                    'overdue' => 'bg-red-100 text-red-800',
                                    'pending' => 'bg-gray-100 text-gray-800',
                                    default => 'bg-gray-100 text-gray-800'
                                };
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">
                                <span class="w-1.5 h-1.5 mr-1.5 rounded-full {{ 
                                    $status === 'paid' ? 'bg-green-500' : 
                                    ($status === 'partial' ? 'bg-yellow-500' : 
                                    ($status === 'overdue' ? 'bg-red-500' : 'bg-gray-500')) 
                                }}"></span>
                                {{ ucfirst($status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center space-x-3">
                                <a href="{{ route('sales.invoices.show', $invoice) }}" class="text-blue-600 hover:text-blue-900" title="View">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </a>
                                <a href="{{ route('sales.invoices.edit', $invoice) }}" class="text-orange-600 hover:text-orange-900" title="Edit">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>
                                <form action="{{ route('sales.invoices.destroy', $invoice) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this invoice?');">
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
                        <td colspan="9" class="px-6 py-4 text-center text-gray-500">
                            No invoices found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($invoices->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
            <div class="flex items-center">
                <span class="text-sm text-gray-700">Showing {{ $invoices->firstItem() ?? 0 }} to {{ $invoices->lastItem() ?? 0 }} of {{ $invoices->total() }} results</span>
            </div>
            <div class="flex items-center space-x-2">
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
            </div>
        </div>
        @endif
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
    </script>
</x-app-layout>


