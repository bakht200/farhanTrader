<x-app-layout>
    <x-slot name="header">
        Activity Details
    </x-slot>

    <!-- Breadcrumb -->
    <div class="mb-4">
        <nav class="text-sm text-gray-600">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-900">Dashboard</a>
            <span class="mx-2">></span>
            <a href="{{ route('user-activities.index') }}" class="hover:text-gray-900">User Activity</a>
            <span class="mx-2">></span>
            <span class="text-gray-900 font-medium">Details</span>
        </nav>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Left Column -->
            <div class="space-y-6">
                <!-- Basic Information -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Basic Information</h3>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Date & Time</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $userActivity->date->format('d M Y') }} at {{ $userActivity->time }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">User</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $userActivity->user->name ?? 'Guest' }} ({{ $userActivity->user->email ?? 'N/A' }})</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Activity Type</dt>
                            <dd class="mt-1">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    {{ $userActivity->activity_type === 'create' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $userActivity->activity_type === 'update' ? 'bg-blue-100 text-blue-800' : '' }}
                                    {{ $userActivity->activity_type === 'delete' ? 'bg-red-100 text-red-800' : '' }}
                                    {{ $userActivity->activity_type === 'view' ? 'bg-gray-100 text-gray-800' : '' }}
                                    {{ !in_array($userActivity->activity_type, ['create', 'update', 'delete', 'view']) ? 'bg-purple-100 text-purple-800' : '' }}">
                                    {{ ucfirst($userActivity->activity_type ?? 'N/A') }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Page</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $userActivity->page ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Route Name</dt>
                            <dd class="mt-1 text-sm text-gray-900 font-mono">{{ $userActivity->route_name ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Process</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $userActivity->process ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Button</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $userActivity->button ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Status</dt>
                            <dd class="mt-1">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    {{ $userActivity->status === 'success' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $userActivity->status === 'error' ? 'bg-red-100 text-red-800' : '' }}
                                    {{ $userActivity->status === 'warning' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                    {{ !in_array($userActivity->status, ['success', 'error', 'warning']) ? 'bg-gray-100 text-gray-800' : '' }}">
                                    {{ ucfirst($userActivity->status ?? 'N/A') }}
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>

                <!-- Technical Information -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Technical Information</h3>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">IP Address</dt>
                            <dd class="mt-1 text-sm text-gray-900 font-mono">{{ $userActivity->ip_address ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Browser</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $userActivity->browser ?? 'N/A' }} {{ $userActivity->browser_version ? 'v' . $userActivity->browser_version : '' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Platform</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $userActivity->platform ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Device Type</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ ucfirst($userActivity->device_type ?? 'N/A') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">HTTP Method</dt>
                            <dd class="mt-1 text-sm text-gray-900 font-mono">{{ $userActivity->method ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Response Code</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $userActivity->response_code ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Execution Time</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $userActivity->execution_time ? number_format($userActivity->execution_time, 4) . 's' : 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Session ID</dt>
                            <dd class="mt-1 text-sm text-gray-900 font-mono text-xs">{{ $userActivity->session_id ?? 'N/A' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-6">
                <!-- URL Information -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">URL Information</h3>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Full URL</dt>
                            <dd class="mt-1 text-sm text-gray-900 break-all font-mono">{{ $userActivity->url ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Referrer</dt>
                            <dd class="mt-1 text-sm text-gray-900 break-all">{{ $userActivity->referrer ?? 'N/A' }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Description -->
                @if($userActivity->description)
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Description</h3>
                    <p class="text-sm text-gray-900 whitespace-pre-wrap">{{ $userActivity->description }}</p>
                </div>
                @endif

                <!-- Request Data -->
                @if($userActivity->request_data)
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Request Data</h3>
                    <pre class="bg-gray-50 p-4 rounded-md text-xs text-gray-900 overflow-x-auto max-h-96">{{ json_encode($userActivity->request_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
                @endif

                <!-- Response Data -->
                @if($userActivity->response_data)
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Response Data</h3>
                    <pre class="bg-gray-50 p-4 rounded-md text-xs text-gray-900 overflow-x-auto max-h-96">{{ json_encode($userActivity->response_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
                @endif

                <!-- User Agent -->
                @if($userActivity->user_agent)
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">User Agent</h3>
                    <p class="text-sm text-gray-900 break-all font-mono text-xs">{{ $userActivity->user_agent }}</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Back Button -->
        <div class="mt-6 pt-6 border-t border-gray-200">
            <a href="{{ route('user-activities.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-md font-medium">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to Activity Log
            </a>
        </div>
    </div>
</x-app-layout>
