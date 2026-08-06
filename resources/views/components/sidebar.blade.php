<aside 
    :class="sidebarOpen ? 'translate-x-0 md:col-span-2' : '-translate-x-full md:hidden'"
    class="w-64 bg-white border-r border-gray-200 h-screen fixed left-0 top-0 overflow-y-auto z-30 transition-all duration-300 ease-in-out md:translate-x-0 md:relative md:z-auto md:h-screen"
    x-cloak
>
    <!-- Logo -->
    <div class="p-6 border-b border-gray-200">
        <div class="flex items-center gap-3">
            <img src="{{ asset('logo.png') }}" alt="Farhan Traders Logo" class="h-12 w-auto">
            <h1 class="text-lg font-bold text-gray-800">Farhan Traders</h1>
        </div>
    </div>

    <!-- Navigation Menu -->
    <nav class="p-4">
        <!-- Main -->
        <div class="mb-6">
            <h3 class="text-sm font-bold text-blue-600 uppercase mb-3">Main</h3>
            <ul class="space-y-1">
                <li>
                    <a href="{{ route('dashboard') }}" class="flex items-center px-3 py-2 text-gray-700 rounded-md hover:bg-gray-100 {{ request()->routeIs('dashboard') ? 'bg-gray-100 font-medium' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                        </svg>
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="{{ route('expenses.index') }}" class="flex items-center px-3 py-2 text-gray-700 rounded-md hover:bg-gray-100 {{ request()->routeIs('expenses.*') ? 'bg-gray-100 font-medium' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        Expenses
                    </a>
                </li>
                <li>
                    <a href="{{ route('health-check.index') }}" title="Requires internet connection" data-requires-internet="1" class="flex items-center px-3 py-2 text-gray-700 rounded-md hover:bg-gray-100 {{ request()->routeIs('health-check.*') ? 'bg-gray-100 font-medium' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m-8 5h10a2 2 0 002-2V7a2 2 0 00-2-2h-3l-1-2H9L8 5H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <span class="flex-1">Health Check</span>
                        <x-online-only-lock />
                    </a>
                </li>
                @if(Auth::user()?->isAdmin())
                <li>
                    <a href="{{ route('branches.index') }}" class="flex items-center px-3 py-2 text-gray-700 rounded-md hover:bg-gray-100 {{ request()->routeIs('branches.*') ? 'bg-gray-100 font-medium' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                        Branches
                    </a>
                </li>
                @endif
            </ul>
        </div>

        <!-- Inventory -->
        <div class="mb-6">
            <h3 class="text-sm font-bold text-blue-600 uppercase mb-3">Inventory</h3>
            <ul class="space-y-1">
                <li>
                    <a href="{{ route('products.index') }}" class="flex items-center px-3 py-2 text-gray-700 rounded-md hover:bg-gray-100 {{ request()->routeIs('products.*') ? 'bg-gray-100 font-medium' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                        Products
                    </a>
                </li>
                <li>
                    <a href="{{ route('products.create') }}" class="flex items-center px-3 py-2 text-gray-700 rounded-md hover:bg-gray-100 {{ request()->routeIs('products.create') ? 'bg-gray-100 font-medium' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Create Product
                    </a>
                </li>
                <li>
                    <a href="{{ route('products.low-stocks') }}" class="flex items-center px-3 py-2 text-gray-700 rounded-md hover:bg-gray-100 {{ request()->routeIs('products.low-stocks') ? 'bg-gray-100 font-medium' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                        Low Stocks
                    </a>
                </li>
                <li>
                    <a href="{{ route('categories.index') }}" class="flex items-center px-3 py-2 text-gray-700 rounded-md hover:bg-gray-100 {{ request()->routeIs('categories.*') ? 'bg-gray-100 font-medium' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                        Category
                    </a>
                </li>
                <li>
                    <a href="{{ route('units.index') }}" class="flex items-center px-3 py-2 text-gray-700 rounded-md hover:bg-gray-100 {{ request()->routeIs('units.*') ? 'bg-gray-100 font-medium' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                        Units
                    </a>
                </li>
            </ul>
        </div>

        <!-- Sales -->
        <div class="mb-6">
            <h3 class="text-sm font-bold text-blue-600 uppercase mb-3">Sales</h3>
            <ul class="space-y-1">
                <li>
                    <a href="{{ route('sales.index') }}" class="flex items-center px-3 py-2 text-gray-700 rounded-md hover:bg-gray-100 {{ request()->routeIs('sales.*') && !request()->routeIs('sales.invoices.*') && !request()->routeIs('sales.returns.*') && !request()->routeIs('sales.pos.*') ? 'bg-gray-100 font-medium' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        Sales
                    </a>
                </li>
                {{-- Sales Return link removed as per requirements --}}
                <li>
                    <a href="{{ route('sales.pos.index') }}" class="flex items-center px-3 py-2 text-gray-700 rounded-md hover:bg-gray-100 {{ request()->routeIs('sales.pos.*') ? 'bg-gray-100 font-medium' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        POS
                    </a>
                </li>
                {{-- Sales Report link removed as per requirements --}}
            </ul>
        </div>

        <!-- Orders -->
        <div class="mb-6">
            <h3 class="text-sm font-bold text-blue-600 uppercase mb-3">Orders</h3>
            <ul class="space-y-1">
                <li>
                    <a href="{{ route('orders.completed') }}" class="flex items-center px-3 py-2 text-gray-700 rounded-md hover:bg-gray-100 {{ request()->routeIs('orders.completed') ? 'bg-gray-100 font-medium' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        Completed
                    </a>
                </li>
                <li>
                    <a href="{{ route('orders.pending') }}" class="flex items-center px-3 py-2 text-gray-700 rounded-md hover:bg-gray-100 {{ request()->routeIs('orders.pending') ? 'bg-gray-100 font-medium' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Pending Order Amount
                    </a>
                </li>
                <li>
                    <a href="{{ route('orders.on-hold') }}" class="flex items-center px-3 py-2 text-gray-700 rounded-md hover:bg-gray-100 {{ request()->routeIs('orders.on-hold') ? 'bg-gray-100 font-medium' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        On-Hold Orders
                    </a>
                </li>
            </ul>
        </div>

        <!-- People -->
        <div class="mb-6">
            <h3 class="text-sm font-bold text-blue-600 uppercase mb-3">People</h3>
            <ul class="space-y-1">
                <li>
                    <a href="{{ route('customers.index') }}" class="flex items-center px-3 py-2 text-gray-700 rounded-md hover:bg-gray-100 {{ request()->routeIs('customers.*') ? 'bg-gray-100 font-medium' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        Customer
                    </a>
                </li>
                <li>
                    <a href="{{ route('suppliers.index') }}" class="flex items-center px-3 py-2 text-gray-700 rounded-md hover:bg-gray-100 {{ request()->routeIs('suppliers.*') ? 'bg-gray-100 font-medium' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                        Supplier
                    </a>
                </li>
            </ul>
        </div>

        <!-- AI Insights (online only) -->
        <div class="mb-6">
            <h3 class="text-sm font-bold text-blue-600 uppercase mb-3 flex items-center gap-1.5">
                AI Insights
                <span class="ftpos-online-only-badge hidden inline-flex text-red-500" title="Requires internet connection">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01M5 12.859a10 10 0 0114 0M1.889 8.314a15 15 0 0120.222 0M3 3l18 18" />
                    </svg>
                </span>
            </h3>
                <ul class="space-y-1">
                    <li>
                        <a href="{{ route('ai-insights.index') }}" title="Requires internet connection" data-requires-internet="1" class="flex items-center px-3 py-2 text-gray-700 rounded-md hover:bg-gray-100 {{ request()->routeIs('ai-insights.index') ? 'bg-gray-100 font-medium' : '' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                            </svg>
                            <span class="flex-1">AI Dashboard</span>
                            <x-online-only-lock />
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('ai-insights.forecast') }}" title="Requires internet connection" data-requires-internet="1" class="flex items-center px-3 py-2 text-gray-700 rounded-md hover:bg-gray-100 {{ request()->routeIs('ai-insights.forecast') ? 'bg-gray-100 font-medium' : '' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                            <span class="flex-1">Sales Forecast</span>
                            <x-online-only-lock />
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('ai-insights.inventory') }}" title="Requires internet connection" data-requires-internet="1" class="flex items-center px-3 py-2 text-gray-700 rounded-md hover:bg-gray-100 {{ request()->routeIs('ai-insights.inventory') ? 'bg-gray-100 font-medium' : '' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6m4 6V7m4 10V4M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2h-2M5 19a2 2 0 01-2-2V7a2 2 0 012-2h2m0 0V3m0 2h8"></path>
                            </svg>
                            <span class="flex-1">ABC Analysis</span>
                            <x-online-only-lock />
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('ai-insights.customers') }}" title="Requires internet connection" data-requires-internet="1" class="flex items-center px-3 py-2 text-gray-700 rounded-md hover:bg-gray-100 {{ request()->routeIs('ai-insights.customers') ? 'bg-gray-100 font-medium' : '' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5V9a2 2 0 00-2-2h-3m-4 13H6a2 2 0 01-2-2v-3m0 0V9a2 2 0 012-2h3m0 0V4a2 2 0 012-2h2a2 2 0 012 2v3m-6 0h6"></path>
                            </svg>
                            <span class="flex-1">Customer Segments</span>
                            <x-online-only-lock />
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('ai-insights.recommendations') }}" title="Requires internet connection" data-requires-internet="1" class="flex items-center px-3 py-2 text-gray-700 rounded-md hover:bg-gray-100 {{ request()->routeIs('ai-insights.recommendations') ? 'bg-gray-100 font-medium' : '' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                            </svg>
                            <span class="flex-1">Recommendations</span>
                            <x-online-only-lock />
                        </a>
                    </li>
                    {{-- <li>
                        <a href="{{ route('ai-insights.anomalies') }}" class="flex items-center px-3 py-2 text-gray-700 rounded-md hover:bg-gray-100 {{ request()->routeIs('ai-insights.anomalies') ? 'bg-gray-100 font-medium' : '' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                            Anomaly Detection
                        </a>
                    </li> --}}
                </ul>
        </div>

        <!-- User Activity -->
        <!-- <div class="mb-6">
            <h3 class="text-sm font-bold text-blue-600 uppercase mb-3">System</h3>
            <ul class="space-y-1">
                <li>
                    <a href="{{ route('user-activities.index') }}" class="flex items-center px-3 py-2 text-gray-700 rounded-md hover:bg-gray-100 {{ request()->routeIs('user-activities.*') ? 'bg-gray-100 font-medium' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        User Activity
                    </a>
                </li>
            </ul>
        </div> -->
    </nav>
</aside>

