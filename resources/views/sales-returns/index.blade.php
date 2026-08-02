<x-app-layout>
    <x-slot name="header">
        Sales Returns
    </x-slot>

    <!-- Breadcrumb -->
    <div class="mb-4">
        <nav class="text-sm text-gray-600">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-900">Dashboard</a>
            <span class="mx-2">></span>
            <a href="{{ route('sales.index') }}" class="hover:text-gray-900">Sales</a>
            <span class="mx-2">></span>
            <span class="text-gray-900 font-medium">Sales Returns</span>
        </nav>
    </div>

    <!-- Search Bar -->
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <form method="GET" action="{{ route('sales.returns.index') }}" class="flex items-center gap-4" id="search-form">
            <div class="flex-1 relative">
                <input type="text" 
                       id="search-input"
                       name="search" 
                       value="{{ request('search') }}" 
                       placeholder="Q Search by return number, customer name..." 
                       class="w-full px-4 py-2 pl-10 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
                       oninput="handleSearchInput()">
                <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            @if(request('search'))
                <a href="{{ route('sales.returns.index') }}" class="px-4 py-2 text-gray-600 hover:text-gray-900">
                    Clear
                </a>
            @endif
        </form>
    </div>

    <!-- Sales Returns Table -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="p-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-semibold">All Sales Returns</h3>
            <a href="{{ route('sales.returns.create') }}" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-md">Create Return</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Return Number</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Return Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($returns as $return)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-medium text-gray-900">{{ $return->return_number ?? 'RET-' . str_pad($return->id, 6, '0', STR_PAD_LEFT) }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-900">{{ $return->customer->name ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-900">{{ $return->return_date ? $return->return_date->format('Y-m-d') : 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-medium text-gray-900">PKR {{ number_format($return->total_amount ?? 0, 2) }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                {{ ucfirst($return->status ?? 'completed') }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center space-x-3">
                                <a href="{{ route('sales.returns.show', $return) }}" class="text-blue-600 hover:text-blue-900" title="View">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </a>
                                <a href="{{ route('sales.returns.edit', $return) }}" class="text-orange-600 hover:text-orange-900" title="Edit">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            No sales returns found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($returns->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
            <div class="flex items-center">
                <span class="text-sm text-gray-700">Showing {{ $returns->firstItem() ?? 0 }} to {{ $returns->lastItem() ?? 0 }} of {{ $returns->total() }} results</span>
            </div>
            <div class="flex items-center space-x-2">
                <div class="flex items-center space-x-1">
                    @if($returns->onFirstPage())
                        <span class="px-3 py-1 text-gray-400 cursor-not-allowed">&lt;</span>
                    @else
                        <a href="{{ $returns->previousPageUrl() }}" class="px-3 py-1 text-gray-700 hover:text-gray-900">&lt;</a>
                    @endif
                    
                    @foreach($returns->getUrlRange(1, min(5, $returns->lastPage())) as $page => $url)
                        @if($page == $returns->currentPage())
                            <span class="px-3 py-1 bg-orange-500 text-white rounded">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="px-3 py-1 text-gray-700 hover:text-gray-900">{{ $page }}</a>
                        @endif
                    @endforeach
                    
                    @if($returns->hasMorePages())
                        <span class="px-2 py-1 text-gray-500">...</span>
                        <a href="{{ $returns->url($returns->lastPage()) }}" class="px-3 py-1 text-gray-700 hover:text-gray-900">{{ $returns->lastPage() }}</a>
                    @endif
                    
                    @if($returns->hasMorePages())
                        <a href="{{ $returns->nextPageUrl() }}" class="px-3 py-1 text-gray-700 hover:text-gray-900">&gt;</a>
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




