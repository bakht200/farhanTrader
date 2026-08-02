<x-app-layout>
    <x-slot name="header">
        User Activity
    </x-slot>

    <!-- Breadcrumb -->
    <div class="mb-4">
        <nav class="text-sm text-gray-600">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-900">Dashboard</a>
            <span class="mx-2">></span>
            <span class="text-gray-900 font-medium">User Activity</span>
        </nav>
    </div>

    <!-- Filters Section -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Filters</h3>
            @if(request()->anyFilled(['search', 'user_id', 'activity_type', 'status', 'ip_address', 'browser', 'device_type', 'platform', 'method', 'start_date', 'end_date', 'start_time', 'end_time', 'route_name', 'page_filter']))
                <a href="{{ route('user-activities.index') }}" class="text-sm text-orange-600 hover:text-orange-800 font-medium">
                    Clear All Filters
                </a>
            @endif
        </div>

        <form method="GET" action="{{ route('user-activities.index') }}" class="space-y-4" id="filter-form">
            <!-- First Row: Search and Basic Filters -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Search -->
                <div class="lg:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <div class="relative">
                        <input type="text" 
                               name="search" 
                               value="{{ request('search') }}" 
                               placeholder="Search by page, description, process, button, URL, IP, browser, user..."
                               class="w-full px-4 py-2 pl-10 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
                               oninput="handleSearchInput()">
                        <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>

                <!-- User Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">User</label>
                    <select name="user_id" onchange="this.form.submit()" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                        <option value="all" {{ request('user_id') === 'all' || !request('user_id') ? 'selected' : '' }}>All Users</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Activity Type Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Activity Type</label>
                    <select name="activity_type" onchange="this.form.submit()" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                        <option value="all" {{ request('activity_type') === 'all' || !request('activity_type') ? 'selected' : '' }}>All Types</option>
                        @foreach($activityTypes as $type)
                            <option value="{{ $type }}" {{ request('activity_type') == $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Second Row: Date and Time Filters -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Start Date -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                    <input type="date" 
                           name="start_date" 
                           value="{{ request('start_date') }}" 
                           onchange="this.form.submit()"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                </div>

                <!-- End Date -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                    <input type="date" 
                           name="end_date" 
                           value="{{ request('end_date') }}" 
                           onchange="this.form.submit()"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                </div>

                <!-- Start Time -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Start Time</label>
                    <input type="time" 
                           name="start_time" 
                           value="{{ request('start_time') }}" 
                           onchange="this.form.submit()"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                </div>

                <!-- End Time -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">End Time</label>
                    <input type="time" 
                           name="end_time" 
                           value="{{ request('end_time') }}" 
                           onchange="this.form.submit()"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                </div>
            </div>

            <!-- Third Row: Technical Filters -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <!-- IP Address -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">IP Address</label>
                    <input type="text" 
                           name="ip_address" 
                           value="{{ request('ip_address') }}" 
                           placeholder="e.g., 192.168.1.1"
                           onchange="this.form.submit()"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                </div>

                <!-- Browser Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Browser</label>
                    <select name="browser" onchange="this.form.submit()" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                        <option value="all" {{ request('browser') === 'all' || !request('browser') ? 'selected' : '' }}>All Browsers</option>
                        @foreach($browsers as $browser)
                            <option value="{{ $browser }}" {{ request('browser') == $browser ? 'selected' : '' }}>{{ $browser }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Device Type Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Device Type</label>
                    <select name="device_type" onchange="this.form.submit()" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                        <option value="all" {{ request('device_type') === 'all' || !request('device_type') ? 'selected' : '' }}>All Devices</option>
                        @foreach($deviceTypes as $device)
                            <option value="{{ $device }}" {{ request('device_type') == $device ? 'selected' : '' }}>{{ ucfirst($device) }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Platform Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Platform</label>
                    <select name="platform" onchange="this.form.submit()" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                        <option value="all" {{ request('platform') === 'all' || !request('platform') ? 'selected' : '' }}>All Platforms</option>
                        @foreach($platforms as $platform)
                            <option value="{{ $platform }}" {{ request('platform') == $platform ? 'selected' : '' }}>{{ $platform }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Method Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">HTTP Method</label>
                    <select name="method" onchange="this.form.submit()" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                        <option value="all" {{ request('method') === 'all' || !request('method') ? 'selected' : '' }}>All Methods</option>
                        @foreach($methods as $method)
                            <option value="{{ $method }}" {{ request('method') == $method ? 'selected' : '' }}>{{ $method }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Fourth Row: Status and Route Filters -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Status Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" onchange="this.form.submit()" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                        <option value="all" {{ request('status') === 'all' || !request('status') ? 'selected' : '' }}>All Statuses</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Route Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Route Name</label>
                    <input type="text" 
                           name="route_name" 
                           value="{{ request('route_name') }}" 
                           placeholder="e.g., products.index"
                           onchange="this.form.submit()"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                </div>

                <!-- Page -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Page</label>
                    <input type="text" 
                           name="page_filter" 
                           value="{{ request('page_filter') }}" 
                           placeholder="e.g., Products Index"
                           onchange="this.form.submit()"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                </div>
            </div>

            <!-- Hidden fields for pagination -->
            <input type="hidden" name="per_page" value="{{ request('per_page', 25) }}">
            @if(request('page'))
                <input type="hidden" name="page" value="{{ request('page') }}">
            @endif
        </form>
    </div>

    <!-- Activities Table -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Activity Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Page</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Process/Button</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Browser</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Device</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($activities as $activity)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $activity->date->format('d M Y') }}</div>
                            <div class="text-xs text-gray-500">{{ $activity->time }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $activity->user->name ?? 'Guest' }}</div>
                            <div class="text-xs text-gray-500">{{ $activity->user->email ?? 'N/A' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                {{ $activity->activity_type === 'create' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $activity->activity_type === 'update' ? 'bg-blue-100 text-blue-800' : '' }}
                                {{ $activity->activity_type === 'delete' ? 'bg-red-100 text-red-800' : '' }}
                                {{ $activity->activity_type === 'view' ? 'bg-gray-100 text-gray-800' : '' }}
                                {{ !in_array($activity->activity_type, ['create', 'update', 'delete', 'view']) ? 'bg-purple-100 text-purple-800' : '' }}">
                                {{ ucfirst($activity->activity_type ?? 'N/A') }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900">{{ $activity->page ?? 'N/A' }}</div>
                            <div class="text-xs text-gray-500 truncate max-w-xs">{{ $activity->route_name ?? 'N/A' }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900">{{ $activity->process ?? 'N/A' }}</div>
                            @if($activity->button)
                                <div class="text-xs text-gray-500">Button: {{ $activity->button }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $activity->ip_address ?? 'N/A' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $activity->browser ?? 'N/A' }}</div>
                            @if($activity->browser_version)
                                <div class="text-xs text-gray-500">v{{ $activity->browser_version }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ ucfirst($activity->device_type ?? 'N/A') }}</div>
                            <div class="text-xs text-gray-500">{{ $activity->platform ?? 'N/A' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                {{ $activity->status === 'success' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $activity->status === 'error' ? 'bg-red-100 text-red-800' : '' }}
                                {{ $activity->status === 'warning' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ !in_array($activity->status, ['success', 'error', 'warning']) ? 'bg-gray-100 text-gray-800' : '' }}">
                                {{ ucfirst($activity->status ?? 'N/A') }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="{{ route('user-activities.show', $activity) }}" 
                               class="text-orange-600 hover:text-orange-900" 
                               title="View Details">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="px-6 py-4 text-center text-gray-500">
                            No activities found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($activities->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
            <div class="flex items-center">
                <span class="text-sm text-gray-700">Showing {{ $activities->firstItem() ?? 0 }} to {{ $activities->lastItem() ?? 0 }} of {{ $activities->total() }} results</span>
            </div>
            <div class="flex items-center space-x-2">
                <div class="flex items-center space-x-1">
                    @if($activities->onFirstPage())
                        <span class="px-3 py-1 text-gray-400 cursor-not-allowed">&lt;</span>
                    @else
                        <a href="{{ $activities->previousPageUrl() }}" class="px-3 py-1 text-gray-700 hover:text-gray-900">&lt;</a>
                    @endif
                    
                    @foreach($activities->getUrlRange(1, min(5, $activities->lastPage())) as $page => $url)
                        @if($page == $activities->currentPage())
                            <span class="px-3 py-1 bg-orange-500 text-white rounded">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="px-3 py-1 text-gray-700 hover:text-gray-900">{{ $page }}</a>
                        @endif
                    @endforeach
                    
                    @if($activities->hasMorePages())
                        <span class="px-2 py-1 text-gray-500">...</span>
                        <a href="{{ $activities->url($activities->lastPage()) }}" class="px-3 py-1 text-gray-700 hover:text-gray-900">{{ $activities->lastPage() }}</a>
                    @endif
                    
                    @if($activities->hasMorePages())
                        <a href="{{ $activities->nextPageUrl() }}" class="px-3 py-1 text-gray-700 hover:text-gray-900">&gt;</a>
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
                const form = document.getElementById('filter-form');
                if (form) {
                    // Remove page parameter when filtering (reset to page 1)
                    const pageInput = form.querySelector('input[name="page"]');
                    if (pageInput) {
                        pageInput.remove();
                    }
                    form.submit();
                }
            }, 500); // Wait 500ms after user stops typing
        }

        // Reset page to 1 when any filter changes (except pagination)
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('filter-form');
            if (form) {
                // Get all filter inputs except pagination controls
                const filterInputs = form.querySelectorAll('input:not([name="page"]):not([name="per_page"]), select');
                
                filterInputs.forEach(input => {
                    // Skip if it's a pagination-related input
                    if (input.name === 'page' || input.name === 'per_page') {
                        return;
                    }
                    
                    input.addEventListener('change', function() {
                        // Remove page parameter when filter changes
                        const pageInput = form.querySelector('input[name="page"]');
                        if (pageInput) {
                            pageInput.remove();
                        }
                    });
                });
            }
        });
    </script>
</x-app-layout>
