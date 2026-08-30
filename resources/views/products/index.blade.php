<x-app-layout>
    <x-slot name="header">
        Products
    </x-slot>

    <!-- Breadcrumb -->
    <div class="mb-4">
        <nav class="text-sm text-gray-600">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-900">Dashboard</a>
            <span class="mx-2">></span>
            <span class="text-gray-900 font-medium">Products</span>
        </nav>
    </div>

    <!-- Action Bar -->
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <!-- Search and Filters -->
            <div class="flex flex-1 items-center gap-4">
                <form method="GET" action="{{ route('products.index') }}" class="flex-1" id="search-form">
                    <div class="relative">
                        <input type="text" 
                               id="search-input"
                               name="search" 
                               value="{{ request('search') }}" 
                               placeholder="Q Search" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
                               oninput="handleSearchInput()">
                        @if(request('search'))
                            <a href="{{ route('products.index', request()->except('search')) }}" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </a>
                        @endif
                    </div>
                </form>

                <!-- Category Filter -->
                <form method="GET" action="{{ route('products.index') }}" class="flex items-center gap-2">
                    @if(request('search'))
                        <input type="hidden" name="search" value="{{ request('search') }}">
                    @endif
                    <select name="category_id" onchange="this.form.submit()" class="px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                        <option value="all" {{ request('category_id') === 'all' || !request('category_id') ? 'selected' : '' }}>Category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </form>

                <!-- Refresh Button -->
                <a href="{{ route('products.index') }}" class="p-2 text-gray-500 hover:text-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                </a>
            </div>

            <!-- Add Product Button -->
            <div>
                <a href="{{ route('products.create') }}" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-md font-medium inline-flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Add Product
                </a>
            </div>
        </div>
    </div>

    <!-- Products Table -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden -mx-2 sm:mx-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-2 sm:px-3 py-3 text-left w-8">
                            <input type="checkbox" class="rounded border-gray-300 text-orange-600 focus:ring-orange-500" id="select-all">
                        </th>
                        <th class="px-2 sm:px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-12">SNO</th>
                        <th class="px-2 sm:px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product Name</th>
                        <th class="px-2 sm:px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-2 sm:px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                        <th class="px-2 sm:px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit</th>
                        <th class="px-2 sm:px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Extra</th>
                        <th class="px-2 sm:px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                        <th class="px-2 sm:px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($products as $product)
                    <tr class="hover:bg-gray-50">
                        <td class="px-2 sm:px-3 py-3">
                            <input type="checkbox" class="product-checkbox rounded border-gray-300 text-orange-600 focus:ring-orange-500" value="{{ $product->id }}">
                        </td>
                        <td class="px-2 sm:px-3 py-3 whitespace-nowrap">
                            <span class="text-sm font-medium text-gray-900">{{ ($products->currentPage() - 1) * $products->perPage() + $loop->iteration }}</span>
                        </td>
                        <td class="px-2 sm:px-3 py-3">
                            <div class="flex items-center min-w-0">
                                <div class="w-8 h-8 bg-gray-200 rounded flex items-center justify-center mr-2 flex-shrink-0">
                                    @if($product->image)
                                        <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="w-full h-full object-cover rounded">
                                    @else
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    @endif
                                </div>
                                <span class="text-sm font-medium text-gray-900 break-words">{{ $product->name }}</span>
                            </div>
                        </td>
                        <td class="px-2 sm:px-3 py-3">
                            <span class="text-sm text-gray-900 break-words">{{ $product->category->name ?? 'N/A' }}</span>
                        </td>
                        <td class="px-2 sm:px-3 py-3 whitespace-nowrap">
                            <span class="text-sm font-medium text-gray-900">PKR {{ number_format($product->selling_price, 2) }}</span>
                        </td>
                        @php
                            $baseUnitId = $product->base_unit_id ?? $product->unit_id;
                            $baseUnitName = $product->baseUnit->short_name ?? $product->unit->short_name ?? 'Unit';

                            $stockUnits = collect();
                            if ($baseUnitId) {
                                $stockUnits->push([
                                    'id' => (int) $baseUnitId,
                                    'name' => $baseUnitName,
                                ]);
                            }

                            foreach ($product->productUnits as $productUnit) {
                                if (! $productUnit->is_active || ! $productUnit->unit) {
                                    continue;
                                }

                                $stockUnits->push([
                                    'id' => (int) $productUnit->unit_id,
                                    'name' => $productUnit->unit->short_name,
                                ]);
                            }

                            if ($stockUnits->isEmpty() && $product->unit) {
                                $stockUnits->push([
                                    'id' => (int) $product->unit_id,
                                    'name' => $product->unit->short_name,
                                ]);
                            }

                            $stockUnits = $stockUnits->unique('id')->values();
                            $defaultUnit = $stockUnits->firstWhere('id', (int) $baseUnitId) ?? $stockUnits->first();
                        @endphp
                        <td class="px-2 sm:px-3 py-3 whitespace-nowrap">
                            <span class="text-sm text-gray-900">{{ $defaultUnit['name'] ?? ($product->unit->short_name ?? 'N/A') }}</span>
                        </td>
                        <td class="px-2 sm:px-3 py-3 whitespace-nowrap">
                            @php $extraPrice = (float) ($product->extra_price ?? 0); @endphp
                            @if($extraPrice > 0)
                                <span class="text-sm font-medium text-gray-900">PKR {{ number_format($extraPrice, 2) }}</span>
                            @else
                                <span class="text-sm text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-2 sm:px-3 py-3 whitespace-nowrap">
                            <button type="button"
                                    class="js-stock-toggle text-sm text-gray-900 text-left"
                                    data-product-id="{{ $product->id }}"
                                    data-base-qty="{{ (float) $product->stock_quantity }}"
                                    data-base-unit-id="{{ (int) ($baseUnitId ?? 0) }}"
                                    data-current-unit-id="{{ (int) ($defaultUnit['id'] ?? 0) }}">
                                <span class="js-stock-qty">{{ number_format((float) $product->stock_quantity, 2) }}</span>
                                <span class="js-stock-unit">{{ $defaultUnit['name'] ?? ($product->unit->short_name ?? '') }}</span>
                            </button>
                            @if($stockUnits->count() > 1)
                                <div class="text-[11px] text-orange-600 mt-1">Click to change unit</div>
                            @endif
                            <script type="application/json" id="product-stock-units-{{ $product->id }}">@json($stockUnits)</script>
                        </td>
                        <td class="px-2 sm:px-3 py-3 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center space-x-2">
                                <a href="{{ route('products.show', $product) }}" class="text-blue-600 hover:text-blue-900" title="View">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </a>
                                <a href="{{ route('products.edit', $product) }}" class="text-green-600 hover:text-green-900" title="Edit">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>
                                <form action="{{ route('products.destroy', $product) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this product?');">
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
                        <td colspan="9" class="px-3 py-4 text-center text-gray-500">
                            No products found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-3 sm:px-4 py-4 border-t border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center">
                <span class="text-sm text-gray-700">Row Per Page</span>
                <form method="GET" action="{{ route('products.index') }}" class="ml-2">
                    @if(request('search'))
                        <input type="hidden" name="search" value="{{ request('search') }}">
                    @endif
                    @if(request('category_id'))
                        <input type="hidden" name="category_id" value="{{ request('category_id') }}">
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
                @if($products->hasPages())
                    <div class="flex items-center space-x-1">
                        @if($products->onFirstPage())
                            <span class="px-3 py-1 text-gray-400 cursor-not-allowed">&lt;</span>
                        @else
                            <a href="{{ $products->previousPageUrl() }}" class="px-3 py-1 text-gray-700 hover:text-gray-900">&lt;</a>
                        @endif
                        
                        @foreach($products->getUrlRange(1, min(5, $products->lastPage())) as $page => $url)
                            @if($page == $products->currentPage())
                                <span class="px-3 py-1 bg-orange-500 text-white rounded">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="px-3 py-1 text-gray-700 hover:text-gray-900">{{ $page }}</a>
                            @endif
                        @endforeach
                        
                        @if($products->hasMorePages())
                            <span class="px-2 py-1 text-gray-500">...</span>
                            <a href="{{ $products->url($products->lastPage()) }}" class="px-3 py-1 text-gray-700 hover:text-gray-900">{{ $products->lastPage() }}</a>
                        @endif
                        
                        @if($products->hasMorePages())
                            <a href="{{ $products->nextPageUrl() }}" class="px-3 py-1 text-gray-700 hover:text-gray-900">&gt;</a>
                        @else
                            <span class="px-3 py-1 text-gray-400 cursor-not-allowed">&gt;</span>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        const stockConversionCache = {};

        function getProductStockUnits(productId) {
            const el = document.getElementById(`product-stock-units-${productId}`);
            if (!el) return [];
            try {
                const parsed = JSON.parse(el.textContent || '[]');
                return Array.isArray(parsed) ? parsed : [];
            } catch (e) {
                console.warn('Failed to parse stock units for product', productId, e);
                return [];
            }
        }

        async function getStockConversionFactor(productId, fromUnitId, toUnitId) {
            const fromId = Number(fromUnitId);
            const toId = Number(toUnitId);
            if (!productId || !fromId || !toId) return null;
            if (fromId === toId) return 1;

            const cacheKey = `${productId}:${fromId}:${toId}`;
            if (Object.prototype.hasOwnProperty.call(stockConversionCache, cacheKey)) {
                return stockConversionCache[cacheKey];
            }

            try {
                const response = await fetch(`/products/${productId}/conversion/${fromId}/${toId}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await response.json();
                if (response.ok && data.success && data.conversion_factor != null) {
                    const factor = parseFloat(data.conversion_factor);
                    if (!Number.isNaN(factor) && factor > 0) {
                        stockConversionCache[cacheKey] = factor;
                        return factor;
                    }
                }
            } catch (error) {
                console.error('Error fetching stock conversion factor:', error);
            }

            return null;
        }

        async function toggleStockUnit(button) {
            if (!button) return;
            const productId = Number(button.dataset.productId || 0);
            const baseQty = parseFloat(button.dataset.baseQty || 0);
            const baseUnitId = Number(button.dataset.baseUnitId || 0);
            const currentUnitId = Number(button.dataset.currentUnitId || 0);
            const units = getProductStockUnits(productId);

            if (!productId || !baseUnitId || units.length <= 1) return;

            const currentIndex = units.findIndex(u => Number(u.id) === currentUnitId);
            const nextIndex = currentIndex >= 0 ? (currentIndex + 1) % units.length : 0;
            const nextUnit = units[nextIndex];
            const nextUnitId = Number(nextUnit.id);

            let convertedQty = baseQty;
            if (nextUnitId !== baseUnitId) {
                const factor = await getStockConversionFactor(productId, baseUnitId, nextUnitId);
                if (factor == null) {
                    alert('Conversion factor not found for selected unit.');
                    return;
                }
                convertedQty = baseQty * factor;
            }

            button.dataset.currentUnitId = String(nextUnitId);
            const qtyEl = button.querySelector('.js-stock-qty');
            const unitEl = button.querySelector('.js-stock-unit');
            if (qtyEl) qtyEl.textContent = Number(convertedQty).toFixed(2);
            if (unitEl) unitEl.textContent = nextUnit.name || '';
        }

        document.addEventListener('click', async function (event) {
            const toggleBtn = event.target.closest('.js-stock-toggle');
            if (!toggleBtn) return;
            event.preventDefault();
            await toggleStockUnit(toggleBtn);
        });

        // Select all checkbox functionality
        document.getElementById('select-all').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.product-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });

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
