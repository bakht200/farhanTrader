<x-app-layout>
    <x-slot name="header">
        Units
    </x-slot>

    <!-- Breadcrumb -->
    <div class="mb-4">
        <nav class="text-sm text-gray-600">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-900">Dashboard</a>
            <span class="mx-2">></span>
            <span class="text-gray-900 font-medium">Units</span>
        </nav>
    </div>

    <!-- Action Bar -->
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <!-- Left side: Refresh and Search -->
            <div class="flex flex-1 items-center gap-4">
                <a href="{{ route('units.index') }}" class="p-2 text-gray-500 hover:text-gray-700" title="Refresh">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                </a>

                <form method="GET" action="{{ route('units.index') }}" class="flex-1" id="search-form">
                    <div class="relative">
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
                </form>
            </div>

            <!-- Right side: Status Filter and Add Unit Button -->
            <div class="flex items-center gap-4">
                <form method="GET" action="{{ route('units.index') }}" class="flex items-center gap-2">
                    @if(request('search'))
                        <input type="hidden" name="search" value="{{ request('search') }}">
                    @endif
                    @if(request('sort_by'))
                        <input type="hidden" name="sort_by" value="{{ request('sort_by') }}">
                    @endif
                    @if(request('sort_order'))
                        <input type="hidden" name="sort_order" value="{{ request('sort_order') }}">
                    @endif
                    <select name="status" onchange="this.form.submit()" class="px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                        <option value="all" {{ request('status') === 'all' || !request('status') ? 'selected' : '' }}>Status</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </form>

                <a href="{{ route('units.create') }}" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-md font-medium inline-flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Add Unit
                </a>
            </div>
        </div>
    </div>

    <!-- Units Table -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left">
                            <input type="checkbox" class="rounded border-gray-300 text-orange-600 focus:ring-orange-500" id="select-all">
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="{{ route('units.index', array_merge(request()->except(['sort_by', 'sort_order']), ['sort_by' => 'name', 'sort_order' => request('sort_by') === 'name' && request('sort_order') === 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center">
                                Unit
                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                </svg>
                                <svg class="w-4 h-4 ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Short Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="{{ route('units.index', array_merge(request()->except(['sort_by', 'sort_order']), ['sort_by' => 'products_count', 'sort_order' => request('sort_by') === 'products_count' && request('sort_order') === 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center">
                                No of Products
                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                </svg>
                                <svg class="w-4 h-4 ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="{{ route('units.index', array_merge(request()->except(['sort_by', 'sort_order']), ['sort_by' => 'created_at', 'sort_order' => request('sort_by') === 'created_at' && request('sort_order') === 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center">
                                Created Date
                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                </svg>
                                <svg class="w-4 h-4 ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($units as $unit)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <input type="checkbox" class="unit-checkbox rounded border-gray-300 text-orange-600 focus:ring-orange-500" value="{{ $unit->id }}">
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-medium text-gray-900">{{ $unit->name }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-900">{{ $unit->short_name }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-900">{{ $unit->products_count }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-900">{{ $unit->created_at->format('d M Y') }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($unit->is_active)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <span class="w-1.5 h-1.5 mr-1.5 bg-green-500 rounded-full"></span>
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    <span class="w-1.5 h-1.5 mr-1.5 bg-red-500 rounded-full"></span>
                                    Inactive
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center space-x-3">
                                <a href="{{ route('units.edit', $unit) }}" class="text-green-600 hover:text-green-900" title="Edit">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>
                                <form action="{{ route('units.destroy', $unit) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this unit?');">
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
                            No units found.
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
                <form method="GET" action="{{ route('units.index') }}" class="ml-2">
                    @if(request('search'))
                        <input type="hidden" name="search" value="{{ request('search') }}">
                    @endif
                    @if(request('status'))
                        <input type="hidden" name="status" value="{{ request('status') }}">
                    @endif
                    @if(request('sort_by'))
                        <input type="hidden" name="sort_by" value="{{ request('sort_by') }}">
                    @endif
                    @if(request('sort_order'))
                        <input type="hidden" name="sort_order" value="{{ request('sort_order') }}">
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
                @if($units->hasPages())
                    <div class="flex items-center space-x-1">
                        @if($units->onFirstPage())
                            <span class="px-3 py-1 text-gray-400 cursor-not-allowed">&lt;</span>
                        @else
                            <a href="{{ $units->previousPageUrl() }}" class="px-3 py-1 text-gray-700 hover:text-gray-900">&lt;</a>
                        @endif
                        
                        @foreach($units->getUrlRange(1, min(5, $units->lastPage())) as $page => $url)
                            @if($page == $units->currentPage())
                                <span class="px-3 py-1 bg-orange-500 text-white rounded">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="px-3 py-1 text-gray-700 hover:text-gray-900">{{ $page }}</a>
                            @endif
                        @endforeach
                        
                        @if($units->hasMorePages())
                            <span class="px-2 py-1 text-gray-500">...</span>
                            <a href="{{ $units->url($units->lastPage()) }}" class="px-3 py-1 text-gray-700 hover:text-gray-900">{{ $units->lastPage() }}</a>
                        @endif
                        
                        @if($units->hasMorePages())
                            <a href="{{ $units->nextPageUrl() }}" class="px-3 py-1 text-gray-700 hover:text-gray-900">&gt;</a>
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
            const checkboxes = document.querySelectorAll('.unit-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    </script>
</x-app-layout>
