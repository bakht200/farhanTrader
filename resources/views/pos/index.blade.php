<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>POS - {{ config('app.name', 'Laravel') }}</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }
        body {
            transform: scale(0.9);
            transform-origin: top left;
            width: 111.11%; /* Compensate for scale: 100% / 0.9 */
            height: 111.11vh; /* Use viewport height */
        }
    </style>
</head>
<body class="font-sans antialiased bg-gray-100 overflow-hidden">
    <!-- Top Header Bar -->
    <div class="bg-gray-800 text-white px-6 py-3 flex items-center justify-between">
        <div class="flex items-center space-x-4">
            <!-- Logo -->
            <div class="flex items-center space-x-2">
                <img src="{{ asset('logo.png') }}" alt="Farhan Traders Logo" class="h-10 w-auto">
            </div>
            <!-- Time -->
            <div class="bg-green-500 rounded-full px-4 py-1 text-sm font-medium" id="current-time">
                {{ now()->format('H:i') }}
            </div>
            <!-- Offline / online status (POS has no app-layout badge) -->
            <button type="button"
                id="pos-connectivity-status"
                class="rounded-full px-4 py-1 text-sm font-semibold whitespace-nowrap border-2 border-white/20 bg-green-600 text-white"
                title="Connection status">
                Online
            </button>
            <span id="pos-pending-sync" class="hidden rounded-full px-3 py-1 text-xs font-semibold bg-amber-400 text-gray-900"></span>
        </div>
        <div class="flex items-center space-x-3">
                    <a href="{{ route('orders.index') }}" class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded-md text-sm font-medium text-white whitespace-nowrap">View Orders</a>
                    <button onclick="resetCart()" class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded-md text-sm font-medium text-white whitespace-nowrap">Reset</button>
                    <button onclick="holdOrder()" class="bg-orange-500 hover:bg-orange-600 px-4 py-2 rounded-md text-sm font-medium text-white flex items-center space-x-2 whitespace-nowrap">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        <span>Hold</span>
                    </button>
                    <button onclick="openHoldOrdersModal()" class="bg-blue-500 hover:bg-blue-600 px-4 py-2 rounded-md text-sm font-medium text-white flex items-center space-x-2 whitespace-nowrap">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        <span>Load Hold Order</span>
                    </button>
                
            <a href="{{ route('dashboard') }}" class="bg-purple-600 hover:bg-purple-700 px-3 py-2 rounded-md text-sm font-medium flex items-center space-x-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                </svg>
                <span>Dashboard</span>
            </a>
            <x-quantity-alerts-bell variant="dark" />
            <button onclick="openCalendar()" class="p-2 hover:bg-gray-700 rounded" title="Calendar">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
            </button>
            <button onclick="toggleFullscreen()" class="p-2 hover:bg-gray-700 rounded" title="Fullscreen">
                <svg id="fullscreen-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path>
                </svg>
            </button>
            <button onclick="printOrder()" class="p-2 hover:bg-gray-700 rounded" title="Print">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                </svg>
            </button>
            <button onclick="toggleLeftPanel()" class="p-2 hover:bg-gray-700 rounded" title="Toggle Products Panel" id="toggle-left-panel-btn">
                <svg id="toggle-left-panel-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </button>
            <button onclick="toggleRightPanel()" class="p-2 hover:bg-gray-700 rounded" title="Toggle Order Panel" id="toggle-right-panel-btn">
                <svg id="toggle-right-panel-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </button>
            <a href="{{ route('profile.edit') }}" class="p-2 hover:bg-gray-700 rounded" title="Settings">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
            </a>
            <a href="{{ route('profile.edit') }}" class="p-2 hover:bg-gray-700 rounded" title="Profile">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
            </a>
        </div>
    </div>

    <div class="flex relative" style="height: calc(111.11vh - 64px); max-height: calc(111.11vh - 64px);">
        <!-- Left Panel - Product Catalog -->
        <div id="left-panel" class="bg-white transition-all duration-300 flex flex-col" style="width: 0%;">
            <!-- Navigation Buttons Below Header -->
            <div class="bg-white border-b border-gray-200 px-6 py-2 flex items-center space-x-3 flex-shrink-0">
                <a href="{{ route('orders.index') }}" class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded-md text-sm font-medium text-white">View Orders</a>
                <button onclick="resetCart()" class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded-md text-sm font-medium text-white">Reset</button>
                <button onclick="holdOrder()" class="bg-orange-500 hover:bg-orange-600 px-4 py-2 rounded-md text-sm font-medium text-white flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                    <span>Hold</span>
                </button>
                <button onclick="openHoldOrdersModal()" class="bg-blue-500 hover:bg-blue-600 px-4 py-2 rounded-md text-sm font-medium text-white flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    <span>Load Hold Order</span>
                </button>
            </div>
            
            <!-- Scrollable Content Area -->
            <div class="flex-1 overflow-y-auto">
                <!-- Categories Section -->
            <div class="p-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold mb-3">Categories</h3>
                <div class="flex items-center space-x-2 overflow-x-auto pb-2" id="category-buttons">
                    <button onclick="filterCategory('all')" 
                            id="category-btn-all"
                            class="flex items-center space-x-2 px-4 py-2 rounded-md whitespace-nowrap {{ $categoryId === 'all' ? 'bg-orange-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                        <span>All Categories</span>
                        <span class="text-xs">({{ $categories->sum('products_count') }} Items)</span>
                    </button>
                    @foreach($categories as $category)
                    <button onclick="filterCategory({{ $category->id }})" 
                            id="category-btn-{{ $category->id }}"
                            class="flex items-center space-x-2 px-4 py-2 rounded-md whitespace-nowrap {{ $categoryId == $category->id ? 'bg-orange-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                        <span>{{ $category->name }}</span>
                        <span class="text-xs">({{ $category->products_count }} Items)</span>
                    </button>
                    @endforeach
                </div>
            </div>

            <!-- Products Section -->
            <div class="p-4">
                <div class="mb-4">
                    <div class="relative">
                        <input type="text" 
                               id="product-search" 
                               placeholder="Q Search Product" 
                               value="{{ $search }}"
                               oninput="searchProducts(this.value)"
                               class="w-full px-4 py-2 pl-10 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                        <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>

                <div class="grid gap-2" id="products-grid" style="grid-template-columns: repeat(8, minmax(0, 1fr));">
                    @foreach($products as $product)
                    <div onclick="addToCart({{ $product->id }})" 
                         data-product-name="{{ $product->name }}"
                         data-product-sku="{{ $product->sku ?? '' }}"
                         data-product-brand="{{ $product->brand ?? '' }}"
                         data-category-id="{{ $product->category_id }}"
                         class="bg-white border border-gray-200 rounded-lg p-2 cursor-pointer hover:shadow-lg transition-shadow">
                        <div class="aspect-square bg-gray-100 rounded-lg mb-2 flex items-center justify-center overflow-hidden">
                            @if($product->image)
                                <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                            @else
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                            @endif
                        </div>
                        <h4 class="font-semibold text-xs mb-1 line-clamp-2">{{ $product->name }}</h4>
                        <p class="text-xs text-gray-500 mb-1">Remaining: {{ number_format($product->stock_quantity, 2) }} {{ $product->unit->short_name ?? 'Pcs' }}</p>
                        @php
                            $displayPrice = $product->selling_price;
                            if ($product->selling_type === 'retail' && $product->retail_price) {
                                $displayPrice = $product->retail_price;
                            } elseif ($product->selling_type === 'wholesale' && $product->wholesale_price) {
                                $displayPrice = $product->wholesale_price;
                            } elseif ($product->selling_type === 'both' && $product->retail_price) {
                                $displayPrice = $product->retail_price;
                            }
                        @endphp
                        <p class="text-sm font-bold text-orange-600">PKR {{ number_format($displayPrice, 2) }}</p>
                        @if($product->selling_type)
                            <p class="text-xs text-gray-400 mt-1">{{ ucfirst($product->selling_type) }}</p>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            </div>
        </div>

        <!-- Resizer -->
        <div id="panel-resizer" class="bg-gray-300 hover:bg-orange-500 cursor-col-resize transition-colors flex items-center justify-center relative" style="flex-shrink: 0; width: 8px; min-width: 8px;">
            <div class="absolute flex flex-col space-y-1.5 opacity-40 hover:opacity-100 transition-opacity pointer-events-none">
                <div class="w-1 h-3 bg-gray-600 rounded-full"></div>
                <div class="w-1 h-3 bg-gray-600 rounded-full"></div>
                <div class="w-1 h-3 bg-gray-600 rounded-full"></div>
            </div>
        </div>
        
        <!-- Right Panel - Order Management -->
        <div id="right-panel" class="bg-gray-50 border-l border-gray-200 flex flex-col transition-all duration-300 flex-shrink-0 overflow-hidden h-full" style="width: 100%;">
            <!-- Order List Header -->
            <div class="p-4 bg-white border-b border-gray-200">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h3 class="text-lg font-semibold">Order List</h3>
                    </div>    
                <!-- Action Buttons -->
               
                
                    <button onclick="clearCart()" class="text-red-600 hover:text-red-800" title="Clear Cart">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </div>
        
                <!-- Product Search in Right Panel -->
                <div class="relative">
                    <input type="text" 
                           id="right-product-search" 
                           placeholder="Search Product to Add..." 
                           autocomplete="off"
                           class="w-full px-4 py-2 pl-10 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
                           oninput="searchProductsRight(this.value); if(this.value.trim()) showProductSuggestions();"
                           onfocus="if(this.value.trim()) showProductSuggestions()"
                           onblur="setTimeout(() => hideProductSuggestions(), 200)"
                           onkeydown="handleProductSearchKeydown(event)">
                    <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <div id="product-suggestions-dropdown" class="hidden absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto">
                        <div id="product-suggestions-list" class="py-1">
                            <!-- Product suggestions will be populated here -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Added Section -->
            <div class="flex-1 overflow-y-auto p-4 min-h-0">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                    <div class="bg-orange-100 rounded-full p-2 mr-2">
                        <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold">Product Added</h3>
                        <span class="ml-2 bg-orange-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs font-bold" id="cart-count">0</span>
                    </div>
                </div>

                <div id="cart-items" class="overflow-x-auto">
                    <div class="text-center py-12 text-gray-400">
                        <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <p>No Products Selected</p>
                    </div>
                </div>
            </div>

            <!-- Customer Information -->
            <div class="p-4 bg-white border-t border-gray-200 flex-shrink-0">
                <h3 class="text-lg font-semibold mb-3">Customer Information</h3>
                <div class="space-y-2">
                    <div class="relative">
                        <div id="customer-dropdown" class="hidden absolute left-0 right-0 z-50 bottom-full mb-1 bg-white border border-gray-300 rounded-md shadow-lg flex flex-col max-h-72 min-h-0">
                            <div id="customer-list-scroll" class="overflow-y-auto overflow-x-hidden flex-1 min-h-0 overscroll-contain rounded-t-md">
                                <div id="customer-list" class="py-1">
                                    <!-- Customer options populated by JS -->
                                </div>
                            </div>
                            <div class="border-t border-gray-200 py-1 flex-shrink-0 bg-white rounded-b-md">
                                <button type="button" onclick="openNewCustomerModal()" class="w-full text-left px-4 py-2 text-sm text-blue-600 hover:bg-blue-50 flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    Add New Customer
                                </button>
                            </div>
                        </div>
                        <input type="text" 
                               id="customer-search" 
                               placeholder="Search by name, ID, or phone (use type filters below)" 
                               autocomplete="off"
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
                               oninput="filterCustomers()"
                               onfocus="showCustomerDropdown()"
                               onblur="setTimeout(() => hideCustomerDropdown(), 200)">
                        <input type="hidden" id="customer-id" value="">
                        <input type="hidden" id="customer-name" value="">
                    </div>
                    <div class="relative z-10 pointer-events-auto flex items-center gap-2 overflow-x-auto pb-1 -mx-1 px-1" id="customer-type-filters" role="group" aria-label="Customer type filters">
                        <button type="button"
                                id="customer-type-filter-all"
                                data-customer-type-filter="all"
                                class="customer-type-filter-chip pointer-events-auto flex-shrink-0 px-3 py-1.5 rounded-md text-sm font-medium whitespace-nowrap bg-orange-500 text-white"
                                onmousedown="event.preventDefault()"
                                onclick="setCustomerTypeFilter('all')">
                            All
                        </button>
                        @foreach($customerTypesForPos as $type)
                        <button type="button"
                                data-customer-type-filter="{{ $type }}"
                                class="customer-type-filter-chip pointer-events-auto flex-shrink-0 px-3 py-1.5 rounded-md text-sm font-medium whitespace-nowrap bg-gray-100 text-gray-700 hover:bg-gray-200"
                                onmousedown="event.preventDefault()"
                                onclick="setCustomerTypeFilter(@json($type))">
                            {{ $type }}
                        </button>
                        @endforeach
                    </div>
                </div>
                <div id="selected-customer" class="mt-2 text-sm text-gray-600 hidden">
                    <div class="flex items-center justify-between gap-2">
                        <div class="min-w-0">
                            <span id="selected-customer-name" class="font-medium text-gray-800"></span>
                            <span id="selected-customer-type" class="hidden text-xs font-medium text-orange-600 ml-2 align-middle"></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" 
                                    id="add-last-order-btn"
                                    onclick="addLastOrderToCart()" 
                                    class="hidden px-3 py-1 text-xs bg-blue-600 hover:bg-blue-700 text-white rounded-md font-medium">
                                Add Last Order
                            </button>
                            <button type="button" onclick="clearCustomerSelection()" class="text-red-600 hover:text-red-800">
                                <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Method - Moved to bottom -->
            <div class="p-4 bg-white border-t border-gray-200 flex-shrink-0">
                <div class="flex items-center gap-4">
                    <h3 class="text-lg font-semibold">
                        Payment Method 
                        <span class="text-orange-600 font-normal">(Cash)</span>
                    </h3>

                    <button id="grand-total-btn"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-semibold text-lg">
                        Grand Total : PKR 0.00
                    </button>

                    <button onclick="placeOrder()" 
                            class="flex-1 bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-md font-semibold">
                        Place Order
                    </button>
                </div>
            </div>

        </div>
    </div>

    <!-- Products Data for JavaScript -->
    <script>
        @php
            $productsData = $products->map(function($p) {
                // Get base unit
                $baseUnit = $p->base_unit_id ?? $p->unit_id;
                $baseUnitName = $p->baseUnit ? $p->baseUnit->short_name : ($p->unit ? $p->unit->short_name : '');
                // Branch overrides applied (base unit + scaled multi-unit prices)
                $sellingUnits = $p->sellingUnitsForPos();
                
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'sku' => $p->sku,
                    'brand' => $p->brand ?? '',
                    'purchase_price' => $p->purchase_price,
                    'selling_price' => $p->selling_price,
                    'retail_price' => $p->retail_price ?? $p->selling_price,
                    'wholesale_price' => $p->wholesale_price ?? $p->selling_price,
                    'selling_type' => $p->selling_type ?? 'retail',
                    'stock_quantity' => $p->stock_quantity,
                    'unit_id' => $baseUnit,
                    'unit_name' => $baseUnitName,
                    'base_unit_id' => $baseUnit,
                    'selling_units' => $sellingUnits, // Array of units with branch-aware prices
                    'image' => $p->image ? asset('storage/' . $p->image) : null,
                ];
            })->values();
        @endphp
        @php
            $customersData = $customers->map(function($c) {
                return [
                    'id' => $c->id,
                    'name' => $c->name,
                    'customer_id' => $c->customer_id ?? 'CN-' . str_pad($c->id, 3, '0', STR_PAD_LEFT),
                    'customer_type' => $c->customer_type ?? '',
                    'phone' => $c->phone ?? '',
                    'email' => $c->email ?? '',
                    'previous_balance' => $c->unpaid_amount ?? 0,
                ];
            })->values();
        @endphp
        const products = @json($productsData);
        const units = @json($units);
        const customersData = @json($customersData);
        const editOrder = @json($editOrderData ?? null);
        const editOrderId = @json($editOrderId ?? null);
        let cart = [];
        let paymentMethod = 'cash';
        let allCustomers = customersData;
        let customerTypeFilter = 'all';
        let orderId = editOrderId || 0;
        let customerPreviousBalance = 0;
        let purchasePriceVisible = false; // Purchase price masked by default (shows XXXX)
        let currentCategoryId = '{{ $categoryId }}'; // Current selected category
        let editingCustomProductIndex = null; // Track which custom product is being edited
        const conversionFactorCache = {};

        function getNumericStockQuantity(item) {
            return parseFloat(item?.stock_quantity) || 0;
        }

        function getItemBaseUnitId(item) {
            return Number(item?.base_unit_id ?? item?.unit_id) || null;
        }

        async function getConversionFactor(productId, fromUnitId, toUnitId) {
            const fromId = Number(fromUnitId);
            const toId = Number(toUnitId);
            if (!productId || !fromId || !toId) return null;
            if (fromId === toId) return 1;

            const cacheKey = `${productId}:${fromId}:${toId}`;
            if (Object.prototype.hasOwnProperty.call(conversionFactorCache, cacheKey)) {
                return conversionFactorCache[cacheKey];
            }

            try {
                const response = await fetch(`/products/${productId}/conversion/${fromId}/${toId}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok || !data.success || data.conversion_factor == null) {
                    conversionFactorCache[cacheKey] = null;
                    return null;
                }
                const factor = parseFloat(data.conversion_factor);
                if (Number.isNaN(factor) || factor <= 0) {
                    conversionFactorCache[cacheKey] = null;
                    return null;
                }
                conversionFactorCache[cacheKey] = factor;
                return factor;
            } catch (error) {
                console.warn('Failed to fetch conversion factor:', error);
                conversionFactorCache[cacheKey] = null;
                return null;
            }
        }

        async function convertQuantity(productId, quantity, fromUnitId, toUnitId) {
            const qty = parseFloat(quantity) || 0;
            const fromId = Number(fromUnitId);
            const toId = Number(toUnitId);
            if (qty <= 0) return 0;
            if (!fromId || !toId) return qty;
            if (fromId === toId) return qty;

            const directFactor = await getConversionFactor(productId, fromId, toId);
            if (directFactor != null) {
                return qty * directFactor;
            }

            const reverseFactor = await getConversionFactor(productId, toId, fromId);
            if (reverseFactor != null && reverseFactor > 0) {
                return qty / reverseFactor;
            }

            // Fallback when conversion is unavailable; keep existing behavior.
            return qty;
        }

        async function getMaxQuantityInSelectedUnit(item) {
            const stockInBase = getNumericStockQuantity(item);
            const baseUnitId = getItemBaseUnitId(item);
            const selectedUnitId = Number(item?.unit_id);
            if (!item?.product_id || !baseUnitId || !selectedUnitId || baseUnitId === selectedUnitId) {
                return stockInBase;
            }
            return convertQuantity(item.product_id, stockInBase, baseUnitId, selectedUnitId);
        }

        async function isQuantityWithinStock(item, requestedQuantity) {
            const reqQty = parseFloat(requestedQuantity) || 0;
            const stockInBase = getNumericStockQuantity(item);
            const baseUnitId = getItemBaseUnitId(item);
            const selectedUnitId = Number(item?.unit_id);
            if (reqQty <= 0) {
                return { allowed: true, maxSelected: await getMaxQuantityInSelectedUnit(item) };
            }

            const requestedInBase = (!item?.product_id || !baseUnitId || !selectedUnitId || baseUnitId === selectedUnitId)
                ? reqQty
                : await convertQuantity(item.product_id, reqQty, selectedUnitId, baseUnitId);

            const maxSelected = await getMaxQuantityInSelectedUnit(item);
            return {
                allowed: requestedInBase <= stockInBase + 0.000001,
                maxSelected,
                stockInBase,
                requestedInBase
            };
        }

        /**
         * Find non-custom cart lines that exceed available stock.
         * Used to block Place Order while still allowing add-to-cart after confirm.
         */
        async function getCartStockProblems() {
            const problems = [];
            for (const item of cart) {
                const isCustom = item.product_id === null || item.is_custom === true;
                if (isCustom) continue;
                const validation = await isQuantityWithinStock(item, item.quantity);
                if (!validation.allowed) {
                    problems.push({
                        name: item.name || 'Product',
                        requested: parseFloat(item.quantity) || 0,
                        available: parseFloat(validation.maxSelected || 0),
                        unit: item.unit_name || '',
                    });
                }
            }
            return problems;
        }

        async function assertCartHasStockForCheckout() {
            const problems = await getCartStockProblems();
            if (problems.length === 0) return true;

            const details = problems.map(p =>
                `• ${p.name}: need ${p.requested.toFixed(2)} ${p.unit}, stock ${p.available.toFixed(2)} ${p.unit}`
            ).join('\n');

            alert(
                'Cannot place order. One or more products are out of stock or exceed available quantity.\n\n' +
                details +
                '\n\nYou can keep them in the cart, but place order is blocked until stock is enough.'
            );
            return false;
        }

        async function handleQuantityInputChange(index, input) {
            const item = cart[index];
            if (!item || !input) return;
            const isCustom = item.product_id === null || item.is_custom === true;
            const val = parseFloat(input.value) || 0.01;

            if (isCustom) {
                item.quantity = parseFloat(val.toFixed(2));
                renderCart();
                return;
            }

            const validation = await isQuantityWithinStock(item, val);
            if (!validation.allowed) {
                const ok = confirm(
                    `"${item.name}" exceeds stock (${parseFloat(validation.maxSelected || 0).toFixed(2)} available).\n\n` +
                    'Click OK to keep this quantity in cart. Place order will stay blocked until stock is enough.'
                );
                if (!ok) {
                    input.value = parseFloat(item.quantity || 0).toFixed(2);
                    return;
                }
            }

            item.quantity = parseFloat(val.toFixed(2));
            renderCart();
        }

        // Product Search Functions for Right Panel (defined early for inline handlers)
        function searchProductsRight(query) {
            const searchTerm = (query || '').toLowerCase().trim();
            const suggestionsList = document.getElementById('product-suggestions-list');
            if (!suggestionsList) return;
            
            suggestionsList.innerHTML = '';
            
            if (!searchTerm) {
                return;
            }
            
            // Filter products based on search term
            const filtered = products.filter(product => 
                (product.name && product.name.toLowerCase().includes(searchTerm)) ||
                (product.sku && product.sku.toLowerCase().includes(searchTerm))
            );
            
            if (filtered.length > 0) {
                filtered.slice(0, 10).forEach(product => {
                    const item = document.createElement('div');
                    item.className = 'px-4 py-3 hover:bg-gray-100 cursor-pointer border-b border-gray-100 select-none';
                    item.style.userSelect = 'none';
                    item.style.webkitUserSelect = 'none';
                    item.setAttribute('data-product-id', product.id);
                    
                    // Calculate display price
                    let displayPrice = product.selling_price;
                    if (product.selling_type === 'retail' && product.retail_price) {
                        displayPrice = product.retail_price;
                    } else if (product.selling_type === 'wholesale' && product.wholesale_price) {
                        displayPrice = product.wholesale_price;
                    } else if (product.selling_type === 'both' && product.retail_price) {
                        displayPrice = product.retail_price;
                    }
                    
                    item.innerHTML = `
                        <div class="flex items-center justify-between pointer-events-none">
                            <div class="flex-1">
                                <div class="font-medium text-gray-900">${product.name}</div>
                                <div class="text-xs text-gray-500 mt-1">
                                    ${product.sku ? 'SKU: ' + product.sku : ''}
                                </div>
                                <div class="text-xs text-gray-500 mt-1">
                                    Stock: ${parseFloat(product.stock_quantity || 0).toFixed(2)} ${product.unit_name || 'Pcs'}
                                </div>
                            </div>
                            <div class="ml-4 text-right">
                                <div class="text-sm font-bold text-orange-600">PKR ${parseFloat(displayPrice).toFixed(2)}</div>
                                ${product.selling_type ? '<div class="text-xs text-gray-400 mt-1">' + product.selling_type.charAt(0).toUpperCase() + product.selling_type.slice(1) + '</div>' : ''}
                            </div>
                        </div>
                    `;
                    
                    // Function to handle adding to cart
                    const handleAddToCart = function(e) {
                        if (e) {
                            e.preventDefault();
                            e.stopPropagation();
                        }
                        addToCart(product.id);
                        document.getElementById('right-product-search').value = '';
                        hideProductSuggestions();
                        // Visual feedback
                        item.style.backgroundColor = '#fef3c7';
                        setTimeout(() => {
                            item.style.backgroundColor = '';
                        }, 200);
                    };
                    
                    // Click event for desktop
                    item.addEventListener('click', handleAddToCart);
                    
                    // Touch events for mobile
                    let touchStartTime = 0;
                    item.addEventListener('touchstart', function(e) {
                        touchStartTime = Date.now();
                        item.style.backgroundColor = '#fef3c7';
                    }, { passive: true });
                    
                    item.addEventListener('touchend', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        const touchDuration = Date.now() - touchStartTime;
                        // Only trigger if it was a quick tap (not a swipe)
                        if (touchDuration < 300) {
                            handleAddToCart(e);
                        }
                        item.style.backgroundColor = '';
                    });
                    
                    item.addEventListener('touchcancel', function() {
                        item.style.backgroundColor = '';
                    }, { passive: true });
                    
                    suggestionsList.appendChild(item);
                });
            } else {
                // No product found message
                const noProductItem = document.createElement('div');
                noProductItem.className = 'px-4 py-3 text-gray-500 italic text-center border-b border-gray-100';
                noProductItem.textContent = 'No product found';
                suggestionsList.appendChild(noProductItem);
                
                // Add Product button
                const addProductItem = document.createElement('div');
                addProductItem.className = 'px-4 py-3';
                addProductItem.innerHTML = `
                    <button onclick="openAddProductModal()" class="w-full bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-md font-medium inline-flex items-center justify-center space-x-2 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>Add Product</span>
                    </button>
                `;
                suggestionsList.appendChild(addProductItem);
            }
        }
        
        function showProductSuggestions() {
            const dropdown = document.getElementById('product-suggestions-dropdown');
            const searchInput = document.getElementById('right-product-search');
            if (dropdown && searchInput && searchInput.value.trim()) {
                searchProductsRight(searchInput.value);
                dropdown.classList.remove('hidden');
            }
        }
        
        function hideProductSuggestions() {
            const dropdown = document.getElementById('product-suggestions-dropdown');
            if (dropdown) {
                dropdown.classList.add('hidden');
            }
        }
        
        function handleProductSearchKeydown(event) {
            const dropdown = document.getElementById('product-suggestions-dropdown');
            const suggestionsList = document.getElementById('product-suggestions-list');
            
            if (!dropdown || !suggestionsList || dropdown.classList.contains('hidden')) {
                if (event.key === 'Enter' && event.target.value.trim()) {
                    // If Enter is pressed and there are suggestions, add first product
                    const firstItem = suggestionsList.querySelector('div.cursor-pointer');
                    if (firstItem && !firstItem.classList.contains('text-gray-500')) {
                        firstItem.click();
                    }
                }
                return;
            }
            
            // Get all clickable items (exclude "No product found" message)
            const items = Array.from(suggestionsList.querySelectorAll('div.cursor-pointer')).filter(
                item => !item.classList.contains('text-gray-500') && !item.classList.contains('italic')
            );
            const currentIndex = items.findIndex(item => item.classList.contains('bg-orange-50'));
            
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                if (currentIndex < items.length - 1) {
                    items.forEach(item => item.classList.remove('bg-orange-50'));
                    items[currentIndex + 1].classList.add('bg-orange-50');
                    items[currentIndex + 1].scrollIntoView({ block: 'nearest' });
                } else if (items.length > 0) {
                    items.forEach(item => item.classList.remove('bg-orange-50'));
                    items[0].classList.add('bg-orange-50');
                    items[0].scrollIntoView({ block: 'nearest' });
                }
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                if (currentIndex > 0) {
                    items.forEach(item => item.classList.remove('bg-orange-50'));
                    items[currentIndex - 1].classList.add('bg-orange-50');
                    items[currentIndex - 1].scrollIntoView({ block: 'nearest' });
                } else if (items.length > 0) {
                    items.forEach(item => item.classList.remove('bg-orange-50'));
                    items[items.length - 1].classList.add('bg-orange-50');
                    items[items.length - 1].scrollIntoView({ block: 'nearest' });
                }
            } else if (event.key === 'Enter') {
                event.preventDefault();
                if (currentIndex >= 0 && items[currentIndex]) {
                    items[currentIndex].click();
                } else if (items.length > 0) {
                    items[0].click();
                }
            } else if (event.key === 'Escape') {
                hideProductSuggestions();
                event.target.blur();
            }
        }

        // Update time
        setInterval(() => {
            const now = new Date();
            const currentTimeEl = document.getElementById('current-time');
            if (currentTimeEl) {
                currentTimeEl.textContent = now.toLocaleTimeString();
            }
        }, 1000);

        // Filter category (client-side filtering without page reload)
        function filterCategory(categoryId) {
            currentCategoryId = categoryId;
            
            // Update URL without reload
            const url = new URL(window.location);
            url.searchParams.set('category_id', categoryId);
            window.history.pushState({}, '', url);
            
            // Update button styles
            const categoryButtons = document.getElementById('category-buttons');
            if (categoryButtons) {
                const buttons = categoryButtons.querySelectorAll('button');
                buttons.forEach(btn => {
                    const btnId = btn.id;
                    if (btnId === `category-btn-${categoryId}`) {
                        btn.classList.remove('bg-gray-100', 'text-gray-700', 'hover:bg-gray-200');
                        btn.classList.add('bg-orange-500', 'text-white');
                    } else {
                        btn.classList.remove('bg-orange-500', 'text-white');
                        btn.classList.add('bg-gray-100', 'text-gray-700', 'hover:bg-gray-200');
                    }
                });
            }
            
            // Filter products (also respect current search term)
            const productsGrid = document.getElementById('products-grid');
            if (!productsGrid) return;
            
            const searchInput = document.getElementById('product-search');
            const searchTerm = (searchInput && searchInput.value ? searchInput.value : '').toLowerCase().trim();
            
            const productCards = productsGrid.children;
            for (let i = 0; i < productCards.length; i++) {
                const card = productCards[i];
                if (!card.hasAttribute('data-category-id')) continue;
                
                const cardCategoryId = card.getAttribute('data-category-id');
                const productName = (card.getAttribute('data-product-name') || '').toLowerCase();
                const productSku = (card.getAttribute('data-product-sku') || '').toLowerCase();
                
                // Check if matches category filter
                const matchesCategory = categoryId === 'all' || cardCategoryId === categoryId.toString();
                
                // Check if matches search term
                const matchesSearch = !searchTerm || 
                    productName.includes(searchTerm) || 
                    productSku.includes(searchTerm);
                
                if (matchesCategory && matchesSearch) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            }
        }

        // Search products (client-side filtering without page reload)
        function searchProducts(query) {
            const productsGrid = document.getElementById('products-grid');
            if (!productsGrid) return;
            
            const searchTerm = (query || '').toLowerCase().trim();
            
            // Get all product cards (direct children divs with data-product-name attribute)
            const productCards = productsGrid.children;
            
            for (let i = 0; i < productCards.length; i++) {
                const card = productCards[i];
                if (!card.hasAttribute('data-product-name')) continue;
                
                const productName = (card.getAttribute('data-product-name') || '').toLowerCase();
                const productSku = (card.getAttribute('data-product-sku') || '').toLowerCase();
                const cardCategoryId = card.getAttribute('data-category-id');
                
                // Check if matches search term
                const matchesSearch = !searchTerm || 
                    productName.includes(searchTerm) || 
                    productSku.includes(searchTerm);
                
                // Check if matches current category filter
                const matchesCategory = currentCategoryId === 'all' || cardCategoryId === currentCategoryId.toString();
                
                if (matchesSearch && matchesCategory) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            }
        }

        // Add to cart
        async function addToCart(productId) {
            // Convert productId to number for comparison (handle both string and number)
            const productIdNum = typeof productId === 'string' ? parseInt(productId, 10) : Number(productId);
            const product = products.find(p => Number(p.id) === productIdNum);
            if (!product) {
                console.error('Product not found:', productId, 'Type:', typeof productId, 'Converted:', productIdNum);
                console.error('Available products:', products.map(p => ({ id: p.id, name: p.name, idType: typeof p.id })));
                alert('Product not found. Please refresh the page.');
                return;
            }
            
            console.log('Adding product to cart:', product.name, 'ID:', product.id);

            // Out of stock: allow add after OK, but place order remains blocked
            if (product.stock_quantity <= 0) {
                const ok = confirm(
                    `"${product.name}" is out of stock.\n\n` +
                    'Click OK to add it to the cart anyway. Place order will stay blocked until stock is available.'
                );
                if (!ok) {
                    return;
                }
            }

            // Determine initial selling price based on selling_type (branch-resolved product rates)
            let initialPrice = product.selling_price;
            if (product.selling_type === 'retail' && product.retail_price) {
                initialPrice = product.retail_price;
            } else if (product.selling_type === 'wholesale' && product.wholesale_price) {
                initialPrice = product.wholesale_price;
            } else if (product.selling_type === 'both' && product.retail_price) {
                initialPrice = product.retail_price; // Default to retail for "both"
            }

            const existingItem = cart.find(item => Number(item.product_id) === productIdNum);
            if (existingItem) {
                const newQuantity = parseFloat((existingItem.quantity + 1).toFixed(2));
                const validation = await isQuantityWithinStock(existingItem, newQuantity);
                if (!validation.allowed) {
                    const ok = confirm(
                        `Only ${parseFloat(validation.maxSelected || 0).toFixed(2)} available in stock for "${existingItem.name}".\n\n` +
                        'Click OK to add more to the cart anyway. Place order will stay blocked until stock is enough.'
                    );
                    if (!ok) {
                        return;
                    }
                }
                existingItem.quantity = newQuantity;
            } else {
                // Get default unit (base unit or first selling unit)
                const defaultUnit = product.selling_units && product.selling_units.length > 0
                    ? product.selling_units.find(u => u.is_base_unit) || product.selling_units[0]
                    : { unit_id: product.unit_id, unit_name: product.unit_name, selling_price: initialPrice };
                // Prefer branch product rate; fall back to selling-unit price only if product rate is missing
                const cartPrice = parseFloat(initialPrice) || parseFloat(defaultUnit.selling_price) || 0;
                
                cart.push({
                    product_id: productIdNum, // Use the normalized ID
                    name: product.name,
                    purchase_price: parseFloat(product.purchase_price) || 0,
                    selling_price: cartPrice,
                    retail_price: parseFloat(product.retail_price || product.selling_price) || 0,
                    wholesale_price: parseFloat(product.wholesale_price || product.selling_price) || 0,
                    selling_type: product.selling_type || 'retail',
                    price_type: product.selling_type === 'both' ? 'retail' : product.selling_type, // For "both", default to retail
                    quantity: 1,
                    unit_id: defaultUnit.unit_id || product.unit_id,
                    unit_name: defaultUnit.unit_name || defaultUnit.unit_short_name || product.unit_name,
                    base_unit_id: product.base_unit_id || product.unit_id,
                    selling_units: product.selling_units || [], // Store configured units
                    stock_quantity: parseFloat(product.stock_quantity) || 0,
                    discount_type: 'percentage',
                    discount: 0,
                });
                await refreshLinePurchasePrice(cart.length - 1);
            }
            renderCart();
        }

        // Remove from cart
        function removeFromCart(index) {
            cart.splice(index, 1);
            renderCart();
        }

        // Update quantity
        async function updateQuantity(index, change) {
            const item = cart[index];
            const isCustom = item.product_id === null || item.is_custom === true;
            const newQuantity = parseFloat((item.quantity + change).toFixed(2));
            if (newQuantity > 0) {
                if (isCustom) {
                    item.quantity = newQuantity;
                    renderCart();
                    return;
                }

                const validation = await isQuantityWithinStock(item, newQuantity);
                if (validation.allowed) {
                    item.quantity = newQuantity;
                    renderCart();
                } else {
                    const ok = confirm(
                        `"${item.name}" exceeds stock (${parseFloat(validation.maxSelected || 0).toFixed(2)} available).\n\n` +
                        'Click OK to keep this quantity in cart. Place order will stay blocked until stock is enough.'
                    );
                    if (!ok) {
                        return;
                    }
                    item.quantity = newQuantity;
                    renderCart();
                }
            }
        }

        // Update discount
        function updateDiscount(index, type, value) {
            const item = cart[index];
            item.discount_type = type;
            item.discount = parseFloat(parseFloat(value || 0).toFixed(2));
            renderCart();
        }

        /**
         * Unit that product.purchase_price refers to: same as selling-unit "base" (is_base_unit),
         * so cost scales consistently with selling price when UOM changes. Falls back to product base_unit_id.
         */
        function getProductCostBaseUnitId(product) {
            if (!product) return null;
            const su = (product.selling_units || []).find(u => u.is_base_unit);
            if (su && su.unit_id != null && su.unit_id !== '') {
                return Number(su.unit_id);
            }
            return Number(product.base_unit_id ?? product.unit_id) || null;
        }

        /**
         * Scale purchase cost to the cart line's selected unit (same UOM as selling price).
         * Tries line→base (×), then base→line (÷), then selling-unit price ratio. If still unknown,
         * keeps raw cost and sets skip_sell_vs_purchase_warning so we do not false-alarm vs sub-units.
         */
        async function refreshLinePurchasePrice(index) {
            const item = cart[index];
            if (!item || item.product_id === null || item.is_custom === true) {
                return;
            }
            const product = products.find(p => Number(p.id) === Number(item.product_id));
            if (!product) {
                return;
            }
            const baseId = getProductCostBaseUnitId(product);
            const raw = parseFloat(product.purchase_price) || 0;
            const lineUnit = Number(item.unit_id);
            item.skip_sell_vs_purchase_warning = false;

            if (!lineUnit || !baseId || Number.isNaN(lineUnit) || Number.isNaN(baseId)) {
                item.purchase_price = raw;
                return;
            }
            if (lineUnit === baseId) {
                item.purchase_price = raw;
                return;
            }

            const convHeaders = { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
            async function fetchFactor(fromId, toId) {
                const res = await fetch(`/products/${product.id}/conversion/${fromId}/${toId}`, { headers: convHeaders });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.success || data.conversion_factor == null) {
                    return null;
                }
                const f = parseFloat(data.conversion_factor);
                if (Number.isNaN(f) || f <= 0) {
                    return null;
                }
                return f;
            }

            try {
                const fLineToBase = await fetchFactor(lineUnit, baseId);
                if (fLineToBase != null) {
                    item.purchase_price = raw * fLineToBase;
                    return;
                }
            } catch (e) {
                console.warn('Purchase cost: line→base conversion failed', e);
            }

            try {
                const fBaseToLine = await fetchFactor(baseId, lineUnit);
                if (fBaseToLine != null) {
                    item.purchase_price = raw / fBaseToLine;
                    return;
                }
            } catch (e) {
                console.warn('Purchase cost: base→line conversion failed', e);
            }

            const suBase = (product.selling_units || []).find(u => Number(u.unit_id) === baseId);
            const suLine = (product.selling_units || []).find(u => Number(u.unit_id) === lineUnit);
            const pBase = parseFloat(suBase && suBase.selling_price) || 0;
            const pLineCfg = parseFloat(suLine && suLine.selling_price) || 0;
            if (pBase > 0 && pLineCfg > 0) {
                item.purchase_price = raw * (pLineCfg / pBase);
                return;
            }

            item.purchase_price = raw;
            item.skip_sell_vs_purchase_warning = true;
        }

        async function handleSellingPriceInputChange(index, input) {
            const item = cart[index];
            if (!item || !input) return;
            const isCustom = item.product_id === null || item.is_custom === true;
            const newPrice = parseFloat(input.value) || 0;
            const prevSelling = parseFloat(item.selling_price) || 0;
            if (!isCustom) {
                await refreshLinePurchasePrice(index);
            }
            const purchasePrice = parseFloat(cart[index].purchase_price) || 0;
            if (!isCustom && !item.skip_sell_vs_purchase_warning && purchasePrice > 0 && newPrice < purchasePrice) {
                const msg = 'Warning: Selling price (PKR ' + newPrice.toFixed(2) + ') is less than purchase price (PKR ' + purchasePrice.toFixed(2) + '). Do you want to proceed?';
                if (!confirm(msg)) {
                    input.value = prevSelling.toFixed(2);
                    return;
                }
            }
            item.selling_price = parseFloat(newPrice.toFixed(2));
            renderCart();
        }

        async function refreshAllCartPurchasePrices() {
            for (let i = 0; i < cart.length; i++) {
                await refreshLinePurchasePrice(i);
            }
            renderCart();
        }

        // Update unit and recalculate price
        async function updateUnitAndPrice(index, newUnitId) {
            const item = cart[index];
            const isCustom = item.product_id === null || item.is_custom === true;
            
            if (isCustom) {
                // For custom products, just update unit_id
                item.unit_id = newUnitId;
                const selectedUnit = units.find(u => u.id == newUnitId);
                if (selectedUnit) {
                    item.unit_name = selectedUnit.short_name;
                }
                renderCart();
                return;
            }
            
            // Find product
            const product = products.find(p => p.id == item.product_id);
            if (!product) {
                console.error('Product not found for unit update');
                return;
            }
            
            // Update unit_id
            item.unit_id = newUnitId;
            
            // Find unit configuration in selling_units
            const unitConfig = product.selling_units && product.selling_units.length > 0
                ? product.selling_units.find(u => u.unit_id == newUnitId)
                : null;
            
            if (unitConfig) {
                // Update unit name
                item.unit_name = unitConfig.unit_name || unitConfig.unit_short_name || '';
                
                // Branch product rates (for base unit) — prefer over shared unit table
                let branchBasePrice = parseFloat(product.selling_price) || 0;
                if (product.selling_type === 'retail' && product.retail_price) {
                    branchBasePrice = parseFloat(product.retail_price) || branchBasePrice;
                } else if (product.selling_type === 'wholesale' && product.wholesale_price) {
                    branchBasePrice = parseFloat(product.wholesale_price) || branchBasePrice;
                } else if (product.selling_type === 'both' && product.retail_price) {
                    branchBasePrice = parseFloat(product.retail_price) || branchBasePrice;
                }

                // Update price
                if (unitConfig.is_base_unit && branchBasePrice > 0) {
                    item.selling_price = branchBasePrice;
                } else if (unitConfig.selling_price && unitConfig.selling_price > 0) {
                    // Use branch-scaled unit price from selling_units
                    item.selling_price = parseFloat(unitConfig.selling_price);
                } else {
                    // Calculate from base unit using conversion factor
                    const baseUnit = product.selling_units.find(u => u.is_base_unit);
                    const basePrice = branchBasePrice || (baseUnit ? parseFloat(baseUnit.selling_price) : 0);
                    if (baseUnit && baseUnit.unit_id != newUnitId && basePrice) {
                        try {
                            const response = await fetch(`/products/${product.id}/conversion/${baseUnit.unit_id}/${newUnitId}`);
                            const data = await response.json();
                            if (data.success && data.conversion_factor) {
                                // Price = base_unit_price × conversion_factor
                                item.selling_price = parseFloat(basePrice) * parseFloat(data.conversion_factor);
                            } else {
                                console.warn('Conversion factor not found, using base unit price');
                                item.selling_price = parseFloat(basePrice);
                            }
                        } catch (e) {
                            console.error('Error fetching conversion factor:', e);
                            // Fallback to base unit price
                            if (basePrice) {
                                item.selling_price = parseFloat(basePrice);
                            }
                        }
                    } else if (basePrice) {
                        // Same unit as base, use base price
                        item.selling_price = parseFloat(basePrice);
                    }
                }
            } else {
                // No unit config found, try to find unit in global units list
                const selectedUnit = units.find(u => u.id == newUnitId);
                if (selectedUnit) {
                    item.unit_name = selectedUnit.short_name;
                }
                // Keep existing price (no conversion available)
            }

            await refreshLinePurchasePrice(index);
            renderCart();
        }

        // Calculate total (cart items only, excluding previous balance)
        function calculateTotal() {
            let total = 0;
            cart.forEach(item => {
                let itemTotal = item.quantity * item.selling_price;
                if (item.discount > 0) {
                    if (item.discount_type === 'percentage') {
                        itemTotal -= itemTotal * (item.discount / 100);
                    } else {
                        itemTotal -= item.discount;
                    }
                }
                total += itemTotal;
            });
            return total;
        }
        
        // Calculate grand total (including previous balance)
        function calculateGrandTotal() {
            return calculateTotal() + customerPreviousBalance;
        }

        // Render cart
        async function renderCart() {
            const container = document.getElementById('cart-items');
            const cartCount = document.getElementById('cart-count');
            
            if (!container) {
                console.error('Cart container element not found!');
                return;
            }
            
            if (cart.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-12 text-gray-400">
                        <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <p>No Products Selected</p>
                    </div>
                    <div class="px-4 pb-2 flex items-center justify-between">
                        <button onclick="togglePurchasePrice()" class="text-xs text-gray-500 hover:text-gray-700 underline">
                            ${purchasePriceVisible ? 'Hide' : 'Show'} Purchase Price
                        </button>
                        <button type="button" title="Shortcut: Shift+C" onclick="openCustomProductModal()" class="text-xs bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded">
                            Add Custom Product (Shift + C)
                        </button>
                    </div>
                `;
                const grandTotalBtn = document.getElementById('grand-total-btn');
                if (grandTotalBtn) {
                    grandTotalBtn.textContent = `Grand Total: ${formatCurrency(calculateGrandTotal())}`;
                }
                cartCount.textContent = '0';
                return;
            }

            cartCount.textContent = cart.length;

            let html = `
                <table class="min-w-full divide-y divide-gray-200 bg-white">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" onclick="togglePurchasePrice()" style="cursor: pointer;" title="Click to show/hide price">
                                Pur.Price
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">QTY / UOM</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Remaining Stock</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Selling Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Selling Price</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Discount</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
            `;

            for (let index = 0; index < cart.length; index++) {
                const item = cart[index];
                // Ensure all numeric values are numbers
                const quantity = parseFloat(item.quantity) || 0;
                const sellingPrice = parseFloat(item.selling_price) || 0;
                const purchasePrice = parseFloat(item.purchase_price) || 0;
                
                let itemTotal = quantity * sellingPrice;
                let discount = 0;
                if (item.discount > 0) {
                    if (item.discount_type === 'percentage') {
                        discount = itemTotal * (parseFloat(item.discount) / 100);
                    } else {
                        discount = parseFloat(item.discount) || 0;
                    }
                }
                itemTotal -= discount;

                const selectedUnit = units.find(u => u.id == item.unit_id);
                const unitName = selectedUnit ? selectedUnit.short_name : 'Pcs';

                const isCustom = item.product_id === null || item.is_custom === true;
                let maxSelectedQty = 0;
                let remainingSelectedQty = 0;
                let stockDisplay = '<div class="text-xs text-blue-500">Custom Product</div>';
                if (!isCustom) {
                    maxSelectedQty = await getMaxQuantityInSelectedUnit(item);
                    remainingSelectedQty = Math.max(0, (parseFloat(maxSelectedQty) || 0) - quantity);
                    stockDisplay = `
                        <div class="text-xs text-gray-500">In Stock: ${parseFloat(maxSelectedQty || 0).toFixed(2)} ${unitName}</div>
                    `;
                }
                const purchasePriceDisplay = isCustom ? '<div class="text-sm text-gray-400">-</div>' : `<div class="text-sm text-gray-900">${purchasePriceVisible ? formatCurrency(purchasePrice) : '****'}</div>`;
                const remainingStockDisplay = isCustom
                    ? '<div class="text-xs text-blue-500">Custom</div>'
                    : `<div class="text-sm font-medium text-amber-600">${parseFloat(remainingSelectedQty || 0).toFixed(2)} ${unitName}</div>`;
                const quantityValidation = 'onchange="void handleQuantityInputChange(' + index + ', this);"';
                const priceValidation = isCustom ? 'onchange="const newPrice = parseFloat(this.value) || 0; cart[' + index + '].selling_price = parseFloat(newPrice.toFixed(2)); renderCart();"' : 'onchange="void handleSellingPriceInputChange(' + index + ', this);"';
                const minPrice = isCustom ? '0' : ((item.skip_sell_vs_purchase_warning ? '0' : purchasePrice.toString()));
                
                html += `
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div>
                                <div class="font-semibold text-gray-900">${item.name}</div>
                                ${stockDisplay}
                        </div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap" ${!isCustom ? 'onclick="togglePurchasePrice()" style="cursor: pointer;" title="Click to show/hide price"' : ''}>
                            ${purchasePriceDisplay}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="flex items-center space-x-1">
                                <button onclick="updateQuantity(${index}, -0.1)" class="px-2 py-1 bg-gray-200 hover:bg-gray-300 rounded text-sm">-</button>
                            <input type="number" 
                                   value="${parseFloat(item.quantity || 0).toFixed(2)}" 
                                   min="0.01" 
                                   step="0.01"
                                   ${quantityValidation}
                                   class="w-20 px-2 py-1 border border-gray-300 rounded text-sm text-center">
                                <button onclick="updateQuantity(${index}, 0.1)" class="px-2 py-1 bg-gray-200 hover:bg-gray-300 rounded text-sm">+</button>
                                <select onchange="updateUnitAndPrice(${index}, parseInt(this.value));" class="px-2 py-1 border border-gray-300 rounded text-sm ml-1">
                                ${(() => {
                                    const isCustom = item.product_id === null || item.is_custom === true;
                                    
                                    // For custom products, show all units
                                    if (isCustom) {
                                        return units.map(u => 
                                            `<option value="${u.id}" ${u.id == item.unit_id ? 'selected' : ''}>${u.short_name}</option>`
                                        ).join('');
                                    }
                                    
                                    // For regular products, filter units to only show configured ones
                                    const availableUnits = item.selling_units && item.selling_units.length > 0
                                        ? item.selling_units.map(su => ({
                                            id: su.unit_id,
                                            short_name: su.unit_name || su.unit_short_name || 'Pcs'
                                        }))
                                        : units.filter(u => u.id == item.unit_id); // Fallback to product unit
                                    
                                    return availableUnits.map(u => 
                                        `<option value="${u.id}" ${u.id == item.unit_id ? 'selected' : ''}>${u.short_name}</option>`
                                    ).join('');
                                })()}
                            </select>
                        </div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            ${remainingStockDisplay}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            ${item.selling_type === 'both' 
                                ? `<select onchange="cart[${index}].price_type = this.value; cart[${index}].selling_price = this.value === 'retail' ? cart[${index}].retail_price : cart[${index}].wholesale_price; renderCart();" class="w-28 px-2 py-1 border border-gray-300 rounded text-sm">
                                    <option value="retail" ${item.price_type === 'retail' ? 'selected' : ''}>Retail</option>
                                    <option value="wholesale" ${item.price_type === 'wholesale' ? 'selected' : ''}>Wholesale</option>
                                  </select>`
                                : `<div class="text-xs text-gray-600">${item.selling_type ? item.selling_type.charAt(0).toUpperCase() + item.selling_type.slice(1) : 'Retail'}</div>`
                            }
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="relative inline-flex items-center gap-1">
                                <input type="number"
                                       id="selling-price-input-${index}"
                                       value="${parseFloat(sellingPrice || 0).toFixed(2)}"
                                       min="${minPrice}"
                                       step="0.01"
                                       ${priceValidation}
                                       class="w-24 px-2 py-1 border border-gray-300 rounded text-sm">
                                ${(!isCustom && document.getElementById('customer-id')?.value) ? `
                                    <button type="button"
                                            onclick="event.stopPropagation(); toggleLastPriceHint(${index})"
                                            class="p-1 rounded text-gray-500 hover:text-orange-600 hover:bg-orange-50"
                                            title="Show last sold price for this customer">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </button>
                                    <div id="last-price-hint-${index}" class="hidden absolute left-0 top-full mt-1 z-50 w-64 rounded-md border border-gray-200 bg-white shadow-lg p-3 text-left"></div>
                                ` : ''}
                            </div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="flex items-center space-x-1">
                                <input type="number" 
                                       value="${parseFloat(item.discount || 0).toFixed(2)}" 
                                       min="0"
                                       step="0.01"
                                       onchange="updateDiscount(${index}, cart[${index}].discount_type, this.value)"
                                       class="w-20 px-2 py-1 border border-gray-300 rounded text-sm">
                                <select onchange="updateDiscount(${index}, this.value, cart[${index}].discount)" class="px-2 py-1 border border-gray-300 rounded text-sm">
                                    <option value="percentage" ${item.discount_type === 'percentage' ? 'selected' : ''}>%</option>
                                    <option value="fixed" ${item.discount_type === 'fixed' ? 'selected' : ''}>Rs</option>
                                </select>
                            </div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="text-sm font-semibold text-gray-900">${formatCurrency(itemTotal)}</div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-center">
                            <div class="flex items-center justify-center space-x-2">
                                ${isCustom ? `
                                    <button onclick="editCustomProduct(${index})" class="text-blue-600 hover:text-blue-800" title="Edit">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>
                                ` : ''}
                                <button onclick="removeFromCart(${index})" class="text-red-600 hover:text-red-800" title="Remove">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }

            html += `
                    </tbody>
                </table>
                <div class="px-4 pb-2 pt-2 flex items-center justify-between">
                    <button onclick="togglePurchasePrice()" class="text-xs text-gray-500 hover:text-gray-700 underline">
                        ${purchasePriceVisible ? 'Hide' : 'Show'} Purchase Price
                    </button>
                    <button type="button" title="Shortcut: Shift+C" onclick="openCustomProductModal()" class="text-xs bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded">
                        Add Custom Product (Shift + C)
                    </button>
                </div>
            `;

            container.innerHTML = html;
            const grandTotalBtn = document.getElementById('grand-total-btn');
            if (grandTotalBtn) {
                grandTotalBtn.textContent = `Grand Total: ${formatCurrency(calculateGrandTotal())}`;
            }
        }

        // Format currency
        function formatCurrency(amount) {
            return 'PKR ' + parseFloat(amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }

        // Cache last customer+product prices for POS eye hint
        const lastPriceCache = {};

        function closeAllLastPriceHints(exceptIndex = null) {
            document.querySelectorAll('[id^="last-price-hint-"]').forEach((el) => {
                const idx = parseInt(el.id.replace('last-price-hint-', ''), 10);
                if (exceptIndex !== null && idx === exceptIndex) return;
                el.classList.add('hidden');
                el.innerHTML = '';
            });
        }

        async function toggleLastPriceHint(index) {
            const item = cart[index];
            const hintEl = document.getElementById(`last-price-hint-${index}`);
            const customerId = document.getElementById('customer-id')?.value;

            if (!hintEl || !item || item.product_id === null || item.is_custom) return;

            if (!customerId) {
                alert('Please select a customer first to see last sold price.');
                return;
            }

            if (!hintEl.classList.contains('hidden')) {
                hintEl.classList.add('hidden');
                hintEl.innerHTML = '';
                return;
            }

            closeAllLastPriceHints(index);
            hintEl.classList.remove('hidden');
            hintEl.innerHTML = `<div class="text-xs text-gray-500">Loading last price...</div>`;

            const cacheKey = `${customerId}-${item.product_id}-${item.unit_id || 'any'}`;
            let data = lastPriceCache[cacheKey];

            if (!data) {
                try {
                    let url = `{{ url('/sales/pos/last-price') }}/${customerId}/${item.product_id}`;
                    if (item.unit_id) {
                        url += `?unit_id=${encodeURIComponent(item.unit_id)}`;
                    }
                    const response = await fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    data = await response.json();
                    if (response.ok && data.success) {
                        lastPriceCache[cacheKey] = data;
                    }
                } catch (e) {
                    hintEl.innerHTML = `<div class="text-xs text-red-600">Could not load last price.</div>`;
                    return;
                }
            }

            if (!data || !data.success) {
                hintEl.innerHTML = `
                    <div class="text-xs text-gray-600">No previous price for this customer.</div>
                `;
                return;
            }

            const unitNote = data.matched_unit === false
                ? `<div class="text-[11px] text-amber-600 mt-1">Last sold in ${data.unit_name || 'another unit'}</div>`
                : '';

            hintEl.innerHTML = `
                <div class="text-xs font-semibold text-gray-800 mb-1">Last sold to this customer</div>
                <div class="text-sm font-bold text-orange-600">${formatCurrency(data.unit_price)} / ${data.unit_name || ''}</div>
                <div class="text-[11px] text-gray-500 mt-1">
                    ${data.sale_number || ''} ${data.sale_date ? '· ' + data.sale_date : ''}
                </div>
                ${unitNote}
                <button type="button"
                        onclick="useLastSoldPrice(${index}, ${parseFloat(data.unit_price)})"
                        class="mt-2 w-full text-xs bg-orange-500 hover:bg-orange-600 text-white px-2 py-1.5 rounded">
                    Use this price
                </button>
            `;
        }

        function useLastSoldPrice(index, price) {
            const item = cart[index];
            if (!item) return;
            item.selling_price = parseFloat(parseFloat(price || 0).toFixed(2));
            item.skip_sell_vs_purchase_warning = true;
            closeAllLastPriceHints();
            renderCart();
        }

        document.addEventListener('click', function (e) {
            if (!e.target.closest('[id^="last-price-hint-"]') && !e.target.closest('button[onclick*="toggleLastPriceHint"]')) {
                closeAllLastPriceHints();
            }
        });

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

        // Set payment method (always cash for now)
        function setPaymentMethod(method) {
            paymentMethod = method;
        }

        // Store order data for printing
        let lastOrderData = null;

        // Place order - show payment popup first
        async function placeOrder() {
            if (cart.length === 0) {
                alert('Please add products to cart');
                return;
            }

            if (!await assertCartHasStockForCheckout()) {
                return;
            }
            
            // Show payment popup
            showPaymentPopup();
        }

        // Show payment popup
        function showPaymentPopup() {
            const modal = document.getElementById('payment-popup-modal');
            const grandTotalDisplay = document.getElementById('payment-grand-total');
            const paidAmountInput = document.getElementById('paid-amount-input');
            const previousBalanceDisplay = document.getElementById('previous-balance-display');
            const previousBalanceSection = document.getElementById('previous-balance-section');
            
            if (!modal) return;
            
            // Calculate totals
            const cartTotal = calculateTotal();
            const grandTotal = calculateGrandTotal();
            
            // Show cart total
            const cartTotalDisplay = document.getElementById('payment-cart-total');
            if (cartTotalDisplay) {
                cartTotalDisplay.textContent = formatCurrency(cartTotal);
            }
            
            // Show/hide previous balance section
            if (customerPreviousBalance > 0 && previousBalanceSection) {
                previousBalanceSection.classList.remove('hidden');
                if (previousBalanceDisplay) {
                    previousBalanceDisplay.textContent = formatCurrency(customerPreviousBalance);
                }
            } else if (previousBalanceSection) {
                previousBalanceSection.classList.add('hidden');
            }
            
            // Update grand total display
            if (grandTotalDisplay) {
                grandTotalDisplay.textContent = formatCurrency(grandTotal);
            }
            
            // Update paid amount input
            if (paidAmountInput) {
                paidAmountInput.value = grandTotal.toFixed(2); // Default to full amount
                paidAmountInput.focus();
                paidAmountInput.select();
            }
            
            // Set default comment value
            const commentInput = document.getElementById('payment-comment-input');
            if (commentInput && !commentInput.value.trim()) {
                commentInput.value = 'pos order';
            }
            
            calculatePaymentBalance();
            modal.classList.remove('hidden');
        }

        // Close payment popup
        function closePaymentPopup() {
            const modal = document.getElementById('payment-popup-modal');
            if (modal) {
                modal.classList.add('hidden');
            }
        }

        // Calculate payment balance
        function calculatePaymentBalance() {
            const paidAmountInput = document.getElementById('paid-amount-input');
            const balanceDisplay = document.getElementById('payment-balance-display');
            
            if (!paidAmountInput || !balanceDisplay) return;
            
            const grandTotal = calculateGrandTotal();
            const paidAmount = parseFloat(paidAmountInput.value) || 0;
            const balance = grandTotal - paidAmount;
            
            if (balance <= 0) {
                balanceDisplay.innerHTML = '<span class="text-green-600">PKR 0.00 (Fully Paid)</span>';
            } else {
                balanceDisplay.innerHTML = `<span class="text-red-600">PKR ${balance.toFixed(2)}</span>`;
            }
        }

        // Confirm payment and place order
        async function confirmPayment() {
            const paidAmountInput = document.getElementById('paid-amount-input');
            if (!paidAmountInput) return;

            if (!await assertCartHasStockForCheckout()) {
                return;
            }
            
            const grandTotal = calculateGrandTotal();
            const paidAmount = parseFloat(paidAmountInput.value) || 0;
            
            if (paidAmount < 0) {
                alert('Paid amount cannot be negative');
                return;
            }
            
            if (paidAmount > grandTotal) {
                if (!confirm('Paid amount is greater than total amount. Continue?')) {
                    return;
                }
            }
            
            // If remaining balance > 0, customer must be selected (Walk-in not allowed)
            const remainingBalance = Math.max(0, grandTotal - paidAmount);
            const customerId = document.getElementById('customer-id').value;
            if (remainingBalance > 0 && !customerId) {
                alert('Remaining balance is not zero. Please select a customer. Walk-in customer is only allowed when the order is fully paid.');
                return;
            }

            if (window.FTReceipt && typeof window.FTReceipt.requireConfigured === 'function') {
                try {
                    await window.FTReceipt.requireConfigured({
                        description: 'Receipt details are required for this branch before confirming payment. You can also change these anytime from <strong>Receipt Settings</strong> in the menu.',
                        saveLabel: 'Save & Continue',
                    });
                } catch (e) {
                    return;
                }
            }
            
            // Close payment popup
            closePaymentPopup();
            
            // Proceed with order placement
            processOrder(paidAmount);
        }

        // Process order with paid amount
        function processOrder(paidAmount) {
            const formData = new FormData();
            const customerId = document.getElementById('customer-id').value;
            const customerNameInput = document.getElementById('customer-name').value;
            // Use "Walk-in Customer" if customer name is empty
            const customerName = customerNameInput.trim() || 'Walk-in Customer';
            const orderComment = (document.getElementById('payment-comment-input') && document.getElementById('payment-comment-input').value) ? document.getElementById('payment-comment-input').value.trim() : '';
            
            // Add order_id if editing (only if orderId is set and greater than 0)
            // For new orders, orderId should be 0 or null, so don't send order_id
            if (orderId && orderId > 0) {
                formData.append('order_id', orderId);
            }
            
            if (customerId) {
                formData.append('customer_id', customerId);
            }
            // Always send customer_name
            formData.append('customer_name', customerName);
            formData.append('payment_method', paymentMethod);
            formData.append('paid_amount', paidAmount);
            
            // Add comment (required)
            const commentInput = document.getElementById('payment-comment-input');
            if (commentInput) {
                formData.append('comment', commentInput.value.trim());
            }
            cart.forEach((item, index) => {
                const isCustom = item.product_id === null || item.is_custom === true;
                if (isCustom) {
                    // Custom product - send product_name instead of product_id
                    formData.append(`items[${index}][product_name]`, item.name);
                    formData.append(`items[${index}][is_custom]`, '1');
                } else {
                    // Regular product - send product_id
                    formData.append(`items[${index}][product_id]`, item.product_id);
                }
                formData.append(`items[${index}][quantity]`, item.quantity);
                formData.append(`items[${index}][unit_id]`, item.unit_id || '');
                formData.append(`items[${index}][selling_price]`, item.selling_price);
                formData.append(`items[${index}][discount_type]`, item.discount_type);
                formData.append(`items[${index}][discount]`, item.discount);
            });

            const placeOrderOnline = () => fetch('{{ route("sales.pos.process") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            const runPlaceOrder = async () => {
                const offline = window.FTOffline && window.FTOffline.isOnline && !window.FTOffline.isOnline();
                if (offline && window.FTOffline.queueOfflineSale) {
                    const payload = {
                        customer_id: formData.get('customer_id') || null,
                        customer_name: formData.get('customer_name') || 'Walk-in Customer',
                        payment_method: formData.get('payment_method') || 'cash',
                        paid_amount: formData.get('paid_amount'),
                        comment: formData.get('comment'),
                        items: cart.map((item) => {
                            const isCustom = item.product_id === null || item.is_custom === true;
                            return {
                                product_id: isCustom ? null : item.product_id,
                                product_name: isCustom ? item.name : undefined,
                                is_custom: isCustom ? '1' : '0',
                                quantity: item.quantity,
                                unit_id: item.unit_id || null,
                                selling_price: item.selling_price,
                                discount_type: item.discount_type,
                                discount: item.discount,
                            };
                        }),
                    };
                    const result = await window.FTOffline.queueOfflineSale(payload);
                    return {
                        success: true,
                        offline: true,
                        client_uuid: result.clientUuid,
                        message: 'Saved locally — will sync when online',
                        sale_id: result.localSale.id,
                    };
                }
                return placeOrderOnline().then(async response => {
                const contentType = response.headers.get('content-type');
                let data;
                
                if (contentType && contentType.includes('application/json')) {
                    data = await response.json();
                } else {
                    const text = await response.text();
                    throw new Error(text || 'Failed to place order');
                }
                
                if (!response.ok) {
                    // Handle validation errors
                    if (data.errors) {
                        let errorMessages = [];
                        for (let field in data.errors) {
                            errorMessages.push(data.errors[field].join(', '));
                        }
                        throw new Error(errorMessages.join('\n'));
                    }
                    throw new Error(data.message || 'Failed to place order');
                }
                
                return data;
            });
            };

            runPlaceOrder()
            .then(data => {
                if (data.success) {
                    if (Array.isArray(data.stock_alerts) && typeof window.handleStockAlerts === 'function') {
                        window.handleStockAlerts(data.stock_alerts);
                    }
                    if (data.offline) {
                        const cartTotal = typeof calculateTotal === 'function' ? calculateTotal() : 0;
                        const previousBalance = typeof customerPreviousBalance !== 'undefined' ? (customerPreviousBalance ?? 0) : 0;
                        const paidAmountForReceipt = parseFloat(paidAmount != null ? paidAmount : cartTotal) || 0;
                        const grandTotal = cartTotal + previousBalance;
                        const balance = Math.max(0, grandTotal - paidAmountForReceipt);
                        const offlineSaleNo = 'SALE-TMP-' + String(data.client_uuid || Date.now()).replace(/\D/g, '').slice(-6).padStart(6, '0');

                        lastOrderData = {
                            saleNumber: offlineSaleNo,
                            customerName: customerName || 'Walk-in Customer',
                            paymentMethod: paymentMethod || 'cash',
                            comment: orderComment || '',
                            items: JSON.parse(JSON.stringify(cart)),
                            cartTotal: cartTotal,
                            grandTotal: grandTotal,
                            paidAmount: paidAmountForReceipt,
                            balance: balance,
                            previousBalance: previousBalance,
                            previousBalancePayment: 0,
                            orderDate: typeof formatDateTime === 'function' ? formatDateTime(new Date().toISOString()) : new Date().toLocaleString(),
                        };

                        if (typeof closePaymentModal === 'function') closePaymentModal();
                        showBillPopup(offlineSaleNo);
                        orderId = 0;
                        if (typeof clearCart === 'function') {
                            clearCart();
                        } else {
                            cart = [];
                            if (typeof updateCartDisplay === 'function') updateCartDisplay();
                        }
                        return;
                    }
                    // If editing, fetch updated order data and show bill
                    if (data.is_edit) {
                        // Fetch the updated sale/order data - try sales route first, then orders
                        const saleId = data.sale_id || orderId;
                        Promise.race([
                            fetch(`/sales/${saleId}`, {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            }).then(r => r.ok ? r.json() : Promise.reject()),
                            fetch(`/orders/${saleId}`, {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            }).then(r => r.ok ? r.json() : Promise.reject())
                        ])
                        .then(responseData => {
                            const orderData = responseData.sale || responseData.order || responseData;
                            if (orderData.error) {
                                throw new Error(orderData.error);
                            }
                            
                            const order = orderData.sale || orderData.order || orderData;
                            const items = order.items || (orderData.items || []);
                            const cartTotal = parseFloat(order.total_amount || order.subtotal || 0);
                            // Previous balance BEFORE this sale (server returns this correctly, before extra payment was applied)
                            const previousBalance = order.previous_balance ?? orderData.previous_balance ?? data.previous_balance ?? 0;
                            const previousBalancePayment = order.previous_balance_payment ?? orderData.previous_balance_payment ?? data.previous_balance_payment ?? 0;
                            const grandTotal = cartTotal + previousBalance;
                            // Total paid = sale paid_amount + extra payment toward previous balance (ADJ)
                            const paidAmount = parseFloat(order.paid_amount || 0) + parseFloat(previousBalancePayment || 0);
                            const balance = Math.max(0, grandTotal - paidAmount);
                            
                            // Map items to match the format expected by bill popup
                            const mappedItems = items.map(item => {
                                const unitPrice = parseFloat(item.unit_price || item.selling_price || 0);
                                const quantity = parseFloat(item.quantity || 0);
                                const discount = parseFloat(item.discount || 0);
                                
                                return {
                                    name: item.product_name || item.name || 'N/A',
                                    quantity: quantity,
                                    unit_id: item.unit_id || null,
                                    unit_name: item.unit_name || item.unit_short_name || 'Pcs',
                                    selling_price: unitPrice,
                                    discount: discount,
                                    discount_type: discount > 0 ? (discount / (quantity * unitPrice) * 100 > 50 ? 'fixed' : 'percentage') : 'percentage',
                                    product_id: item.product_id,
                                    is_custom: item.product_id === null
                                };
                            });
                            
                            // Store order data for printing
                            lastOrderData = {
                                saleNumber: order.sale_number || order.order_number || data.sale_number || '',
                                customerName: (order.customer && order.customer.name) ? order.customer.name : (customerName || 'Walk-in Customer'),
                                paymentMethod: order.payment_method || paymentMethod,
                                comment: orderComment,
                                items: mappedItems,
                                cartTotal: cartTotal,
                                grandTotal: grandTotal,
                                paidAmount: paidAmount,
                                balance: balance,
                                previousBalance: previousBalance,
                                previousBalancePayment: order.previous_balance_payment || orderData.previous_balance_payment || data.previous_balance_payment || 0,
                                orderDate: order.sale_date || order.order_date || order.created_at ? formatDateTime(order.sale_date || order.order_date || order.created_at) : formatDateTime(new Date().toISOString())
                            };
                            
                            // Show bill popup
                            showBillPopup(lastOrderData.saleNumber);
                            
                            // Reset orderId to 0 for next order
                            orderId = 0;
                            
                            // Clear cart
                            clearCart();
                            
                            // Remove edit_order_id from URL
                            const url = new URL(window.location);
                            url.searchParams.delete('edit_order_id');
                            window.history.replaceState({}, '', url);
                        })
                        .catch(error => {
                            console.error('Error fetching order data:', error);
                            const cartTotal = calculateTotal();
                            const previousBalance = data.previous_balance ?? customerPreviousBalance ?? 0;
                            const grandTotal = (data.grand_total != null) ? parseFloat(data.grand_total) : (cartTotal + previousBalance);
                            const paidAmountForReceipt = (data.paid_amount != null) ? parseFloat(data.paid_amount) : paidAmount;
                            const balance = (data.remaining_balance != null) ? Math.max(0, parseFloat(data.remaining_balance)) : Math.max(0, grandTotal - paidAmount);
                            
                            lastOrderData = {
                                saleNumber: data.sale_number || '',
                                customerName: customerName,
                                paymentMethod: paymentMethod,
                                comment: orderComment,
                                items: JSON.parse(JSON.stringify(cart)),
                                cartTotal: cartTotal,
                                grandTotal: grandTotal,
                                paidAmount: paidAmountForReceipt,
                                balance: balance,
                                previousBalance: previousBalance,
                                previousBalancePayment: data.previous_balance_payment ?? 0,
                                orderDate: formatDateTime(new Date().toISOString())
                            };
                            
                            showBillPopup(lastOrderData.saleNumber);
                            
                            // Reset orderId to 0 for next order
                            orderId = 0;
                            
                            clearCart();
                            
                            // Remove edit_order_id from URL
                            const url = new URL(window.location);
                            url.searchParams.delete('edit_order_id');
                            window.history.replaceState({}, '', url);
                        });
                        return;
                    }
                    
                    // Store order data for printing - use customerPreviousBalance for receipt (what user saw when selecting customer)
                    const cartTotal = calculateTotal();
                    const previousBalance = customerPreviousBalance ?? data.previous_balance ?? 0;
                    const previousBalancePayment = data.previous_balance_payment ?? 0;
                    const grandTotal = cartTotal + previousBalance;
                    const paidAmountForReceipt = (data.paid_amount != null) ? parseFloat(data.paid_amount) : paidAmount;
                    const balance = Math.max(0, grandTotal - paidAmountForReceipt);
                    
                    lastOrderData = {
                        saleNumber: data.sale_number || '',
                        customerName: customerName,
                        paymentMethod: paymentMethod,
                        comment: orderComment,
                        items: JSON.parse(JSON.stringify(cart)),
                        cartTotal: cartTotal,
                        grandTotal: grandTotal,
                        paidAmount: paidAmountForReceipt,
                        balance: balance,
                        previousBalance: previousBalance,
                        previousBalancePayment: previousBalancePayment,
                        orderDate: formatDateTime(new Date().toISOString())
                    };
                    
                    const newBalance = customerPreviousBalance + cartTotal - paidAmountForReceipt;
                    customerPreviousBalance = newBalance > 0 ? newBalance : 0;
                    
                    // Update customer balance in allCustomers array
                    const customerId = document.getElementById('customer-id').value;
                    if (customerId) {
                        const customerIndex = allCustomers.findIndex(c => c.id == customerId);
                        if (customerIndex !== -1) {
                            allCustomers[customerIndex].previous_balance = customerPreviousBalance;
                        }
                    }
                    
                    // Show bill popup
                    showBillPopup(data.sale_number || '');
                    
                    // Reset orderId to 0 for next order
                    orderId = 0;
                    
                    // Clear cart
                    clearCart();
                } else {
                    alert('Error: ' + (data.message || 'Failed to place order'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                let errorMessage = 'An error occurred while placing the order.';
                if (error.message) {
                    errorMessage = error.message;
                }
                alert(errorMessage);
            });
        }

        // Show bill popup after order placement
        function showBillPopup(saleNumber) {
            if (!lastOrderData) return;
            
            const modal = document.getElementById('bill-popup-modal');
            const billContent = document.getElementById('bill-content');
            
            if (!modal || !billContent) return;
            
            const orderData = lastOrderData;
            
            // Generate bill HTML
            let billHTML = `
                <div class="bg-white p-6 rounded-lg max-w-md mx-auto">
                    <!-- Header -->
                    <div class="text-center border-b-2 border-gray-800 pb-4 mb-4">
                        <h2 class="text-2xl font-bold text-gray-800">FARHAN TRADERS</h2>
                        <p class="text-sm text-gray-600 mt-1">Order Receipt</p>
                    </div>
                    
                    <!-- Order Info -->
                    <div class="mb-4 space-y-1 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Sale Number:</span>
                            <span class="font-semibold text-gray-800">${orderData.saleNumber}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Date:</span>
                            <span class="text-gray-800">${orderData.orderDate}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Customer:</span>
                            <span class="text-gray-800">${orderData.customerName}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Payment:</span>
                            <span class="text-gray-800">${orderData.paymentMethod.charAt(0).toUpperCase() + orderData.paymentMethod.slice(1)}</span>
                        </div>
                        ${(orderData.comment && orderData.comment.trim()) ? '<div class="flex justify-between"><span class="text-gray-600">Comment:</span><span class="text-gray-800">' + (orderData.comment || '').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</span></div>' : ''}
                    </div>
                    
                    <!-- Items Table -->
                    <div class="border-t border-b border-gray-300 py-3 my-4">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-300">
                                    <th class="text-left py-2 text-gray-700 font-semibold">Item</th>
                                    <th class="text-center py-2 text-gray-700 font-semibold">Qty</th>
                                    <th class="text-right py-2 text-gray-700 font-semibold">Total</th>
                                </tr>
                            </thead>
                            <tbody>
            `;
            
            orderData.items.forEach(item => {
                // Use unit_price from backend, fallback to selling_price for compatibility
                const unitPrice = item.unit_price ?? item.selling_price ?? 0;
                const selectedUnit = units.find(u => u.id == item.unit_id);
                const unitName = selectedUnit ? selectedUnit.short_name : (item.unit_name || item.unit_short_name || 'Pcs');
                let itemTotal = item.quantity * unitPrice;
                let discount = 0;
                if (item.discount > 0) {
                    if (item.discount_type === 'percentage') {
                        discount = itemTotal * (item.discount / 100);
                    } else {
                        discount = item.discount;
                    }
                }
                itemTotal -= discount;
                
                billHTML += `
                    <tr class="border-b border-gray-200">
                        <td class="py-2 text-gray-800">${item.name}</td>
                        <td class="py-2 text-center text-gray-600">${item.quantity} ${unitName}</td>
                        <td class="py-2 text-right font-semibold text-gray-800">PKR ${itemTotal.toFixed(2)}</td>
                    </tr>
                `;
            });
            
            billHTML += `
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Total -->
                    <div class="text-right mb-4 space-y-1">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm text-gray-700">Subtotal:</span>
                            <span class="text-sm font-semibold text-gray-900">PKR ${parseFloat(orderData.cartTotal || 0).toFixed(2)}</span>
                        </div>
                        ${orderData.previousBalance !== undefined && orderData.previousBalance > 0 ? 
                        '<div class="flex justify-between items-center mb-1"><span class="text-sm text-gray-700">Previous Balance:</span><span class="text-sm font-semibold text-gray-900">PKR '+parseFloat(orderData.previousBalance).toFixed(2) +'</span></div>'
                        : ''}
                        <div class="border-t border-gray-300 my-2"></div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-base font-bold text-gray-900">Total Payable:</span>
                            <span class="text-base font-bold text-gray-900">PKR ${parseFloat(orderData.grandTotal || 0).toFixed(2)}</span>
                        </div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm text-gray-700">Amount Paid:</span>
                            <span class="text-sm text-green-600">PKR ${parseFloat(orderData.paidAmount || 0).toFixed(2)}</span>
                        </div>
                        ${orderData.balance !== undefined ? 
                        '<div class="border-t border-gray-300 my-2"></div><div class="flex justify-between items-center"><span class="text-base font-bold text-gray-900">Remaining Balance:</span><span class="text-base font-bold ' + (orderData.balance > 0 ? 'text-red-600' : 'text-green-600') + '">PKR ' + Math.max(0, parseFloat(orderData.balance)).toFixed(2) + '</span></div>'
                        : ''}
                    </div>
                    
                    <!-- Footer -->
                    <div class="text-center border-t border-gray-300 pt-4">
                        <p class="text-sm text-gray-600">Thank you for your business!</p>
                        <p class="text-xs text-gray-500 mt-1">This is a computer-generated receipt</p>
                    </div>
                </div>
            `;
            
            billContent.innerHTML = billHTML;
            modal.classList.remove('hidden');
        }

        // Close bill popup
        function closeBillPopup() {
            const modal = document.getElementById('bill-popup-modal');
            if (modal) {
                modal.classList.add('hidden');
            }
            
            // Remove edit_order_id from URL if it exists
            const url = new URL(window.location);
            if (url.searchParams.has('edit_order_id')) {
                url.searchParams.delete('edit_order_id');
                window.history.replaceState({}, '', url);
            }
            
            // Refresh the POS screen immediately after closing bill popup
            window.location.reload();
        }

        // Print order receipt
        function printOrderReceipt() {
            if (!lastOrderData) {
                alert('No order data available to print');
                return;
            }
            
            const printWindow = window.open('', '_blank');
            const orderData = lastOrderData;

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
                            <span><strong>${orderData.saleNumber}</strong></span>
                        </div>
                        <div>
                            <span>Date:</span>
                            <span>${orderData.orderDate}</span>
                        </div>
                        <div>
                            <span>Customer:</span>
                            <span>${orderData.customerName}</span>
                        </div>
                        <div>
                            <span>Payment:</span>
                            <span>${orderData.paymentMethod.charAt(0).toUpperCase() + orderData.paymentMethod.slice(1)}</span>
                        </div>
                        ${(orderData.comment && orderData.comment.trim()) ? '<div><span>Comment:</span><span>' + (orderData.comment || '').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</span></div>' : ''}
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

            orderData.items.forEach(item => {
                // Use unit_price from backend, fallback to selling_price for compatibility
                const unitPrice = item.unit_price ?? item.selling_price ?? 0;
                let itemTotal = item.quantity * unitPrice;
                let discount = 0;
                if (item.discount > 0) {
                    if (item.discount_type === 'percentage') {
                        discount = itemTotal * (item.discount / 100);
                    } else {
                        discount = item.discount;
                    }
                }
                itemTotal -= discount;

                const selectedUnit = units.find(u => u.id == item.unit_id);
                const unitName = selectedUnit ? selectedUnit.short_name : (item.unit_name || item.unit_short_name || 'Pcs');

                printContent += `
                    <tr>
                        <td>${item.name}</td>
                        <td style="text-align: right;">${item.quantity} ${unitName}</td>
                        <td style="text-align: right;">PKR ${itemTotal.toFixed(2)}</td>
                    </tr>
                `;
            });

            const regularPaidAmount = parseFloat(orderData.regular_paid_amount || orderData.paidAmount || 0);
            const adjPaidAmount = parseFloat(orderData.adj_paid_amount || 0);
            const adjBillNumber = orderData.adj_bill_number || null;
            const totalPaidAmount = parseFloat(orderData.paidAmount || 0);
            
            printContent += `
                        </tbody>
                    </table>
                    <div class="total-section">
                        <p style="font-size: 10px; margin-bottom: 3px; display: flex; justify-content: space-between;">
                            <span>Subtotal:</span>
                            <span>PKR ${parseFloat(orderData.cartTotal || 0).toFixed(2)}</span>
                        </p>
                        ${orderData.previousBalance !== undefined && orderData.previousBalance > 0 ? 
                        '<p style="font-size: 10px; margin-bottom: 3px; display: flex; justify-content: space-between;"><span>Previous Balance:</span><span>PKR ' + parseFloat(orderData.previousBalance || 0).toFixed(2) + '</span></p>'
                        : ''}
                        <p style="border-top: 1px solid #ddd; margin: 5px 0; padding-top: 5px;"></p>
                        <p style="font-size: 12px; margin-bottom: 3px; display: flex; justify-content: space-between; font-weight: bold;">
                            <span>Total Payable:</span>
                            <span>PKR ${parseFloat(orderData.grandTotal || 0).toFixed(2)}</span>
                        </p>
                        ${regularPaidAmount > 0 ? '<p style="font-size: 10px; margin-bottom: 3px; display: flex; justify-content: space-between;"><span>Amount Paid:</span><span>PKR ' + regularPaidAmount.toFixed(2) + '</span></p>' : ''}
                        ${adjPaidAmount > 0 && adjBillNumber ? '<p style="font-size: 10px; margin-bottom: 3px; display: flex; justify-content: space-between;"><span>Previous Balance Paid (' + adjBillNumber + '):</span><span>PKR ' + adjPaidAmount.toFixed(2) + '</span></p>' : ''}
                        ${totalPaidAmount > 0 && (regularPaidAmount > 0 || adjPaidAmount > 0) ? '<p style="font-size: 10px; margin-bottom: 3px; display: flex; justify-content: space-between; font-weight: bold; border-top: 1px solid #ddd; padding-top: 3px; margin-top: 3px;"><span>Total Paid:</span><span>PKR ' + totalPaidAmount.toFixed(2) + '</span></p>' : ''}
                        ${orderData.balance !== undefined ? 
                        '<p style="border-top: 1px solid #ddd; margin: 5px 0; padding-top: 5px;"></p><p style="font-size: 12px; margin-top: 5px; display: flex; justify-content: space-between; font-weight: bold; color: #000;"><span>Remaining Balance:</span><span>PKR ' + Math.max(0, parseFloat(orderData.balance || 0)).toFixed(2) + '</span></p>'
                        : ''}
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
            printWindow.print();
            
            // Remove edit_order_id from URL after printing
            const url = new URL(window.location);
            if (url.searchParams.has('edit_order_id')) {
                url.searchParams.delete('edit_order_id');
                window.history.replaceState({}, '', url);
            }
        }

        function updateCustomerTypeChipStyles() {
            document.querySelectorAll('.customer-type-filter-chip').forEach(btn => {
                const t = btn.getAttribute('data-customer-type-filter');
                const active = t === customerTypeFilter;
                btn.classList.toggle('bg-orange-500', active);
                btn.classList.toggle('text-white', active);
                btn.classList.toggle('bg-gray-100', !active);
                btn.classList.toggle('text-gray-700', !active);
                btn.classList.toggle('hover:bg-gray-200', !active);
            });
        }

        function appendCustomerTypeChipIfNeeded(type) {
            const t = String(type || '').trim();
            if (!t) return;
            const row = document.getElementById('customer-type-filters');
            if (!row) return;
            const chips = row.querySelectorAll('.customer-type-filter-chip');
            for (let i = 0; i < chips.length; i++) {
                if (chips[i].getAttribute('data-customer-type-filter') === t) {
                    return;
                }
            }
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'customer-type-filter-chip pointer-events-auto flex-shrink-0 px-3 py-1.5 rounded-md text-sm font-medium whitespace-nowrap bg-gray-100 text-gray-700 hover:bg-gray-200';
            btn.setAttribute('data-customer-type-filter', t);
            btn.textContent = t;
            btn.addEventListener('mousedown', (e) => e.preventDefault());
            btn.addEventListener('click', () => setCustomerTypeFilter(t));
            row.appendChild(btn);
        }

        /** @param {string} type @param {Object} opts — optional focusSearch, openDropdown (default true) */
        function setCustomerTypeFilter(type, opts = {}) {
            const focusSearch = opts.focusSearch !== false;
            const openDropdown = opts.openDropdown !== false;
            const normalized =
                type === 'all' || type === null || type === undefined || String(type).trim() === ''
                    ? 'all'
                    : String(type).trim();
            if (normalized !== 'all') {
                appendCustomerTypeChipIfNeeded(normalized);
            }
            customerTypeFilter = normalized;
            updateCustomerTypeChipStyles();
            filterCustomers();
            const inp = document.getElementById('customer-search');
            if (inp && focusSearch) {
                inp.focus();
            }
            const dropdown = document.getElementById('customer-dropdown');
            if (dropdown) {
                if (openDropdown) {
                    dropdown.classList.remove('hidden');
                }
            }
        }

        function customerMatchesTypeFilter(customer) {
            if (customerTypeFilter === 'all') return true;
            const ct = String(customer.customer_type || '').trim().toLowerCase();
            const selectedType = String(customerTypeFilter || '').trim().toLowerCase();
            return ct === selectedType;
        }

        function updateSelectedCustomerTypeLabel(customer) {
            const el = document.getElementById('selected-customer-type');
            if (!el) return;
            const t = customer && customer.customer_type ? String(customer.customer_type).trim() : '';
            if (t) {
                el.textContent = t;
                el.classList.remove('hidden');
            } else {
                el.textContent = '';
                el.classList.add('hidden');
            }
        }

        // Customer dropdown functions
        function filterCustomers() {
            const searchInput = document.getElementById('customer-search');
            const customerList = document.getElementById('customer-list');
            const searchTerm = searchInput.value.toLowerCase().trim();
            
            customerList.innerHTML = '';
            
            const byType = allCustomers.filter(customerMatchesTypeFilter);

            const matchesSearch = (customer) =>
                !searchTerm ||
                String(customer.name || '').toLowerCase().includes(searchTerm) ||
                String(customer.customer_id || '').toLowerCase().includes(searchTerm) ||
                String(customer.phone || '').toLowerCase().includes(searchTerm) ||
                String(customer.customer_type || '').toLowerCase().includes(searchTerm);

            const filtered = byType.filter(matchesSearch);

            if (filtered.length > 0) {
                filtered.forEach(customer => {
                    const item = document.createElement('div');
                    item.className = 'px-4 py-2 hover:bg-gray-100 cursor-pointer border-b border-gray-50 last:border-0';
                    const previousBalance = parseFloat(customer.previous_balance || 0);
                    const balanceText = previousBalance > 0 ? `<div class="text-xs text-red-600 font-semibold mt-1">Previous Balance: PKR ${previousBalance.toFixed(2)}</div>` : '';
                    const typeLabel = (customer.customer_type && String(customer.customer_type).trim())
                        ? `<div class="text-xs font-medium text-orange-600 mt-0.5">${customer.customer_type}</div>`
                        : '';
                    item.innerHTML = `<div class="font-medium text-gray-900">${customer.name}</div>${typeLabel}<div class="text-xs text-gray-500 mt-0.5">${customer.customer_id}${customer.phone ? ' | ' + customer.phone : ''}</div>${balanceText}`;
                    item.onclick = () => selectCustomer(customer);
                    customerList.appendChild(item);
                });
            } else {
                const item = document.createElement('div');
                item.className = 'px-4 py-2 text-gray-500 italic text-sm';
                item.textContent = searchTerm ? 'No customer found' : 'No customers in this type';
                customerList.appendChild(item);
            }
        }
        
        function showCustomerDropdown() {
            const dropdown = document.getElementById('customer-dropdown');
            filterCustomers();
            dropdown.classList.remove('hidden');
        }
        
        function hideCustomerDropdown() {
            const dropdown = document.getElementById('customer-dropdown');
            dropdown.classList.add('hidden');
        }
        
        function selectCustomer(customer) {
            const customerIdInput = document.getElementById('customer-id');
            const customerNameInput = document.getElementById('customer-name');
            const customerSearchInput = document.getElementById('customer-search');
            const selectedCustomerName = document.getElementById('selected-customer-name');
            const selectedCustomerDiv = document.getElementById('selected-customer');
            const addLastOrderBtn = document.getElementById('add-last-order-btn');
            
            if (customerIdInput) customerIdInput.value = customer.id;
            if (customerNameInput) customerNameInput.value = customer.name;
            if (customerSearchInput) customerSearchInput.value = customer.name;
            if (selectedCustomerName) selectedCustomerName.textContent = customer.name;
            updateSelectedCustomerTypeLabel(customer);
            const ct = (customer.customer_type || '').trim();
            setCustomerTypeFilter(ct || 'all', { focusSearch: false, openDropdown: false });
            if (selectedCustomerDiv) selectedCustomerDiv.classList.remove('hidden');
            
            // Show/hide "Add Last Order" button based on whether customer has an ID
            if (addLastOrderBtn) {
                if (customer.id) {
                    addLastOrderBtn.classList.remove('hidden');
                } else {
                    addLastOrderBtn.classList.add('hidden');
                }
            }
            
            // Store previous balance
            customerPreviousBalance = parseFloat(customer.previous_balance || 0);
            Object.keys(lastPriceCache).forEach((key) => delete lastPriceCache[key]);
            closeAllLastPriceHints();
            hideCustomerDropdown();
            renderCart(); // Refresh Grand Total to include previous balance
        }
        
        // Add last order items to cart
        function addLastOrderToCart() {
            const customerIdInput = document.getElementById('customer-id');
            const customerId = customerIdInput ? customerIdInput.value : null;
            
            if (!customerId) {
                alert('Please select a customer first.');
                return;
            }
            
            // Show loading state
            const addLastOrderBtn = document.getElementById('add-last-order-btn');
            const originalText = addLastOrderBtn ? addLastOrderBtn.textContent : 'Add Last Order';
            if (addLastOrderBtn) {
                addLastOrderBtn.disabled = true;
                addLastOrderBtn.textContent = 'Loading...';
            }
            
            // Fetch last order items
            const url = '{{ route("sales.pos.last-order-items", ":customerId") }}'.replace(':customerId', customerId);
            fetch(url, {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(async response => {
                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || 'Failed to fetch last order items');
                }
                return data;
            })
            .then(async (data) => {
                if (!data.items || data.items.length === 0) {
                    alert('No items found in the last order.');
                    return;
                }
                
                // Add each item to cart
                let addedCount = 0;
                let skippedCount = 0;
                const skippedItems = [];
                
                for (const item of data.items) {
                    if (item.is_custom) {
                        // Add custom product to cart
                        cart.push({
                            product_id: null,
                            name: item.name,
                            purchase_price: 0,
                            selling_price: parseFloat(item.selling_price) || 0,
                            retail_price: parseFloat(item.retail_price) || 0,
                            wholesale_price: parseFloat(item.wholesale_price) || 0,
                            selling_type: item.selling_type || 'retail',
                            price_type: item.price_type || 'retail',
                            quantity: parseFloat(item.quantity) || 1,
                            unit_id: item.unit_id || null,
                            unit_name: item.unit_name || 'Pcs',
                            stock_quantity: 999999,
                            discount_type: item.discount_type || 'percentage',
                            discount: parseFloat(item.discount) || 0,
                            is_custom: true
                        });
                        addedCount++;
                    } else {
                        // Regular product - check if product exists in products list
                        const product = products.find(p => Number(p.id) === Number(item.product_id));
                        
                        if (!product) {
                            skippedCount++;
                            skippedItems.push(item.name);
                            return;
                        }
                        
                        // Check stock availability
                        if (product.stock_quantity <= 0) {
                            skippedCount++;
                            skippedItems.push(item.name + ' (out of stock)');
                            return;
                        }
                        
                        // Check if item already exists in cart
                        const existingItem = cart.find(cartItem => 
                            cartItem.product_id === product.id && 
                            cartItem.unit_id === item.unit_id
                        );
                        
                        if (existingItem) {
                            // Update quantity if adding won't exceed stock
                            const newQuantity = parseFloat((existingItem.quantity + parseFloat(item.quantity)).toFixed(2));
                            const validation = await isQuantityWithinStock(existingItem, newQuantity);
                            if (validation.allowed) {
                                existingItem.quantity = newQuantity;
                                addedCount++;
                            } else {
                                skippedCount++;
                                skippedItems.push(item.name + ' (insufficient stock)');
                            }
                        } else {
                            // Add new item to cart
                            cart.push({
                                product_id: Number(item.product_id),
                                name: item.name,
                                purchase_price: parseFloat(item.purchase_price) || 0,
                                selling_price: parseFloat(item.selling_price) || 0,
                                retail_price: parseFloat(item.retail_price) || 0,
                                wholesale_price: parseFloat(item.wholesale_price) || 0,
                                selling_type: item.selling_type || product.selling_type || 'retail',
                                price_type: item.price_type || (product.selling_type === 'both' ? 'retail' : product.selling_type) || 'retail',
                                quantity: parseFloat(item.quantity) || 1,
                                unit_id: item.unit_id || product.unit_id,
                                unit_name: item.unit_name || product.unit_name || 'Pcs',
                                base_unit_id: item.base_unit_id || product.base_unit_id || product.unit_id,
                                selling_units: product.selling_units || [],
                                stock_quantity: parseFloat(product.stock_quantity) || 0,
                                discount_type: item.discount_type || 'percentage',
                                discount: parseFloat(item.discount) || 0,
                            });
                            addedCount++;
                        }
                    }
                }
                
                await refreshAllCartPurchasePrices();
                
                // Show result message
                let message = `Added ${addedCount} item(s) from last order (${data.sale_number})`;
                if (skippedCount > 0) {
                    message += `\nSkipped ${skippedCount} item(s): ${skippedItems.join(', ')}`;
                }
                alert(message);
            })
            .catch(error => {
                console.error('Error fetching last order:', error);
                alert('Error: ' + error.message);
            })
            .finally(() => {
                // Restore button state
                if (addLastOrderBtn) {
                    addLastOrderBtn.disabled = false;
                    addLastOrderBtn.textContent = originalText;
                }
            });
        }
        
        function clearCustomerSelection() {
            const customerIdInput = document.getElementById('customer-id');
            const customerNameInput = document.getElementById('customer-name');
            const customerSearchInput = document.getElementById('customer-search');
            const selectedCustomerDiv = document.getElementById('selected-customer');
            const addLastOrderBtn = document.getElementById('add-last-order-btn');
            
            if (customerIdInput) customerIdInput.value = '';
            if (customerNameInput) customerNameInput.value = '';
            if (customerSearchInput) customerSearchInput.value = '';
            if (selectedCustomerDiv) selectedCustomerDiv.classList.add('hidden');
            if (addLastOrderBtn) addLastOrderBtn.classList.add('hidden');
            updateSelectedCustomerTypeLabel(null);
            setCustomerTypeFilter('all', { focusSearch: false, openDropdown: false });
            customerPreviousBalance = 0;
            Object.keys(lastPriceCache).forEach((key) => delete lastPriceCache[key]);
            closeAllLastPriceHints();
            renderCart();
        }
        
        function openNewCustomerModal() {
            hideCustomerDropdown();
            document.getElementById('new-customer-modal').classList.remove('hidden');
            document.getElementById('new-customer-name').value = document.getElementById('customer-search').value;
            document.getElementById('new-customer-name').focus();
        }
        
        function closeNewCustomerModal() {
            document.getElementById('new-customer-modal').classList.add('hidden');
            document.getElementById('new-customer-form').reset();
        }
        
        function createNewCustomer() {
            const formData = new FormData(document.getElementById('new-customer-form'));
            
            fetch('{{ route("customers.store") }}', {
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
                    if (data.errors) {
                        let errors = [];
                        for (let field in data.errors) {
                            errors.push(data.errors[field].join(', '));
                        }
                        throw new Error(errors.join('\n'));
                    }
                    throw new Error(data.message || 'Failed to create customer');
                }
                return data;
            })
            .then(data => {
                // Add new customer to the list
                const newCustomer = {
                    id: data.customer.id,
                    name: data.customer.name,
                    customer_id: data.customer.customer_id,
                    customer_type: data.customer.customer_type || '',
                    phone: data.customer.phone || '',
                    email: data.customer.email || '',
                    previous_balance: 0,
                    address: data.customer.address || ''
                };
                allCustomers.push(newCustomer);
                
                // Select the newly created customer
                selectCustomer(newCustomer);
                closeNewCustomerModal();
            })
            .catch(error => {
                alert('Error creating customer: ' + error.message);
            });
        }

        // Add Product Modal Functions

        function openAddProductModal() {
            const modal = document.getElementById('add-product-modal');
            if (modal) {
                modal.classList.remove('hidden');
                
                // Add escape key listener
                document.addEventListener('keydown', handleModalEscape);
            }
        }

        function handleModalEscape(event) {
            if (event.key === 'Escape') {
                const modal = document.getElementById('add-product-modal');
                if (modal && !modal.classList.contains('hidden')) {
                    closeAddProductModal();
                }
            }
        }

        function closeAddProductModal() {
            const modal = document.getElementById('add-product-modal');
            if (modal) {
                modal.classList.add('hidden');
                document.getElementById('add-product-form').reset();
                
                // Remove escape key listener
                document.removeEventListener('keydown', handleModalEscape);
            }
        }

        function toggleModalPriceFields() {
            const sellingType = document.getElementById('modal-product-selling-type').value;
            const retailContainer = document.getElementById('modal-retail-price-container');
            const wholesaleContainer = document.getElementById('modal-wholesale-price-container');
            const retailInput = document.getElementById('modal-product-retail-price');
            const wholesaleInput = document.getElementById('modal-product-wholesale-price');

            retailContainer.classList.add('hidden');
            wholesaleContainer.classList.add('hidden');
            retailInput.required = false;
            wholesaleInput.required = false;

            if (sellingType === 'retail') {
                retailContainer.classList.remove('hidden');
                retailInput.required = true;
            } else if (sellingType === 'wholesale') {
                wholesaleContainer.classList.remove('hidden');
                wholesaleInput.required = true;
            } else if (sellingType === 'both') {
                retailContainer.classList.remove('hidden');
                wholesaleContainer.classList.remove('hidden');
                retailInput.required = true;
                wholesaleInput.required = true;
            }
        }


        function submitProductForm() {
            const form = document.getElementById('add-product-form');
            const formData = new FormData(form);

            // SKU will be auto-generated by the backend if not provided
            // No need to set it here

            // Show loading state
            const submitBtn = document.getElementById('modal-submit-btn');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Adding...';

            fetch('{{ route("products.store") }}', {
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
                    if (data.errors) {
                        let errors = [];
                        for (let field in data.errors) {
                            errors.push(data.errors[field].join(', '));
                        }
                        throw new Error(errors.join('\n'));
                    }
                    throw new Error(data.message || 'Failed to create product');
                }
                return data;
            })
            .then(data => {
                alert('Product added successfully!');
                
                // Add the new product to the products array
                const newProduct = {
                    id: data.product.id,
                    name: data.product.name,
                    sku: data.product.sku,
                    purchase_price: parseFloat(data.product.purchase_price),
                    selling_price: parseFloat(data.product.selling_price || 0),
                    retail_price: parseFloat(data.product.retail_price || data.product.selling_price || 0),
                    wholesale_price: parseFloat(data.product.wholesale_price || data.product.selling_price || 0),
                    selling_type: data.product.selling_type || 'retail',
                    stock_quantity: parseFloat(data.product.stock_quantity),
                    unit_id: data.product.unit_id,
                    unit_name: data.product.unit ? data.product.unit.short_name : '',
                    category_id: data.product.category_id,
                    image: data.product.image ? '{{ asset("storage") }}/' + data.product.image : null,
                };
                products.push(newProduct);

                // Add product to the grid dynamically (without page reload)
                addProductToGrid(newProduct);

                // Reset form and close modal
                form.reset();
                closeAddProductModal();
            })
            .catch(error => {
                alert('Error creating product: ' + error.message);
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            });
        }

        // Add new product to the products grid dynamically
        function addProductToGrid(product) {
            const productsGrid = document.getElementById('products-grid');
            if (!productsGrid) return;

            // Calculate display price
            let displayPrice = product.selling_price;
            if (product.selling_type === 'retail' && product.retail_price) {
                displayPrice = product.retail_price;
            } else if (product.selling_type === 'wholesale' && product.wholesale_price) {
                displayPrice = product.wholesale_price;
            } else if (product.selling_type === 'both' && product.retail_price) {
                displayPrice = product.retail_price;
            }

            // Create product card element
            const productCard = document.createElement('div');
            productCard.onclick = () => addToCart(product.id);
            productCard.setAttribute('data-product-name', product.name);
            productCard.setAttribute('data-product-sku', product.sku || '');
            productCard.setAttribute('data-category-id', product.category_id || '');
            productCard.className = 'bg-white border border-gray-200 rounded-lg p-2 cursor-pointer hover:shadow-lg transition-shadow';

            // Build inner HTML
            let imageHtml = '';
            if (product.image) {
                imageHtml = `<img src="${product.image}" alt="${product.name}" class="w-full h-full object-cover">`;
            } else {
                imageHtml = `<svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>`;
            }

            productCard.innerHTML = `
                <div class="aspect-square bg-gray-100 rounded-lg mb-2 flex items-center justify-center overflow-hidden">
                    ${imageHtml}
                </div>
                <h4 class="font-semibold text-xs mb-1 line-clamp-2">${product.name}</h4>
                <p class="text-xs text-gray-500 mb-1">Remaining: ${parseFloat(product.stock_quantity || 0).toFixed(2)} ${product.unit_name || 'Pcs'}</p>
                <p class="text-sm font-bold text-orange-600">PKR ${parseFloat(displayPrice).toFixed(2)}</p>
                ${product.selling_type ? `<p class="text-xs text-gray-400 mt-1">${product.selling_type.charAt(0).toUpperCase() + product.selling_type.slice(1)}</p>` : ''}
            `;

            // Add to grid
            productsGrid.appendChild(productCard);

            // Apply current filters (category and search)
            const searchInput = document.getElementById('product-search');
            const searchTerm = searchInput ? searchInput.value : '';
            searchProducts(searchTerm);
        }

        // Auto-generate SKU function (same as in create.blade.php)
        function generateSku(productName = '') {
            let prefix = 'PRD';
            if (productName && productName.trim()) {
                const words = productName.trim().toUpperCase().split(/\s+/);
                let initials = '';
                words.forEach(word => {
                    if (word.length > 0) {
                        initials += word.charAt(0);
                    }
                });
                if (initials.length >= 2) {
                    prefix = initials.substring(0, 3);
                }
            }

            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const datePart = year + month + day;
            const randomPart = String(Math.floor(Math.random() * 10000)).padStart(4, '0');

            return prefix + '-' + datePart + '-' + randomPart;
        }

        // Clear cart
        function clearCart() {
            cart = [];
            // Reset orderId to 0 for new orders (don't increment here)
            // orderId will be reset to 0 after order completion
            orderId = 0;
            const orderIdEl = document.getElementById('order-id');
            if (orderIdEl) {
                orderIdEl.textContent = orderId || 1;
            }
            clearCustomerSelection();
            renderCart();
        }

        // Reset cart
        function resetCart() {
            if (confirm('Are you sure you want to reset the cart?')) {
                clearCart();
            }
        }

        // Hold order
        function holdOrder() {
            if (cart.length === 0) {
                alert('No items to hold');
                return;
            }

            const formData = new FormData();
            const customerId = document.getElementById('customer-id').value;
            const customerNameInput = document.getElementById('customer-name').value;
            // Use "Walk-in Customer" if customer name is empty
            const customerName = customerNameInput.trim() || 'Walk-in Customer';
            
            if (customerId) {
                formData.append('customer_id', customerId);
            }
            formData.append('customer_name', customerName);
            
            cart.forEach((item, index) => {
                const isCustom = item.product_id === null || item.is_custom === true;
                if (isCustom) {
                    // Custom product - send product_name and is_custom flag instead of product_id
                    formData.append(`items[${index}][product_name]`, item.name);
                    formData.append(`items[${index}][is_custom]`, '1');
                } else {
                    // Regular product - send product_id
                    formData.append(`items[${index}][product_id]`, item.product_id);
                }
                formData.append(`items[${index}][quantity]`, item.quantity);
                formData.append(`items[${index}][unit_id]`, item.unit_id);
                formData.append(`items[${index}][selling_price]`, item.selling_price);
                formData.append(`items[${index}][discount_type]`, item.discount_type);
                formData.append(`items[${index}][discount]`, item.discount);
            });

            fetch('{{ route("sales.pos.hold") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(async response => {
                const contentType = response.headers.get('content-type');
                let data;
                
                if (contentType && contentType.includes('application/json')) {
                    data = await response.json();
                } else {
                    const text = await response.text();
                    throw new Error(text || 'Failed to hold order');
                }
                
                if (!response.ok) {
                    if (data.errors) {
                        let errorMessages = [];
                        for (let field in data.errors) {
                            errorMessages.push(data.errors[field].join(', '));
                        }
                        throw new Error(errorMessages.join('\n'));
                    }
                    throw new Error(data.message || 'Failed to hold order');
                }
                
                return data;
            })
            .then(data => {
                if (data.success) {
                    alert('Order held successfully. Sale Number: ' + (data.sale_number || ''));
                    // Clear cart after holding
                    clearCart();
                    
                    // Open POS in new tab with hold order loaded
                    if (data.sale_id) {
                        const posUrl = '{{ route("sales.pos.index") }}?load_hold=' + data.sale_id;
                        window.open(posUrl, '_blank');
                    }
                } else {
                    alert('Error: ' + (data.message || 'Failed to hold order'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                let errorMessage = 'An error occurred while holding the order.';
                if (error.message) {
                    errorMessage = error.message;
                }
                alert(errorMessage);
            });
        }

        // Void order
        function voidOrder() {
            if (cart.length === 0) {
                alert('No items to void');
                return;
            }
            if (confirm('Are you sure you want to void this order?')) {
                clearCart();
            }
        }

        // Calendar
        function openCalendar() {
            const calculator = document.getElementById('calculator-modal');
            if (calculator) {
                calculator.classList.remove('hidden');
                calcDisplay = '';
                calcResult = null;
                updateCalcDisplay();
            }
        }

        function closeCalculator() {
            const calculator = document.getElementById('calculator-modal');
            if (calculator) {
                calculator.classList.add('hidden');
                calcDisplay = '';
                calcResult = null;
            }
        }

        function updateCalcDisplay() {
            const display = document.getElementById('calc-display');
            if (display) {
                display.value = calcDisplay || '0';
            }
        }

        function calculatorInput(value) {
            const display = document.getElementById('calc-display');
            if (!display) return;

            // Handle AC (All Clear) or C (Clear)
            if (value === 'C' || value === 'AC') {
                calcDisplay = '';
                calcResult = null;
                lastOperation = null;
                display.value = '0';
                return;
            }

            // Handle equals
            if (value === '=') {
                if (calcDisplay) {
                    try {
                        // Replace × with * and ÷ with / for calculation
                        let expression = calcDisplay.replace(/×/g, '*').replace(/÷/g, '/');
                        // Remove any invalid characters (keep numbers, operators, parentheses, decimal point)
                        expression = expression.replace(/[^0-9+\-*/.() ]/g, '');
                        
                        if (expression && /^[0-9+\-*/.() ]+$/.test(expression)) {
                            // Use Function constructor for safer evaluation
                            calcResult = Function('"use strict"; return (' + expression + ')')();
                            if (isNaN(calcResult) || !isFinite(calcResult)) {
                                calcDisplay = 'Error';
                                display.value = 'Error';
                            } else {
                                // Format result
                                calcDisplay = parseFloat(calcResult.toFixed(10)).toString();
                                display.value = calcDisplay;
                            }
                        } else {
                            calcDisplay = 'Error';
                            display.value = 'Error';
                        }
                    } catch (e) {
                        calcDisplay = 'Error';
                        display.value = 'Error';
                    }
                }
                return;
            }

            // Handle percentage
            if (value === '%') {
                if (calcDisplay && calcDisplay !== 'Error') {
                    try {
                        // Calculate percentage: divide by 100
                        let expression = calcDisplay.replace(/×/g, '*').replace(/÷/g, '/');
                        expression = expression.replace(/[^0-9+\-*/.() ]/g, '');
                        if (expression && /^[0-9+\-*/.() ]+$/.test(expression)) {
                            const result = Function('"use strict"; return (' + expression + ')')();
                            if (!isNaN(result) && isFinite(result)) {
                                calcDisplay = (result / 100).toString();
                                display.value = calcDisplay;
                            }
                        }
                    } catch (e) {
                        calcDisplay = 'Error';
                        display.value = 'Error';
                    }
                }
                return;
            }

            // Handle operations
            const operations = ['+', '-', '*', '/', '×', '÷'];
            const lastChar = calcDisplay.slice(-1);
            
            if (operations.includes(value)) {
                // Replace × with * and ÷ with / for internal storage
                const opValue = value === '×' ? '*' : (value === '÷' ? '/' : value);
                
                if (calcDisplay === '' || calcDisplay === 'Error') {
                    if (value === '-') {
                        calcDisplay = '-';
                    } else {
                        return; // Don't allow operations at start
                    }
                } else if (operations.some(op => {
                    const opChar = op === '×' ? '*' : (op === '÷' ? '/' : op);
                    return lastChar === op || lastChar === opChar;
                })) {
                    // Replace last operation
                    calcDisplay = calcDisplay.slice(0, -1) + opValue;
                } else {
                    calcDisplay += opValue;
                }
            } else if (value === '.' || value === '(' || value === ')') {
                // Handle decimal point and parentheses
                if (calcDisplay === 'Error' || (calcResult !== null && value !== '(' && value !== ')')) {
                    if (value === '.') {
                        calcDisplay = '0.';
                    } else {
                        calcDisplay = value;
                    }
                    calcResult = null;
                } else if (value === '.') {
                    // Handle decimal point - check current number doesn't already have one
                    const parts = calcDisplay.split(/[+\-*/()]/);
                    const currentPart = parts[parts.length - 1];
                    if (!currentPart.includes('.')) {
                        calcDisplay += '.';
                    }
                } else {
                    // Handle parentheses
                    calcDisplay += value;
                }
            } else {
                // Handle numbers
                if (calcDisplay === 'Error' || (calcResult !== null && lastOperation === null)) {
                    calcDisplay = value;
                    calcResult = null;
                } else {
                    calcDisplay += value;
                }
            }

            // Update display (show × instead of * and ÷ instead of /)
            const displayValue = calcDisplay.replace(/\*/g, '×').replace(/\//g, '÷');
            display.value = displayValue || '0';
            lastOperation = operations.includes(value) ? value : null;
        }

        function calculatorBackspace() {
            if (calcDisplay && calcDisplay !== 'Error') {
                calcDisplay = calcDisplay.slice(0, -1);
                updateCalcDisplay();
            }
        }

        function insertCalculatorValue() {
            const display = document.getElementById('calc-display');
            if (display && display.value && display.value !== 'Error' && display.value !== '0') {
                let value = display.value.replace(/×/g, '*');
                // Try to evaluate if it's an expression
                try {
                    if (/^[0-9+\-*/.() ]+$/.test(value)) {
                        const result = Function('"use strict"; return (' + value + ')')();
                        if (!isNaN(result) && isFinite(result)) {
                            value = parseFloat(result.toFixed(10)).toString();
                        }
                    }
                } catch (e) {
                    // If evaluation fails, use the display value as is
                }
                
                const customerSearchField = document.getElementById('customer-search');
                if (customerSearchField) {
                    customerSearchField.value = value;
                }
                closeCalculator();
            }
        }

        // Calendar
        function openCalendar() {
            const today = new Date();
            const dateStr = today.toLocaleDateString('en-US', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            alert('Today\'s Date: ' + dateStr);
        }

        // Fullscreen (entire page)
        function toggleFullscreen() {
            const icon = document.getElementById('fullscreen-icon');
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().then(() => {
                    if (icon) {
                        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>';
                    }
                }).catch(err => {
                    console.log('Error attempting to enable fullscreen:', err);
                });
            } else {
                document.exitFullscreen().then(() => {
                    if (icon) {
                        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path>';
                    }
                });
            }
        }

        // Print
        function printOrder() {
            if (cart.length === 0) {
                alert('No items in cart to print');
                return;
            }
            
            const printWindow = window.open('', '_blank');
            const orderDate = formatDateTime(new Date().toISOString());
            const customerName = document.getElementById('customer-name').value || 'Walk-in Customer';
            const paymentMethodText = paymentMethod.charAt(0).toUpperCase() + paymentMethod.slice(1);
            const grandTotal = calculateGrandTotal();

            let printContent = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Order Receipt</title>
                    <style>
                        @media print {
                            * { color: #000 !important; }
                        }
                        * { color: #000 !important; }
                        body { font-family: Arial, sans-serif; padding: 20px; }
                        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
                        .header h2 { margin: 0; font-size: 18px; font-weight: bold; }
                        .header p { margin: 5px 0 0 0; font-size: 11px; }
                        .order-info { margin-bottom: 20px; }
                        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f2f2f2; }
                        .total { text-align: right; font-weight: bold; font-size: 18px; margin-top: 20px; }
                        .business-info {
                            margin-top: 10px;
                            padding-top: 10px;
                            font-size: 10px;
                        }
                        .business-service {
                            font-weight: bold;
                            margin-bottom: 6px;
                            font-size: 10px;
                        }
                        .business-service i {
                            font-style: italic;
                        }
                        .business-contact {
                            display: flex;
                            justify-content: space-between;
                            align-items: flex-start;
                            margin-top: 6px;
                            font-size: 7px;
                        }
                        .business-contact-left {
                            text-align: left;
                        }
                        .business-contact-right {
                            text-align: right;
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
                                    <div>Mob: 0313-9829984, 0313-6777811</div>
                                </div>
                                <div class="business-contact-right">
                                    <div>Email: farhan.akhtar90@yahoo.com</div>
                                </div>
                            </div>
                        </div>
                        <p style="margin-top: 10px;">Order Receipt</p>
                    </div>
                    <div class="order-info">
                        <p><strong>Date:</strong> ${orderDate}</p>
                        <p><strong>Customer:</strong> ${customerName}</p>
                        <p><strong>Payment Method:</strong> ${paymentMethodText}</p>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Qty</th>
                                <th>Discount</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            cart.forEach(item => {
                let itemTotal = item.quantity * item.selling_price;
                let discount = 0;
                if (item.discount > 0) {
                    if (item.discount_type === 'percentage') {
                        discount = itemTotal * (item.discount / 100);
                    } else {
                        discount = item.discount;
                    }
                }
                itemTotal -= discount;

                const selectedUnit = units.find(u => u.id == item.unit_id);
                const unitName = selectedUnit ? selectedUnit.short_name : (item.unit_name || 'Pcs');

                printContent += `
                    <tr>
                        <td>${item.name}</td>
                        <td>${item.quantity} ${unitName}</td>
                        <td>${item.discount > 0 ? (item.discount_type === 'percentage' ? item.discount + '%' : 'PKR ' + item.discount.toFixed(2)) : '-'}</td>
                        <td>PKR ${itemTotal.toFixed(2)}</td>
                    </tr>
                `;
            });

            printContent += `
                        </tbody>
                    </table>
                    <div class="total">
                        <p>Grand Total: PKR ${grandTotal.toFixed(2)}</p>
                    </div>
                    <div style="text-align: center; margin-top: 20px; padding-top: 15px; border-top: 2px solid #000; font-size: 10px;">
                        <p>Thank you for your business!</p>
                    </div>
                </body>
                </html>
            `;

            printWindow.document.write(printContent);
            printWindow.document.close();
            printWindow.print();
            
            // Remove edit_order_id from URL after printing
            const url = new URL(window.location);
            if (url.searchParams.has('edit_order_id')) {
                url.searchParams.delete('edit_order_id');
                window.history.replaceState({}, '', url);
            }
        }

        // Toggle left panel
        let leftPanelVisible = true;
        function toggleLeftPanel() {
            const leftPanel = document.getElementById('left-panel');
            const rightPanel = document.getElementById('right-panel');
            const toggleIcon = document.getElementById('toggle-left-panel-icon');
            
            if (!leftPanel || !toggleIcon) return;
            
            // Don't allow hiding if right panel is already hidden (at least one must be visible)
            if (leftPanelVisible && rightPanel && rightPanel.style.display === 'none') {
                return; // Can't hide left panel if right is already hidden
            }
            
            if (leftPanelVisible) {
                // Hide left panel
                leftPanel.style.display = 'none';
                if (document.getElementById('panel-resizer')) {
                    document.getElementById('panel-resizer').style.display = 'none';
                }
                
                // Always show right panel (expand it)
                if (rightPanel) {
                    rightPanel.style.display = '';
                    rightPanel.style.width = '100%';
                    // Update right panel icon (right panel is now visible, so icon points left to hide it)
                    const rightToggleIcon = document.getElementById('toggle-right-panel-icon');
                    if (rightToggleIcon) {
                        rightToggleIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>';
                    }
                    rightPanelVisible = true;
                }
                
                toggleIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>';
                leftPanelVisible = false;
            } else {
                // Show left panel
                leftPanel.style.display = '';
                if (document.getElementById('panel-resizer')) {
                    document.getElementById('panel-resizer').style.display = '';
                }
                
                // Restore right panel to normal size
                if (rightPanel) {
                    rightPanel.style.width = '100%';
                    // Update right panel icon
                    const rightToggleIcon = document.getElementById('toggle-right-panel-icon');
                    if (rightToggleIcon) {
                        rightToggleIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>';
                        rightPanelVisible = true;
                    }
                }
                leftPanel.style.width = '30%';
                
                toggleIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>';
                leftPanelVisible = true;
                
                // Update products grid columns
                setTimeout(function() {
                    const productsGrid = document.getElementById('products-grid');
                    if (productsGrid && leftPanel) {
                        const leftPanelWidthPx = leftPanel.offsetWidth;
                        let columns = 8;
                        if (leftPanelWidthPx < 400) columns = 3;
                        else if (leftPanelWidthPx < 550) columns = 4;
                        else if (leftPanelWidthPx < 700) columns = 5;
                        else if (leftPanelWidthPx < 850) columns = 6;
                        else if (leftPanelWidthPx < 1000) columns = 7;
                        productsGrid.style.gridTemplateColumns = `repeat(${columns}, minmax(0, 1fr))`;
                    }
                }, 100);
            }
        }

        // Toggle right panel
        let rightPanelVisible = true;
        function toggleRightPanel() {
            const rightPanel = document.getElementById('right-panel');
            const leftPanel = document.getElementById('left-panel');
            const toggleIcon = document.getElementById('toggle-right-panel-icon');
            
            if (!rightPanel || !toggleIcon) return;
            
            // Don't allow hiding if left panel is already hidden (at least one must be visible)
            if (rightPanelVisible && leftPanel && leftPanel.style.display === 'none') {
                return; // Can't hide right panel if left is already hidden
            }
            
            if (rightPanelVisible) {
                // Hide right panel
                rightPanel.style.display = 'none';
                if (document.getElementById('panel-resizer')) {
                    document.getElementById('panel-resizer').style.display = 'none';
                }
                
                // Always show left panel (expand it)
                if (leftPanel) {
                    leftPanel.style.display = '';
                    leftPanel.style.width = '100%';
                    // Update left panel icon (left panel is now visible, so icon points right to hide it)
                    const leftToggleIcon = document.getElementById('toggle-left-panel-icon');
                    if (leftToggleIcon) {
                        leftToggleIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>';
                    }
                    leftPanelVisible = true;
                    
                    // Update products grid columns
                    setTimeout(updateProductsGridColumns, 100);
                }
                
                toggleIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>';
                rightPanelVisible = false;
            } else {
                // Show right panel
                rightPanel.style.display = '';
                if (document.getElementById('panel-resizer')) {
                    document.getElementById('panel-resizer').style.display = '';
                }
                
                // Restore left panel to normal size
                if (leftPanel) {
                    leftPanel.style.width = '0%';
                    // Update left panel icon
                    const leftToggleIcon = document.getElementById('toggle-left-panel-icon');
                    if (leftToggleIcon) {
                        leftToggleIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>';
                        leftPanelVisible = true;
                    }
                }
                rightPanel.style.width = '100%';
                
                toggleIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>';
                rightPanelVisible = true;
                
                // Update products grid columns
                setTimeout(updateProductsGridColumns, 100);
            }
        }

        // Toggle purchase price visibility
        function togglePurchasePrice() {
            purchasePriceVisible = !purchasePriceVisible;
            renderCart();
        }

        // Open custom product modal (for adding new)
        function openCustomProductModal() {
            editingCustomProductIndex = null;
            const modal = document.getElementById('custom-product-modal');
            if (modal) {
                modal.classList.remove('hidden');
                // Reset form
                document.getElementById('custom-product-form').reset();
                // Update modal title and button
                document.querySelector('#custom-product-modal h3').textContent = 'Add Custom Product (Shift + C)';
                document.querySelector('#custom-product-modal button[type="submit"]').textContent = 'Add to Cart';
                // Focus on name field
                setTimeout(() => {
                    document.getElementById('custom-product-name').focus();
                }, 100);
            }
        }

        // Edit custom product
        function editCustomProduct(index) {
            if (index < 0 || index >= cart.length) return;
            
            const item = cart[index];
            const isCustom = item.product_id === null || item.is_custom === true;
            
            if (!isCustom) {
                alert('This is not a custom product');
                return;
            }
            
            editingCustomProductIndex = index;
            const modal = document.getElementById('custom-product-modal');
            if (modal) {
                modal.classList.remove('hidden');
                // Pre-fill form with existing values
                document.getElementById('custom-product-name').value = item.name || '';
                document.getElementById('custom-product-price').value = parseFloat(item.selling_price || 0).toFixed(2);
                document.getElementById('custom-product-unit').value = item.unit_id || '';
                document.getElementById('custom-product-quantity').value = parseFloat(item.quantity || 0).toFixed(2);
                // Update modal title and button
                document.querySelector('#custom-product-modal h3').textContent = 'Edit Custom Product';
                document.querySelector('#custom-product-modal button[type="submit"]').textContent = 'Update Product';
                // Focus on name field
                setTimeout(() => {
                    document.getElementById('custom-product-name').focus();
                }, 100);
            }
        }

        // Close custom product modal
        function closeCustomProductModal() {
            const modal = document.getElementById('custom-product-modal');
            if (modal) {
                modal.classList.add('hidden');
                // Reset form
                document.getElementById('custom-product-form').reset();
                editingCustomProductIndex = null;
            }
        }

        // Open custom product modal from anywhere on POS (Shift+C); ignored while typing in fields
        document.addEventListener('keydown', function posCustomProductShortcut(e) {
            if (!e.shiftKey || e.ctrlKey || e.metaKey || e.altKey) return;
            if (e.key.toLowerCase() !== 'c') return;
            const el = e.target;
            if (el && (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA' || el.tagName === 'SELECT')) return;
            if (el && el.isContentEditable) return;
            const modal = document.getElementById('custom-product-modal');
            if (modal && !modal.classList.contains('hidden')) return;
            e.preventDefault();
            openCustomProductModal();
        });

        // Add or update custom product in cart
        function addCustomProduct(event) {
            event.preventDefault();
            
            const name = document.getElementById('custom-product-name').value.trim();
            const price = parseFloat(document.getElementById('custom-product-price').value) || 0;
            const quantity = parseFloat(document.getElementById('custom-product-quantity').value) || 0;
            const unitId = document.getElementById('custom-product-unit').value;
            
            if (!name) {
                alert('Please enter product name');
                return;
            }
            
            if (price <= 0) {
                alert('Price must be greater than 0');
                return;
            }
            
            if (quantity <= 0) {
                alert('Quantity must be greater than 0');
                return;
            }
            
            if (!unitId) {
                alert('Please select a unit');
                return;
            }
            
            // Find selected unit
            const selectedUnit = units.find(u => u.id == unitId);
            if (!selectedUnit) {
                alert('Invalid unit selected');
                return;
            }
            
            const customProductData = {
                product_id: null, // null indicates custom product
                name: name,
                purchase_price: 0, // No purchase price for custom products
                selling_price: price,
                retail_price: price,
                wholesale_price: price,
                selling_type: 'retail',
                price_type: 'retail',
                quantity: quantity,
                unit_id: parseInt(unitId),
                unit_name: selectedUnit.short_name,
                stock_quantity: 999999, // Unlimited stock for custom products
                discount_type: cart[editingCustomProductIndex]?.discount_type || 'percentage',
                discount: cart[editingCustomProductIndex]?.discount || 0,
                is_custom: true // Flag to identify custom products
            };
            
            // Update existing item or add new one
            if (editingCustomProductIndex !== null && editingCustomProductIndex >= 0 && editingCustomProductIndex < cart.length) {
                // Preserve discount settings when editing
                cart[editingCustomProductIndex] = {
                    ...cart[editingCustomProductIndex],
                    ...customProductData
                };
            } else {
                // Add new custom product to cart
                cart.push(customProductData);
            }
            
            // Close modal
            closeCustomProductModal();
            
            // Render cart
            renderCart();
        }

        // Function to update products grid columns based on left panel width
        function updateProductsGridColumns() {
            const productsGrid = document.getElementById('products-grid');
            const leftPanel = document.getElementById('left-panel');
            if (!productsGrid || !leftPanel) return;
            
            // Get actual width in pixels
            const leftPanelWidthPx = leftPanel.offsetWidth;
            
            // Determine columns based on width
            let columns = 8; // default
            if (leftPanelWidthPx < 400) {
                columns = 3;
            } else if (leftPanelWidthPx < 550) {
                columns = 4;
            } else if (leftPanelWidthPx < 700) {
                columns = 5;
            } else if (leftPanelWidthPx < 850) {
                columns = 6;
            } else if (leftPanelWidthPx < 1000) {
                columns = 7;
            } else {
                columns = 8;
            }
            
            productsGrid.style.gridTemplateColumns = `repeat(${columns}, minmax(0, 1fr))`;
        }

        // Draggable panel resizer
        let isResizing = false;
        
        document.addEventListener('DOMContentLoaded', function() {
            const customerTypeFilters = document.getElementById('customer-type-filters');
            if (customerTypeFilters) {
                customerTypeFilters.addEventListener('pointerdown', function(e) {
                    const chip = e.target.closest('.customer-type-filter-chip');
                    if (!chip) return;
                    e.preventDefault();
                    const chipType = chip.getAttribute('data-customer-type-filter') || 'all';
                    setCustomerTypeFilter(chipType);
                });
            }

            const resizer = document.getElementById('panel-resizer');
            const leftPanel = document.getElementById('left-panel');
            const rightPanel = document.getElementById('right-panel');
            
            if (resizer && leftPanel && rightPanel) {
                resizer.addEventListener('mousedown', function(e) {
                    isResizing = true;
                    document.body.style.cursor = 'col-resize';
                    document.body.style.userSelect = 'none';
                    e.preventDefault();
                });
                
                document.addEventListener('mousemove', function(e) {
                    if (!isResizing) return;
                    
                    const container = resizer.parentElement;
                    const containerWidth = container.offsetWidth;
                    const resizerWidth = resizer.offsetWidth;
                    const mouseX = e.clientX - container.getBoundingClientRect().left;
                    
                    // Calculate percentages
                    const leftPercent = (mouseX / containerWidth) * 100;
                    const rightPercent = 100 - leftPercent - (resizerWidth / containerWidth) * 100;
                    
                    // Constrain between 20% and 80%
                    if (leftPercent >= 20 && leftPercent <= 80) {
                        leftPanel.style.width = leftPercent + '%';
                        rightPanel.style.width = rightPercent + '%';
                        leftPanel.style.transition = 'none';
                        rightPanel.style.transition = 'none';
                        
                        // Update products grid columns based on new width
                        updateProductsGridColumns();
                    }
                });
                
                document.addEventListener('mouseup', function() {
                    if (isResizing) {
                        isResizing = false;
                        document.body.style.cursor = '';
                        document.body.style.userSelect = '';
                        leftPanel.style.transition = '';
                        rightPanel.style.transition = '';
                    }
                });
            }
        });

        // Initialize
        setPaymentMethod('cash');
        
        // Initialize cart on page load
        renderCart();
        
        // Initialize search on page load if there's a search value
        const searchInput = document.getElementById('product-search');
        if (searchInput && searchInput.value) {
            searchProducts(searchInput.value);
        }
        
        // Auto-load hold order if load_hold parameter is in URL
        const urlParams = new URLSearchParams(window.location.search);
        const loadHoldId = urlParams.get('load_hold');
        if (loadHoldId) {
            // Wait a bit for the page to fully load, then load the hold order
            setTimeout(function() {
                loadHoldOrderIntoCart(loadHoldId);
                // Remove the query parameter from URL after loading
                const newUrl = window.location.pathname;
                window.history.replaceState({}, document.title, newUrl);
            }, 500);
        }
        
        // Auto-load order for editing if editOrder is present
        if (editOrder && editOrder.items) {
            setTimeout(async function() {
                await loadOrderIntoCart(editOrder);
            }, 500);
        }
        
        // Function to load order items into cart for editing
        async function loadOrderIntoCart(order) {
            if (cart.length > 0) {
                if (!confirm('You have items in your cart. Loading this order will replace them. Continue?')) {
                    return;
                }
            }

            // Clear current cart
            cart = [];

            // Set customer information
            if (order.customer && order.customer.id) {
                const customer = allCustomers.find(c => c.id == order.customer.id);
                if (customer) {
                    selectCustomer(customer);
                } else {
                    selectCustomer({
                        id: order.customer.id,
                        name: order.customer.name || '',
                        customer_id: order.customer.customer_id || '',
                        customer_type: order.customer.customer_type || '',
                        phone: '',
                        email: '',
                        previous_balance: 0
                    });
                }
            } else {
                clearCustomerSelection();
            }

            // Load items into cart
            if (order.items && order.items.length > 0) {
                order.items.forEach(item => {
                    const isCustom = item.product_id === null || !item.product_id;

                    // For regular products, find the product in the products array to get full details
                    let product = null;
                    if (!isCustom) {
                        product = products.find(p => Number(p.id) === Number(item.product_id));
                    }

                    // Calculate discount value
                    let discountValue = 0;
                    if (item.discount && item.discount > 0) {
                        discountValue = item.discount;
                    }

                    if (isCustom || !product) {
                        // Custom product or product not found
                        cart.push({
                            product_id: null,
                            name: item.product_name || item.name || 'Custom Product',
                            purchase_price: 0,
                            selling_price: parseFloat(item.unit_price || item.selling_price) || 0,
                            retail_price: parseFloat(item.unit_price || item.selling_price) || 0,
                            wholesale_price: parseFloat(item.unit_price || item.selling_price) || 0,
                            selling_type: 'retail',
                            price_type: 'retail',
                            quantity: parseFloat(item.quantity) || 0,
                            unit_id: item.unit_id || null,
                            unit_name: item.unit_name || item.unit_short_name || 'Pcs',
                            stock_quantity: 999999,
                            discount_type: 'fixed',
                            discount: discountValue,
                            is_custom: true,
                        });
                    } else {
                        cart.push({
                            product_id: Number(item.product_id),
                            name: product.name,
                            purchase_price: parseFloat(product.purchase_price) || 0,
                            selling_price: parseFloat(item.unit_price || item.selling_price) || 0,
                            retail_price: parseFloat(product.retail_price || product.selling_price) || 0,
                            wholesale_price: parseFloat(product.wholesale_price || product.selling_price) || 0,
                            selling_type: product.selling_type || 'retail',
                            price_type: product.selling_type === 'both' ? 'retail' : (product.selling_type || 'retail'),
                            quantity: parseFloat(item.quantity) || 0,
                            unit_id: item.unit_id || product.unit_id,
                            unit_name: item.unit_name || product.unit_name,
                            base_unit_id: product.base_unit_id || product.unit_id,
                            selling_units: product.selling_units || [],
                            stock_quantity: parseFloat(product.stock_quantity) || 0,
                            discount_type: 'fixed',
                            discount: discountValue,
                        });
                    }
                });
            }

            await refreshAllCartPurchasePrices();
            
            // Show success message
            alert(`Order ${order.sale_number || order.order_number || ''} loaded for editing!`);
        }
        
        // Debug: Log products array
        console.log('Products loaded:', products.length, 'products');
        if (products.length > 0) {
            console.log('Sample product:', products[0]);
        }

        // Hold Orders Modal Functions
        function openHoldOrdersModal() {
            const modal = document.getElementById('hold-orders-modal');
            if (modal) {
                modal.classList.remove('hidden');
                loadHoldOrders();
            }
        }

        function closeHoldOrdersModal() {
            const modal = document.getElementById('hold-orders-modal');
            if (modal) {
                modal.classList.add('hidden');
            }
        }

        function loadHoldOrders() {
            const listContainer = document.getElementById('hold-orders-list');
            if (!listContainer) return;

            listContainer.innerHTML = `
                <div class="text-center py-8 text-gray-400">
                    <svg class="w-16 h-16 mx-auto mb-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <p>Loading hold orders...</p>
                </div>
            `;

            fetch('{{ route("sales.pos.hold-orders") }}', {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(async response => {
                const data = await response.json();
                if (data.success && data.hold_orders) {
                    displayHoldOrders(data.hold_orders);
                } else {
                    listContainer.innerHTML = `
                        <div class="text-center py-8 text-gray-400">
                            <p>No hold orders found</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error loading hold orders:', error);
                listContainer.innerHTML = `
                    <div class="text-center py-8 text-red-400">
                        <p>Error loading hold orders. Please try again.</p>
                    </div>
                `;
            });
        }

        function displayHoldOrders(holdOrders) {
            const listContainer = document.getElementById('hold-orders-list');
            if (!listContainer) return;

            if (holdOrders.length === 0) {
                listContainer.innerHTML = `
                    <div class="text-center py-8 text-gray-400">
                        <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <p>No hold orders available</p>
                    </div>
                `;
                return;
            }

            let html = '';
            holdOrders.forEach(order => {
                html += `
                    <div class="bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3 mb-2">
                                    <h4 class="font-semibold text-gray-900">${order.sale_number}</h4>
                                    <span class="px-2 py-1 bg-orange-100 text-orange-800 text-xs rounded-full">Hold</span>
                                </div>
                                <div class="text-sm text-gray-600 space-y-1">
                                    <p><strong>Customer:</strong> ${order.customer_name}</p>
                                    <p><strong>Items:</strong> ${order.item_count} | <strong>Total:</strong> PKR ${parseFloat(order.total_amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
                                    <p><strong>Date:</strong> ${formatDateTime(order.created_at)}</p>
                                </div>
                            </div>
                            <button onclick="loadHoldOrderIntoCart(${order.id})" 
                                    class="ml-4 px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md font-medium transition-colors">
                                Load Order
                            </button>
                        </div>
                    </div>
                `;
            });

            listContainer.innerHTML = html;
        }

        function loadHoldOrderIntoCart(orderId) {
            if (cart.length > 0) {
                if (!confirm('You have items in your cart. Loading this hold order will replace them. Continue?')) {
                    return;
                }
            }

            const url = '{{ route("sales.pos.load-hold-order", ":id") }}'.replace(':id', orderId);
            fetch(url, {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(async response => {
                const data = await response.json();
                if (data.success) {
                    // Clear current cart
                    cart = [];

                    // Set customer information
                    if (data.customer_id) {
                        const customer = allCustomers.find(c => c.id == data.customer_id);
                        if (customer) {
                            selectCustomer(customer);
                        } else {
                            selectCustomer({
                                id: data.customer_id,
                                name: data.customer_name || '',
                                customer_id: '',
                                customer_type: data.customer_type || '',
                                phone: '',
                                email: '',
                                previous_balance: 0
                            });
                        }
                    } else {
                        document.getElementById('customer-search').value = data.customer_name || '';
                        document.getElementById('customer-name').value = data.customer_name || '';
                        updateSelectedCustomerTypeLabel({ customer_type: data.customer_type || '' });
                    }

                    // Load items into cart
                    data.items.forEach(item => {
                        const isCustom = item.product_id === null || item.is_custom === true;

                        // For regular products, find the product in the products array to get full details
                        let product = null;
                        if (!isCustom) {
                            product = products.find(p => Number(p.id) === Number(item.product_id));
                            
                            if (!product) {
                                console.warn('Product not found for held order item:', item.product_id);
                                return;
                            }
                        }

                        // Calculate discount value based on type
                        let discountValue = item.discount || 0;
                        if (item.discount_type === 'percentage' && discountValue > 0) {
                            // Already a percentage
                        } else if (item.discount_type === 'fixed' && discountValue > 0) {
                            // Convert fixed discount to percentage if needed
                            const itemTotal = item.quantity * item.selling_price;
                            if (itemTotal > 0) {
                                discountValue = (discountValue / itemTotal) * 100;
                            }
                        }

                        if (isCustom) {
                            // Custom product - restore as custom cart item
                            cart.push({
                                product_id: null,
                                name: item.name,
                                purchase_price: 0,
                                selling_price: parseFloat(item.selling_price) || 0,
                                retail_price: parseFloat(item.retail_price || item.selling_price) || 0,
                                wholesale_price: parseFloat(item.wholesale_price || item.selling_price) || 0,
                                selling_type: 'retail',
                                price_type: 'retail',
                                quantity: parseFloat(item.quantity) || 0,
                                unit_id: item.unit_id || null,
                                unit_name: item.unit_name || 'Pcs',
                                stock_quantity: 999999,
                                discount_type: item.discount_type || 'percentage',
                                discount: discountValue,
                                is_custom: true,
                            });
                        } else {
                            cart.push({
                                product_id: Number(item.product_id),
                                name: item.name,
                                purchase_price: parseFloat(item.purchase_price) || 0,
                                selling_price: parseFloat(item.selling_price) || 0,
                                retail_price: parseFloat(item.retail_price) || 0,
                                wholesale_price: parseFloat(item.wholesale_price) || 0,
                                selling_type: item.selling_type || 'retail',
                                price_type: item.price_type || (item.selling_type === 'both' ? 'retail' : item.selling_type),
                                quantity: parseFloat(item.quantity) || 0,
                                unit_id: item.unit_id || product.unit_id,
                                unit_name: item.unit_name || product.unit_name,
                                base_unit_id: product.base_unit_id || product.unit_id,
                                selling_units: product.selling_units || [],
                                stock_quantity: parseFloat(item.stock_quantity) || 0,
                                discount_type: item.discount_type || 'percentage',
                                discount: discountValue,
                            });
                        }
                    });

                    await refreshAllCartPurchasePrices();
                    
                    // Delete the hold order after loading
                    deleteHoldOrder(orderId);
                    
                    // Close modal
                    closeHoldOrdersModal();
                    
                    // Show success message
                    alert(`Hold order ${data.sale_number} loaded successfully!`);
                } else {
                    alert('Error loading hold order: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error loading hold order:', error);
                alert('Error loading hold order. Please try again.');
            });
        }

        function deleteHoldOrder(orderId) {
            const url = '{{ route("sales.pos.delete-hold-order", ":id") }}'.replace(':id', orderId);
            fetch(url, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(async response => {
                const data = await response.json();
                if (data.success) {
                    // Reload hold orders list to reflect the deletion
                    loadHoldOrders();
                } else {
                    console.warn('Error deleting hold order:', data.message);
                }
            })
            .catch(error => {
                console.error('Error deleting hold order:', error);
            });
        }

        // Global Keyboard Shortcuts
        window.addEventListener('keydown', function(event) {
            // Shift + C: Open Custom Product Modal
            if (event.shiftKey && (event.key === 'C' || event.key === 'c')) {
                // Check if not currently typing in an input or textarea
                if (['INPUT', 'TEXTAREA'].indexOf(document.activeElement.tagName) === -1) {
                    event.preventDefault();
                    openCustomProductModal();
                }
            }
        });
    </script>

    <!-- Calculator Modal -->
    <div id="calculator-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-xl shadow-2xl w-80 p-5">
            <!-- Header -->
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Calculator</h3>
                <button onclick="closeCalculator()" class="text-gray-500 hover:text-gray-700 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Display -->
            <div class="mb-4">
                <input type="text" id="calc-display" readonly value="0"
                       class="w-full px-4 py-3 text-right text-3xl font-bold border border-gray-300 rounded-lg bg-white text-gray-900 focus:outline-none">
            </div>
            
            <!-- Calculator Buttons -->
            <div class="grid grid-cols-4 gap-2.5">
                <!-- Row 1: ( ) % AC -->
                <button onclick="calculatorInput('(')" class="px-3 py-3 bg-blue-50 hover:bg-blue-100 active:bg-blue-200 text-gray-900 rounded-lg font-semibold text-lg shadow-sm transition-colors">(</button>
                <button onclick="calculatorInput(')')" class="px-3 py-3 bg-blue-50 hover:bg-blue-100 active:bg-blue-200 text-gray-900 rounded-lg font-semibold text-lg shadow-sm transition-colors">)</button>
                <button onclick="calculatorInput('%')" class="px-3 py-3 bg-blue-50 hover:bg-blue-100 active:bg-blue-200 text-gray-900 rounded-lg font-semibold text-lg shadow-sm transition-colors">%</button>
                <button onclick="calculatorInput('C')" class="px-3 py-3 bg-blue-50 hover:bg-blue-100 active:bg-blue-200 text-gray-900 rounded-lg font-semibold text-lg shadow-sm transition-colors">AC</button>
                
                <!-- Row 2: 7 8 9 ÷ -->
                <button onclick="calculatorInput('7')" class="px-3 py-3 bg-gray-50 hover:bg-gray-100 active:bg-gray-200 text-gray-900 rounded-lg font-semibold text-lg shadow-sm transition-colors">7</button>
                <button onclick="calculatorInput('8')" class="px-3 py-3 bg-gray-50 hover:bg-gray-100 active:bg-gray-200 text-gray-900 rounded-lg font-semibold text-lg shadow-sm transition-colors">8</button>
                <button onclick="calculatorInput('9')" class="px-3 py-3 bg-gray-50 hover:bg-gray-100 active:bg-gray-200 text-gray-900 rounded-lg font-semibold text-lg shadow-sm transition-colors">9</button>
                <button onclick="calculatorInput('÷')" class="px-3 py-3 bg-blue-50 hover:bg-blue-100 active:bg-blue-200 text-gray-900 rounded-lg font-semibold text-lg shadow-sm transition-colors">÷</button>
                
                <!-- Row 3: 4 5 6 × -->
                <button onclick="calculatorInput('4')" class="px-3 py-3 bg-gray-50 hover:bg-gray-100 active:bg-gray-200 text-gray-900 rounded-lg font-semibold text-lg shadow-sm transition-colors">4</button>
                <button onclick="calculatorInput('5')" class="px-3 py-3 bg-gray-50 hover:bg-gray-100 active:bg-gray-200 text-gray-900 rounded-lg font-semibold text-lg shadow-sm transition-colors">5</button>
                <button onclick="calculatorInput('6')" class="px-3 py-3 bg-gray-50 hover:bg-gray-100 active:bg-gray-200 text-gray-900 rounded-lg font-semibold text-lg shadow-sm transition-colors">6</button>
                <button onclick="calculatorInput('×')" class="px-3 py-3 bg-blue-50 hover:bg-blue-100 active:bg-blue-200 text-gray-900 rounded-lg font-semibold text-lg shadow-sm transition-colors">×</button>
                
                <!-- Row 4: 1 2 3 - -->
                <button onclick="calculatorInput('1')" class="px-3 py-3 bg-gray-50 hover:bg-gray-100 active:bg-gray-200 text-gray-900 rounded-lg font-semibold text-lg shadow-sm transition-colors">1</button>
                <button onclick="calculatorInput('2')" class="px-3 py-3 bg-gray-50 hover:bg-gray-100 active:bg-gray-200 text-gray-900 rounded-lg font-semibold text-lg shadow-sm transition-colors">2</button>
                <button onclick="calculatorInput('3')" class="px-3 py-3 bg-gray-50 hover:bg-gray-100 active:bg-gray-200 text-gray-900 rounded-lg font-semibold text-lg shadow-sm transition-colors">3</button>
                <button onclick="calculatorInput('-')" class="px-3 py-3 bg-blue-50 hover:bg-blue-100 active:bg-blue-200 text-gray-900 rounded-lg font-semibold text-lg shadow-sm transition-colors">-</button>
                
                <!-- Row 5: 0 . = + -->
                <button onclick="calculatorInput('0')" class="px-3 py-3 bg-gray-50 hover:bg-gray-100 active:bg-gray-200 text-gray-900 rounded-lg font-semibold text-lg shadow-sm transition-colors">0</button>
                <button onclick="calculatorInput('.')" class="px-3 py-3 bg-gray-50 hover:bg-gray-100 active:bg-gray-200 text-gray-900 rounded-lg font-semibold text-lg shadow-sm transition-colors">.</button>
                <button onclick="calculatorInput('=')" class="px-3 py-3 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded-lg font-semibold text-lg shadow-md col-span-2 transition-colors">=</button>
                <button onclick="calculatorInput('+')" class="px-3 py-3 bg-blue-50 hover:bg-blue-100 active:bg-blue-200 text-gray-900 rounded-lg font-semibold text-lg shadow-sm transition-colors">+</button>
            </div>
            
            <!-- Insert Button -->
            <button onclick="insertCalculatorValue()" 
                    class="w-full mt-4 px-4 py-3 bg-green-500 hover:bg-green-600 active:bg-green-700 text-white rounded-lg font-semibold shadow-md transition-colors">
                Insert to Customer Name
            </button>
        </div>
    </div>

    <!-- Hold Orders Modal -->
    <div id="hold-orders-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col">
            <!-- Modal Header -->
            <div class="bg-gray-800 text-white px-6 py-4 flex justify-between items-center">
                <h3 class="text-xl font-bold">Hold Orders</h3>
                <button onclick="closeHoldOrdersModal()" class="text-white hover:text-gray-300 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Hold Orders List -->
            <div class="flex-1 overflow-y-auto p-6">
                <div id="hold-orders-list" class="space-y-3">
                    <div class="text-center py-8 text-gray-400">
                        <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p>Loading hold orders...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom Product Modal -->
    <div id="custom-product-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-2xl max-w-md w-full">
            <!-- Modal Header -->
            <div class="bg-gray-800 text-white px-6 py-4 flex justify-between items-center rounded-t-lg">
                <h3 class="text-xl font-bold">Add Custom Product (Shift + C)</h3>
                <button onclick="closeCustomProductModal()" class="text-white hover:text-gray-300 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Modal Content -->
            <div class="p-6">
                <form id="custom-product-form" onsubmit="addCustomProduct(event)">
                    <div class="mb-4">
                        <label for="custom-product-name" class="block text-sm font-medium text-gray-700 mb-2">
                            Product Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="custom-product-name" 
                               required
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
                               placeholder="Enter product name">
                    </div>
                    
                    <div class="mb-4">
                        <label for="custom-product-price" class="block text-sm font-medium text-gray-700 mb-2">
                            Price <span class="text-red-500">*</span>
                        </label>
                        <input type="number" 
                               id="custom-product-price" 
                               step="0.01" 
                               min="0" 
                               required
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
                               placeholder="0.00">
                    </div>
                    
                    <div class="mb-4">
                        <label for="custom-product-unit" class="block text-sm font-medium text-gray-700 mb-2">
                            Unit <span class="text-red-500">*</span>
                        </label>
                        <select id="custom-product-unit" 
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                            <option value="">Select Unit</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->short_name }})</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="custom-product-quantity" class="block text-sm font-medium text-gray-700 mb-2">
                            Quantity <span class="text-red-500">*</span>
                        </label>
                        <input type="number" 
                               id="custom-product-quantity" 
                               step="0.01" 
                               min="0.01" 
                               required
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
                               placeholder="1.00">
                    </div>
                    
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" 
                                onclick="closeCustomProductModal()" 
                                class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-md font-medium">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md font-medium">
                            Add to Cart
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Payment Popup Modal -->
    <div id="payment-popup-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-2xl max-w-md w-full">
            <!-- Modal Header -->
            <div class="bg-gray-800 text-white px-6 py-4 flex justify-between items-center rounded-t-lg">
                <h3 class="text-xl font-bold">Payment</h3>
                <button onclick="closePaymentPopup()" class="text-white hover:text-gray-300 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Payment Content -->
            <div class="p-6">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Cart Total</label>
                    <div class="text-xl font-semibold text-gray-800" id="payment-cart-total">PKR 0.00</div>
                </div>
                
                <!-- Previous Balance Section -->
                <div id="previous-balance-section" class="mb-4 hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Previous Balance</label>
                    <div class="text-xl font-semibold text-red-600" id="previous-balance-display">PKR 0.00</div>
                </div>
                
                <div class="mb-4 border-t border-gray-300 pt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Grand Total</label>
                    <div class="text-2xl font-bold text-gray-900" id="payment-grand-total">PKR 0.00</div>
                </div>
                
                <div class="mb-4">
                    <label for="paid-amount-input" class="block text-sm font-medium text-gray-700 mb-2">Amount Paid (PKR)</label>
                    <input type="number" 
                           id="paid-amount-input" 
                           step="0.01" 
                           min="0" 
                           class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="0.00"
                           onkeyup="calculatePaymentBalance()"
                           oninput="calculatePaymentBalance()">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Remaining Balance</label>
                    <div class="text-xl font-semibold" id="payment-balance-display">
                        <span class="text-green-600">PKR 0.00</span>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="payment-comment-input" class="block text-sm font-medium text-gray-700 mb-2">Comment <span class="text-red-500">*</span></label>
                    <textarea id="payment-comment-input" 
                              rows="3"
                              required
                              minlength="1"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                              placeholder="pos order"></textarea>
                </div>
                
                <div class="flex space-x-3">
                    <button onclick="closePaymentPopup()" 
                            class="flex-1 px-4 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg font-semibold transition">
                        Cancel
                    </button>
                    <button onclick="confirmPayment()" 
                            class="flex-1 px-4 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold transition">
                        Confirm Payment
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bill Popup Modal -->
    <div id="bill-popup-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
            <!-- Modal Header -->
            <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center z-10">
                <h3 class="text-xl font-bold text-gray-800">Order Receipt</h3>
                <button onclick="closeBillPopup()" class="text-gray-500 hover:text-gray-700 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Bill Content -->
            <div id="bill-content" class="p-6">
                <!-- Content will be dynamically inserted here -->
            </div>
            
            <!-- Modal Footer with Actions -->
            <div class="sticky bottom-0 bg-gray-50 border-t border-gray-200 px-6 py-4 flex space-x-3">
                <button onclick="printOrderReceipt()" 
                        class="flex-1 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold flex items-center justify-center space-x-2 shadow-md transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                    </svg>
                    <span>Print Receipt</span>
                </button>
                <button onclick="closeBillPopup()" 
                        class="flex-1 px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg font-semibold transition">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- New Customer Modal -->
    <div id="new-customer-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-2xl max-w-md w-full">
            <!-- Modal Header -->
            <div class="bg-gray-800 text-white px-6 py-4 flex justify-between items-center rounded-t-lg">
                <h3 class="text-xl font-bold">Add New Customer</h3>
                <button onclick="closeNewCustomerModal()" class="text-white hover:text-gray-300 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Customer Form -->
            <form id="new-customer-form" class="p-6" onsubmit="event.preventDefault(); createNewCustomer();">
                <div class="mb-4">
                    <label for="new-customer-name" class="block text-sm font-medium text-gray-700 mb-2">
                        Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="new-customer-name" name="name" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                </div>

                <div class="mb-4">
                    <label for="new-customer-type" class="block text-sm font-medium text-gray-700 mb-2">Customer type</label>
                    <input type="text" id="new-customer-type" name="customer_type" maxlength="100"
                           list="pos-customer-type-suggestions"
                           placeholder="Optional — pick or type a new customer type"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                    <datalist id="pos-customer-type-suggestions">
                        @foreach($customerTypesForPos as $type)
                            <option value="{{ $type }}"></option>
                        @endforeach
                    </datalist>
                    <p class="mt-1 text-xs text-gray-500">New types work immediately for search; the type filter bar updates after you refresh the POS page.</p>
                </div>
                
                <div class="mb-4">
                    <label for="new-customer-phone" class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                    <input type="text" id="new-customer-phone" name="phone"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                </div>
                
                <div class="mb-4">
                    <label for="new-customer-address" class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                    <textarea id="new-customer-address" name="address" rows="2"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"></textarea>
                </div>
                
                <div class="flex space-x-3">
                    <button type="button" onclick="closeNewCustomerModal()" 
                            class="flex-1 px-4 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg font-semibold transition">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="flex-1 px-4 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold transition">
                        Add Customer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div id="add-product-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" onclick="if(event.target === this) closeAddProductModal()">
        <div class="bg-white rounded-lg shadow-2xl max-w-4xl w-full max-h-[90vh] flex flex-col" onclick="event.stopPropagation()">
            <!-- Modal Header -->
            <div class="bg-gray-800 text-white px-6 py-4 flex justify-between items-center rounded-t-lg flex-shrink-0">
                <h3 class="text-xl font-bold">Add Product</h3>
                <button onclick="closeAddProductModal()" class="text-white hover:text-gray-300 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Add New Product Form -->
            <div class="flex-1 overflow-y-auto">
                <div class="p-6">
                    <form id="add-product-form" onsubmit="event.preventDefault(); submitProductForm();" enctype="multipart/form-data">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Product Name -->
                            <div>
                                <label for="modal-product-name" class="block text-sm font-medium text-gray-700 mb-2">
                                    Product Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="modal-product-name" name="name" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                            </div>

                            <!-- Category -->
                            <div>
                                <label for="modal-product-category" class="block text-sm font-medium text-gray-700 mb-2">
                                    Category <span class="text-red-500">*</span>
                                </label>
                                <select id="modal-product-category" name="category_id" required
                                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                                    <option value="">Select Category</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Unit -->
                            <div>
                                <label for="modal-product-unit" class="block text-sm font-medium text-gray-700 mb-2">
                                    Unit <span class="text-red-500">*</span>
                                </label>
                                <select id="modal-product-unit" name="unit_id" required
                                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                                    <option value="">Select Unit</option>
                                    @foreach($units as $unit)
                                        <option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->short_name }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Selling Type -->
                            <div>
                                <label for="modal-product-selling-type" class="block text-sm font-medium text-gray-700 mb-2">
                                    Selling Type <span class="text-red-500">*</span>
                                </label>
                                <select id="modal-product-selling-type" name="selling_type" required onchange="toggleModalPriceFields()"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                                    <option value="">Select</option>
                                    <option value="retail">Retail</option>
                                    <option value="wholesale">Wholesale</option>
                                    <option value="both">Both</option>
                                </select>
                            </div>

                            <!-- Purchase Price -->
                            <div>
                                <label for="modal-product-purchase-price" class="block text-sm font-medium text-gray-700 mb-2">
                                    Purchase Price <span class="text-red-500">*</span>
                                </label>
                                <input type="number" id="modal-product-purchase-price" name="purchase_price" step="0.01" min="0" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                            </div>

                            <!-- Retail Price -->
                            <div id="modal-retail-price-container" class="hidden">
                                <label for="modal-product-retail-price" class="block text-sm font-medium text-gray-700 mb-2">
                                    Retail Price <span class="text-red-500">*</span>
                                </label>
                                <input type="number" id="modal-product-retail-price" name="retail_price" step="0.01" min="0"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                            </div>

                            <!-- Wholesale Price -->
                            <div id="modal-wholesale-price-container" class="hidden">
                                <label for="modal-product-wholesale-price" class="block text-sm font-medium text-gray-700 mb-2">
                                    Wholesale Price <span class="text-red-500">*</span>
                                </label>
                                <input type="number" id="modal-product-wholesale-price" name="wholesale_price" step="0.01" min="0"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                            </div>

                            <!-- Stock Quantity -->
                            <div>
                                <label for="modal-product-stock" class="block text-sm font-medium text-gray-700 mb-2">
                                    Stock Quantity <span class="text-red-500">*</span>
                                </label>
                                <input type="number" id="modal-product-stock" name="stock_quantity" min="0" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                            </div>

                            <!-- Low Stock Threshold -->
                            <div>
                                <label for="modal-product-low-stock" class="block text-sm font-medium text-gray-700 mb-2">
                                    Low Stock Threshold <span class="text-red-500">*</span>
                                </label>
                                <input type="number" id="modal-product-low-stock" name="low_stock_threshold" min="0" value="10" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                            </div>

                            <!-- Product Type -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Product Type <span class="text-red-500">*</span>
                                </label>
                                <div class="flex space-x-4">
                                    <label class="flex items-center">
                                        <input type="radio" name="product_type" value="single" checked class="text-orange-600 focus:ring-orange-500">
                                        <span class="ml-2 text-sm text-gray-700">Single</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="radio" name="product_type" value="variant" class="text-orange-600 focus:ring-orange-500">
                                        <span class="ml-2 text-sm text-gray-700">Variant</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="mt-4">
                            <label for="modal-product-description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <textarea id="modal-product-description" name="description" rows="3"
                                      class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"></textarea>
                        </div>

                        <!-- Image -->
                        <div class="mt-4">
                            <label for="modal-product-image" class="block text-sm font-medium text-gray-700 mb-2">Product Image</label>
                            <input type="file" id="modal-product-image" name="image" accept="image/*"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="border-t border-gray-200 px-6 py-4 flex justify-end space-x-3 flex-shrink-0">
                <button onclick="closeAddProductModal()" 
                        class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg font-semibold transition">
                    Cancel
                </button>
                <button id="modal-submit-btn" onclick="submitProductForm()" 
                        class="px-6 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg font-semibold transition">
                    Add Product
                </button>
            </div>
        </div>
    </div>

    @include('components.receipt-branding')
    @include('components.stock-alert-notify')
</body>
</html>

