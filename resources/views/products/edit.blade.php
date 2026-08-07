<x-app-layout>
    <x-slot name="header">
        Edit Product
    </x-slot>

    <!-- Breadcrumb -->
    <div class="mb-4">
        <nav class="text-sm text-gray-600">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-900">Dashboard</a>
            <span class="mx-2">></span>
            <a href="{{ route('products.index') }}" class="hover:text-gray-900">Products</a>
            <span class="mx-2">></span>
            <span class="text-gray-900 font-medium">Edit Product</span>
        </nav>
    </div>

    <!-- Display validation errors -->
    @if ($errors->any())
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
            <strong>Please fix the following errors:</strong>
            <ul class="list-disc list-inside mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    
    <!-- Display success message -->
    @if (session('success'))
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
            {{ session('success') }}
            @if (session('conversions_created'))
                <div class="mt-2 text-sm">
                    {{ session('conversions_created') }} conversion factor(s) saved successfully.
                </div>
            @endif
        </div>
    @endif

    @if (session('warning'))
        <div class="mb-4 p-4 bg-yellow-100 border border-yellow-400 text-yellow-700 rounded">
            {{ session('warning') }}
            @if (session('conversions_created'))
                <div class="mt-1 text-sm">
                    {{ session('conversions_created') }} conversion factor(s) were saved successfully.
                </div>
            @endif
        </div>
    @endif

    <!-- Display conversion errors -->
    @if (session('conversion_errors') && count(session('conversion_errors')) > 0)
        <div class="mb-4 p-4 bg-red-50 border border-red-300 text-red-800 rounded">
            <strong>The following conversion factors could not be saved:</strong>
            <ul class="list-disc list-inside mt-2">
                @foreach (session('conversion_errors') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <div class="mt-2 text-xs text-red-700">
                Please review the rows below and re-submit. Common cause: the same (from unit, to unit) pair already exists for this product.
            </div>
        </div>
    @endif

    @if (!empty($branchOnlyEdits))
        <div class="mb-4 p-4 bg-amber-50 border border-amber-300 text-amber-900 rounded">
            Name, prices, stock, and selling type save <strong>for this branch only</strong>. The Admin catalog product is not changed.
            Category, SKU, image, and units stay as set by Admin.
        </div>
    @endif

    <form method="POST" action="{{ route('products.update', $product) }}" enctype="multipart/form-data" class="space-y-6" id="product-form">
        @csrf
        @method('PUT')

        <!-- Product Information Section -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center mb-6">
                <div class="bg-orange-100 rounded-full p-2 mr-3">
                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">Product Information</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Product Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Product Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="name" 
                           name="name" 
                           value="{{ old('name', $product->name) }}" 
                           required
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
                           placeholder="Enter product name">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Slug -->
                <div>
                    <label for="slug" class="block text-sm font-medium text-gray-700 mb-2">
                        Slug <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="slug" 
                           name="slug" 
                           value="{{ old('slug', $product->slug) }}" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
                           placeholder="Auto-generated from name">
                    @error('slug')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Base Unit (UOM) -->
                <div>
                    <label for="base_unit_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Base Unit (UOM) <span class="text-red-500">*</span>
                        <span class="text-xs text-gray-500 ml-2">(Unit for stock management)</span>
                    </label>
                    <div class="flex gap-2">
                        <select id="base_unit_id" 
                                name="base_unit_id" 
                                required
                                onchange="updateBaseUnit()"
                                class="flex-1 px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                            <option value="">Select Base Unit</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}" {{ old('base_unit_id', $product->base_unit_id ?? $product->unit_id) == $unit->id ? 'selected' : '' }}>
                                    {{ $unit->name }} ({{ $unit->short_name }})
                                </option>
                            @endforeach
                        </select>
                        <a href="{{ route('units.create') }}" target="_blank" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-md whitespace-nowrap">
                            Add New
                        </a>
                    </div>
                    <!-- Keep unit_id for backward compatibility -->
                    <input type="hidden" id="unit_id" name="unit_id" value="{{ old('unit_id', $product->unit_id) }}">
                    @error('base_unit_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    @error('unit_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Quantity -->
                <div>
                    <label for="stock_quantity" class="block text-sm font-medium text-gray-700 mb-2">
                        Quantity
                    </label>
                    <input type="number" 
                           id="stock_quantity" 
                           name="stock_quantity" 
                           value="{{ old('stock_quantity', $product->stock_quantity) }}" 
                           min="0"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
                           placeholder="Enter quantity">
                    @error('stock_quantity')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Selling Type -->
                <div>
                    <label for="selling_type" class="block text-sm font-medium text-gray-700 mb-2">
                        Selling Type <span class="text-red-500">*</span>
                        <span class="text-xs font-normal text-gray-500">(for current branch)</span>
                    </label>
                    <select id="selling_type" 
                            name="selling_type" 
                            required
                            onchange="togglePriceFields()"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                        <option value="">Select</option>
                        <option value="retail" {{ old('selling_type', $product->selling_type) == 'retail' ? 'selected' : '' }}>Retail</option>
                        <option value="wholesale" {{ old('selling_type', $product->selling_type) == 'wholesale' ? 'selected' : '' }}>Wholesale</option>
                        <option value="both" {{ old('selling_type', $product->selling_type) == 'both' ? 'selected' : '' }}>Both</option>
                    </select>
                    @error('selling_type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Category and Item Code -->
                <div>
                    <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Category <span class="text-red-500">*</span>
                    </label>
                    <div class="flex gap-2">
                        <select id="category_id" 
                                name="category_id" 
                                required
                                class="flex-1 px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                            <option value="">Select</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        <a href="{{ route('categories.create') }}" target="_blank" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-md whitespace-nowrap">
                            Add New
                        </a>
                    </div>
                    @error('category_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Item Code (SKU) -->
                <div>
                    <label for="sku" class="block text-sm font-medium text-gray-700 mb-2">
                        Item Code (SKU) <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="sku" 
                           name="sku" 
                           value="{{ old('sku', $product->sku) }}" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
                           placeholder="Auto-generated SKU">
                    @error('sku')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Description -->
            <div class="mt-6">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                    Description
                </label>
                <textarea id="description" 
                          name="description" 
                          rows="6"
                          maxlength="5000"
                          class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
                          placeholder="Type your message">{{ old('description', $product->description) }}</textarea>
                <div class="mt-2 flex items-center justify-between text-sm text-gray-500">
                    <div class="flex items-center space-x-4">
                        <button type="button" class="text-gray-500 hover:text-gray-700" title="Bold">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 4h8a4 4 0 014 4 4 4 0 01-4 4H6z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 12h9a4 4 0 014 4 4 4 0 01-4 4H6"></path>
                            </svg>
                        </button>
                        <button type="button" class="text-gray-500 hover:text-gray-700" title="Italic">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 16M6 20l-4-16"></path>
                            </svg>
                        </button>
                        <button type="button" class="text-gray-500 hover:text-gray-700" title="Underline">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 19h14M5 5h14"></path>
                            </svg>
                        </button>
                    </div>
                    <span>Maximum 90 Words</span>
                </div>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Supplier Section -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center mb-6">
                <div class="bg-orange-100 rounded-full p-2 mr-3">
                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">Supplier</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="supplier_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Supplier
                    </label>
                    <div class="flex gap-2">
                        <select id="supplier_id" 
                                name="supplier_id" 
                                class="flex-1 px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                            <option value="">Select Supplier</option>
                            @foreach($suppliers as $supplierOption)
                                <option value="{{ $supplierOption->id }}" {{ old('supplier_id', $product->supplier_id) == $supplierOption->id ? 'selected' : '' }}>
                                    {{ $supplierOption->name }}@if($supplierOption->company_name) ({{ $supplierOption->company_name }})@endif
                                </option>
                            @endforeach
                        </select>
                        <a href="{{ route('suppliers.create') }}" target="_blank" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-md whitespace-nowrap">
                            Add New
                        </a>
                    </div>
                    @error('supplier_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Pricing & Stocks Section -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center mb-6">
                <div class="bg-orange-100 rounded-full p-2 mr-3">
                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">Pricing & Stocks</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Product Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">
                        Product Type <span class="text-red-500">*</span>
                    </label>
                    <div class="flex space-x-4">
                        <label class="flex items-center">
                            <input type="radio" 
                                   name="product_type" 
                                   value="single" 
                                   {{ old('product_type', $product->product_type) == 'single' ? 'checked' : '' }}
                                   required
                                   class="text-orange-600 focus:ring-orange-500">
                            <span class="ml-2 text-sm text-gray-700">Single Product</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" 
                                   name="product_type" 
                                   value="variant" 
                                   {{ old('product_type', $product->product_type) == 'variant' ? 'checked' : '' }}
                                   class="text-orange-600 focus:ring-orange-500">
                            <span class="ml-2 text-sm text-gray-700">Variant Product</span>
                        </label>
                    </div>
                    @error('product_type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Quantity Alert -->
                <div>
                    <label for="quantity_alert" class="block text-sm font-medium text-gray-700 mb-2">
                        Quantity Alert <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="quantity_alert" 
                           name="quantity_alert" 
                           value="{{ old('quantity_alert', $product->quantity_alert) }}" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
                           placeholder="Enter quantity alert">
                    @error('quantity_alert')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Purchase Price -->
                <div>
                    <label for="purchase_price" class="block text-sm font-medium text-gray-700 mb-2">
                        Purchase Price <span class="text-red-500">*</span>
                    </label>
                    <input type="number" 
                           id="purchase_price" 
                           name="purchase_price" 
                           value="{{ old('purchase_price', $product->purchase_price) }}" 
                           step="0.01"
                           min="0"
                           required
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
                           placeholder="0.00">
                    @error('purchase_price')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Retail Price (shown for retail or both) -->
                <div id="retail_price_container" class="hidden">
                    <label for="retail_price" class="block text-sm font-medium text-gray-700 mb-2">
                        Retail Price <span class="text-red-500">*</span>
                    </label>
                    <input type="number" 
                           id="retail_price" 
                           name="retail_price" 
                           value="{{ old('retail_price', $product->retail_price) }}" 
                           step="0.01"
                           min="0"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
                           placeholder="0.00">
                    @error('retail_price')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Wholesale Price (shown for wholesale or both) -->
                <div id="wholesale_price_container" class="hidden">
                    <label for="wholesale_price" class="block text-sm font-medium text-gray-700 mb-2">
                        Wholesale Price <span class="text-red-500">*</span>
                    </label>
                    <input type="number" 
                           id="wholesale_price" 
                           name="wholesale_price" 
                           value="{{ old('wholesale_price', $product->wholesale_price) }}" 
                           step="0.01"
                           min="0"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
                           placeholder="0.00">
                    @error('wholesale_price')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Selling Price (legacy field, kept for backward compatibility) -->
                <input type="hidden" id="selling_price" name="selling_price" value="{{ old('selling_price', $product->selling_price) }}">

                <!-- Low Stock Threshold -->
                <div>
                    <label for="low_stock_threshold" class="block text-sm font-medium text-gray-700 mb-2">
                        Low Stock Threshold
                    </label>
                    <input type="number" 
                           id="low_stock_threshold" 
                           name="low_stock_threshold" 
                           value="{{ old('low_stock_threshold', $product->low_stock_threshold) }}" 
                           min="0"
                           required
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
                           placeholder="10">
                    @error('low_stock_threshold')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Multiple Units & Conversions Section -->
        <div class="bg-white rounded-lg shadow-sm p-6" id="units-conversions-section" @if(!empty($branchOnlyEdits)) data-branch-readonly="1" @endif>
            <div class="flex items-center mb-6">
                <div class="bg-orange-100 rounded-full p-2 mr-3">
                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">Selling Units & Conversions</h3>
            </div>

            @if (!empty($branchOnlyEdits))
                <div class="mb-4 p-3 bg-gray-50 border border-gray-200 text-sm text-gray-700 rounded">
                    Units and conversions are managed by Admin and are not changed when you save branch prices.
                </div>
            @endif

            <div class="mb-4">
                <p class="text-sm text-gray-600 mb-4">
                    Configure additional selling units for this product. The base unit (selected above) will be automatically included. Set conversion factors to convert between units.
                </p>
            </div>

            <!-- Hidden base unit data (auto-populated from base_unit_id) -->
            <input type="hidden" id="base-unit-id-hidden" @if(empty($branchOnlyEdits)) name="units[0][unit_id]" @endif value="{{ $product->base_unit_id ?? $product->unit_id }}">
            @if(empty($branchOnlyEdits))
            <input type="hidden" name="units[0][is_base_unit]" value="1">
            <input type="hidden" id="base-unit-retail-price-hidden" name="units[0][retail_price]" value="{{ $product->retail_price ?? '' }}">
            <input type="hidden" id="base-unit-wholesale-price-hidden" name="units[0][wholesale_price]" value="{{ $product->wholesale_price ?? '' }}">
            @else
            <input type="hidden" id="base-unit-retail-price-hidden" value="{{ $product->retail_price ?? '' }}">
            <input type="hidden" id="base-unit-wholesale-price-hidden" value="{{ $product->wholesale_price ?? '' }}">
            @endif

            <!-- Locked Base Unit Display -->
            <div id="base-unit-display" class="mb-4 p-4 border-2 border-orange-300 rounded-lg bg-orange-50">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-orange-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                        <label class="text-sm font-semibold text-gray-700">Base Unit (Locked)</label>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Unit</label>
                        <input type="text" 
                               id="base-unit-name-display"
                               readonly
                               disabled
                               value="@php
                                   $baseUnitId = $product->base_unit_id ?? $product->unit_id;
                                   $baseUnit = $units->firstWhere('id', $baseUnitId);
                                   echo $baseUnit ? $baseUnit->name . ' (' . $baseUnit->short_name . ')' : '';
                               @endphp"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 text-gray-700 cursor-not-allowed">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Selling Price</label>
                        <input type="text" 
                               id="base-unit-price-display"
                               readonly
                               disabled
                               value="{{ $product->selling_price ? number_format($product->selling_price, 2, '.', '') : '' }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 text-gray-700 cursor-not-allowed">
                    </div>
                </div>
            </div>

            <!-- Selling Units Container -->
            <div id="selling-units-container" class="space-y-4">
                @php
                    $sellingUnitIndex = 1;
                    $existingSellingUnits = isset($productUnits) ? $productUnits->where('is_base_unit', false) : collect();
                @endphp
                @if(isset($productUnits) && $existingSellingUnits->count() > 0)
                    @foreach($existingSellingUnits as $productUnit)
                        <div class="p-4 border border-gray-300 rounded-lg bg-gray-50" id="selling-unit-{{ $sellingUnitIndex }}">
                            <div class="flex items-center justify-between mb-3">
                                <label class="text-sm font-semibold text-gray-700">Selling Unit</label>
                                @if(empty($branchOnlyEdits))
                                <button type="button" onclick="removeSellingUnit({{ $sellingUnitIndex }})" class="text-red-600 hover:text-red-800">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                                @endif
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Unit <span class="text-red-500">*</span></label>
                                    <select @if(empty($branchOnlyEdits)) name="units[{{ $sellingUnitIndex }}][unit_id]" @endif
                                            @if(!empty($branchOnlyEdits)) disabled @else required @endif
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                        <option value="">Select Unit</option>
                                        @foreach($units as $unit)
                                            <option value="{{ $unit->id }}" 
                                                    {{ $productUnit->unit_id == $unit->id ? 'selected' : '' }}
                                                    {{ ($product->base_unit_id ?? $product->unit_id) == $unit->id ? 'disabled' : '' }}>
                                                {{ $unit->name }} ({{ $unit->short_name }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @if(empty($branchOnlyEdits))
                                    <input type="hidden" name="units[{{ $sellingUnitIndex }}][is_base_unit]" value="0">
                                    <input type="hidden" name="units[{{ $sellingUnitIndex }}][id]" value="{{ $productUnit->id }}">
                                    @endif
                                    <p class="text-xs text-gray-500 mt-1">Price will be calculated from base unit if not set</p>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Price per Unit (Optional)</label>
                                    <input type="number" 
                                           @if(empty($branchOnlyEdits)) name="units[{{ $sellingUnitIndex }}][selling_price]" @endif
                                           value="{{ $productUnit->selling_price ? number_format($productUnit->selling_price, 2, '.', '') : '' }}"
                                           step="0.01"
                                           min="0"
                                           max="999999.99"
                                           @if(!empty($branchOnlyEdits)) disabled @endif
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md"
                                           placeholder="Auto-calculated"
                                           onblur="this.value = this.value ? parseFloat(this.value).toFixed(2) : ''">
                                    <p class="text-xs text-gray-500 mt-1">Leave empty to calculate from base unit price</p>
                                </div>
                            </div>
                        </div>
                        @php $sellingUnitIndex++; @endphp
                    @endforeach
                @endif
            </div>

            <!-- Add Selling Unit Button -->
            @if(empty($branchOnlyEdits))
            <button type="button" 
                    onclick="addSellingUnit()" 
                    class="mt-4 w-full bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-md border-2 border-dashed border-gray-300 flex items-center justify-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Add Selling Unit
            </button>
            @endif

            <!-- Conversions Container -->
            <div class="mt-6 pt-6 border-t border-gray-200">
                <h4 class="text-sm font-semibold text-gray-700 mb-4">Unit Conversions</h4>
                <div id="conversions-container" class="space-y-3">
                    @php
                        $conversionIndex = 0;
                    @endphp
                    @if(isset($conversions) && $conversions->count() > 0)
                        @foreach($conversions as $conversion)
                            @php
                                $baseUnitId = $product->base_unit_id ?? $product->unit_id;
                                $baseUnit = $units->firstWhere('id', $baseUnitId);
                                $baseUnitName = $baseUnit ? $baseUnit->name . ' (' . $baseUnit->short_name . ')' : '';
                            @endphp
                            <div class="p-3 border border-gray-200 rounded bg-white" id="conversion-{{ $conversionIndex }}">
                                <div class="grid grid-cols-4 gap-3 items-end">
                                    <div>
                                        <label class="block text-xs text-gray-600 mb-1">From Unit <span class="text-red-500">*</span></label>
                                        @if(empty($branchOnlyEdits))
                                        <input type="hidden" name="conversions[{{ $conversionIndex }}][from_unit_id]" value="{{ $baseUnitId }}" class="conversion-from-unit-hidden">
                                        @endif
                                        <input type="text" 
                                               value="{{ $baseUnitName }}"
                                               readonly
                                               disabled
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm bg-gray-100 text-gray-700 cursor-not-allowed conversion-from-unit-display">
                                        <span class="text-xs text-red-500 conversion-error hidden"></span>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-600 mb-1">To Unit <span class="text-red-500">*</span></label>
                                        <select @if(empty($branchOnlyEdits)) name="conversions[{{ $conversionIndex }}][to_unit_id]" @endif
                                                @if(!empty($branchOnlyEdits)) disabled @else required @endif
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm conversion-to-unit"
                                                onchange="validateConversionRow(this.closest('[id^=conversion-]'))">
                                            <option value="">Select</option>
                                            @foreach($units as $unit)
                                                <option value="{{ $unit->id }}" {{ $conversion->to_unit_id == $unit->id ? 'selected' : '' }}>
                                                    {{ $unit->name }} ({{ $unit->short_name }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <span class="text-xs text-red-500 conversion-error hidden"></span>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-600 mb-1">Conversion Factor <span class="text-red-500">*</span></label>
                                        <input type="number" 
                                               @if(empty($branchOnlyEdits)) name="conversions[{{ $conversionIndex }}][factor]" @endif
                                               value="{{ number_format($conversion->conversion_factor, 2, '.', '') }}"
                                               step="0.01"
                                               min="0.01"
                                               max="999999.99"
                                               @if(!empty($branchOnlyEdits)) disabled @else required @endif
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm conversion-factor"
                                               placeholder="e.g., 50.00"
                                               onchange="validateConversionRow(this.closest('[id^=conversion-]'))"
                                               onblur="this.value = parseFloat(this.value || 0).toFixed(2); validateConversionRow(this.closest('[id^=conversion-]'))">
                                        @if(empty($branchOnlyEdits))
                                        <input type="hidden" name="conversions[{{ $conversionIndex }}][id]" value="{{ $conversion->id }}">
                                        @endif
                                        <span class="text-xs text-gray-500">(1 from_unit = factor × to_unit)</span>
                                        <span class="text-xs text-red-500 conversion-error hidden block"></span>
                                    </div>
                                    <div>
                                        @if(empty($branchOnlyEdits))
                                        <button type="button" 
                                                onclick="removeConversion({{ $conversionIndex }})" 
                                                class="w-full px-3 py-2 bg-red-100 hover:bg-red-200 text-red-700 rounded-md text-sm">
                                            Remove
                                        </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @php $conversionIndex++; @endphp
                        @endforeach
                    @endif
                </div>
                @if(empty($branchOnlyEdits))
                <button type="button" 
                        onclick="addConversion()" 
                        class="mt-3 text-sm text-orange-600 hover:text-orange-700 underline">
                    + Add Conversion Factor
                </button>
                @endif
            </div>
        </div>

        <!-- Images Section -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center mb-6">
                <div class="bg-orange-100 rounded-full p-2 mr-3">
                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">Images</h3>
            </div>

            <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center">
                <input type="file" 
                       id="image" 
                       name="image" 
                       accept="image/*"
                       class="hidden"
                       onchange="previewImage(this)">
                <label for="image" class="cursor-pointer">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <p class="mt-2 text-sm text-gray-600">Click to upload image</p>
                </label>
                <div id="image-preview" class="mt-4 {{ $product->image ? '' : 'hidden' }}">
                    <img id="preview" src="{{ $product->image ? asset('storage/' . $product->image) : '' }}" alt="Preview" class="mx-auto h-32 w-32 object-cover rounded-lg">
                </div>
                @if($product->image)
                    <p class="mt-2 text-xs text-gray-500 text-center">Current image</p>
                @endif
                @error('image')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Custom Fields Section -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center mb-6">
                <div class="bg-orange-100 rounded-full p-2 mr-3">
                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">Custom Fields</h3>
            </div>

            <div class="space-y-4">
                <div class="flex items-center space-x-4">
                    <label class="flex items-center">
                        <input type="checkbox" 
                               id="enable_manufacturer" 
                               class="rounded border-gray-300 text-orange-600 focus:ring-orange-500"
                               onchange="toggleManufacturerFields()">
                        <span class="ml-2 text-sm text-gray-700">Manufacturer</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" 
                               id="enable_expiry" 
                               class="rounded border-gray-300 text-orange-600 focus:ring-orange-500"
                               onchange="toggleExpiryFields()">
                        <span class="ml-2 text-sm text-gray-700">Expiry</span>
                    </label>
                </div>

                <div id="manufacturer_fields" class="grid grid-cols-1 md:grid-cols-2 gap-6 hidden">
                    <div>
                        <label for="manufacturer" class="block text-sm font-medium text-gray-700 mb-2">
                            Manufacturer <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="manufacturer" 
                               name="manufacturer" 
                               value="{{ old('manufacturer', $product->manufacturer) }}" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
                               placeholder="Enter manufacturer name">
                        @error('manufacturer')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="manufactured_date" class="block text-sm font-medium text-gray-700 mb-2">
                            Manufactured Date <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="date" 
                                   id="manufactured_date" 
                                   name="manufactured_date" 
                                   value="{{ old('manufactured_date', $product->manufactured_date ? $product->manufactured_date->format('Y-m-d') : '') }}" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                            <svg class="absolute right-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        @error('manufactured_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div id="expiry_fields" class="hidden">
                    <div>
                        <label for="expiry_date" class="block text-sm font-medium text-gray-700 mb-2">
                            Expiry Date <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="date" 
                                   id="expiry_date" 
                                   name="expiry_date" 
                                   value="{{ old('expiry_date', $product->expiry_date ? $product->expiry_date->format('Y-m-d') : '') }}" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                            <svg class="absolute right-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        @error('expiry_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Product History Section -->
        @if($productHistory && $productHistory->count() > 0)
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center mb-6">
                <div class="bg-orange-100 rounded-full p-2 mr-3">
                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">Product Update History</h3>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Supplier</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Type</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-700 uppercase">Qty Added</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-700 uppercase">Old Price</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-700 uppercase">New Price</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-700 uppercase">Stock</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Bill #</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($productHistory as $history)
                        <tr>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                {{ $history->transaction_date->format('Y-m-d') }}
                                <div class="text-xs text-gray-500">{{ $history->transaction_date->format('h:i A') }}</div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                {{ $history->supplier->name ?? 'N/A' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                    @if($history->type == 'created') bg-green-100 text-green-800
                                    @elseif($history->type == 'quantity_added') bg-blue-100 text-blue-800
                                    @elseif($history->type == 'price_updated') bg-yellow-100 text-yellow-800
                                    @else bg-purple-100 text-purple-800
                                    @endif">
                                    {{ ucfirst(str_replace('_', ' ', $history->type)) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-900">
                                {{ $history->quantity_added > 0 ? '+' . number_format($history->quantity_added) : '-' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-900">
                                {{ $history->old_price ? 'PKR ' . number_format($history->old_price, 2) : '-' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-semibold text-gray-900">
                                {{ $history->new_price ? 'PKR ' . number_format($history->new_price, 2) : '-' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-900">
                                {{ number_format($history->old_stock_quantity ?? 0) }} → {{ number_format($history->new_stock_quantity ?? 0) }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                {{ $history->supplierBill->bill_number ?? ($history->supplierBill ? '#' . $history->supplierBill->id : '-') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Footer Buttons -->
        <div class="flex justify-end space-x-4 pb-6">
            <a href="{{ route('products.index') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-md font-medium">
                Cancel
            </a>
            <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white px-6 py-2 rounded-md font-medium">
                Update Product
            </button>
        </div>
    </form>

    <script>
        // Auto-generate SKU function
        function generateSku(productName = '') {
            // Get initials from product name
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

            // Generate date part (YYYYMMDD)
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const datePart = year + month + day;

            // Generate random number part (4 digits)
            const randomPart = String(Math.floor(Math.random() * 10000)).padStart(4, '0');

            // Combine: PREFIX-YYYYMMDD-XXXX
            return prefix + '-' + datePart + '-' + randomPart;
        }

        // Auto-generate SKU on page load if not set
        document.addEventListener('DOMContentLoaded', function() {
            const skuInput = document.getElementById('sku');
            if (!skuInput.value || skuInput.value.trim() === '') {
                const productName = document.getElementById('name').value;
                skuInput.value = generateSku(productName);
                skuInput.dataset.autoGenerated = 'true';
            }
        });

        // Auto-generate SKU from product name when name changes (only if auto-generated)
        document.getElementById('name').addEventListener('input', function() {
            const skuInput = document.getElementById('sku');
            if (!skuInput.value || skuInput.dataset.autoGenerated === 'true') {
                skuInput.value = generateSku(this.value);
                skuInput.dataset.autoGenerated = 'true';
            }
        });

        // Mark SKU as manually edited when user changes it
        document.getElementById('sku').addEventListener('input', function() {
            // If user clears the field, regenerate
            if (!this.value || this.value.trim() === '') {
                const productName = document.getElementById('name').value;
                this.value = generateSku(productName);
                this.dataset.autoGenerated = 'true';
            } else {
                // User is editing, mark as manually edited (but allow them to change it)
                // We'll only auto-update if it was auto-generated
            }
        });

        // If user focuses on SKU field and it's empty, generate one
        document.getElementById('sku').addEventListener('focus', function() {
            if (!this.value || this.value.trim() === '') {
                const productName = document.getElementById('name').value;
                this.value = generateSku(productName);
                this.dataset.autoGenerated = 'true';
            }
        });

        // Auto-generate slug from product name
        document.getElementById('name').addEventListener('input', function() {
            const slugInput = document.getElementById('slug');
            if (!slugInput.value || slugInput.dataset.autoGenerated === 'true') {
                slugInput.value = this.value.toLowerCase()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/(^-|-$)/g, '');
                slugInput.dataset.autoGenerated = 'true';
            }
        });

        // Image preview
        function previewImage(input) {
            const preview = document.getElementById('preview');
            const previewDiv = document.getElementById('image-preview');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    previewDiv.classList.remove('hidden');
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Toggle manufacturer fields
        function toggleManufacturerFields() {
            const checkbox = document.getElementById('enable_manufacturer');
            const fields = document.getElementById('manufacturer_fields');
            const manufacturerInput = document.getElementById('manufacturer');
            const manufacturedDateInput = document.getElementById('manufactured_date');
            
            if (checkbox.checked) {
                fields.classList.remove('hidden');
                manufacturerInput.required = true;
                manufacturedDateInput.required = true;
            } else {
                fields.classList.add('hidden');
                manufacturerInput.required = false;
                manufacturedDateInput.required = false;
                manufacturerInput.value = '';
                manufacturedDateInput.value = '';
            }
        }

        // Toggle expiry fields
        function toggleExpiryFields() {
            const checkbox = document.getElementById('enable_expiry');
            const fields = document.getElementById('expiry_fields');
            const expiryInput = document.getElementById('expiry_date');
            
            if (checkbox.checked) {
                fields.classList.remove('hidden');
                expiryInput.required = true;
            } else {
                fields.classList.add('hidden');
                expiryInput.required = false;
                expiryInput.value = '';
            }
        }

        // Initialize checkboxes if old values exist or product has values
        @if(old('manufacturer') || old('manufactured_date') || ($product->manufacturer || $product->manufactured_date))
            document.getElementById('enable_manufacturer').checked = true;
            toggleManufacturerFields();
        @endif

        @if(old('expiry_date') || $product->expiry_date)
            document.getElementById('enable_expiry').checked = true;
            toggleExpiryFields();
        @endif

        // Toggle price fields based on selling type
        function togglePriceFields() {
            const sellingType = document.getElementById('selling_type').value;
            const retailPriceContainer = document.getElementById('retail_price_container');
            const wholesalePriceContainer = document.getElementById('wholesale_price_container');
            const retailPriceInput = document.getElementById('retail_price');
            const wholesalePriceInput = document.getElementById('wholesale_price');
            const sellingPriceInput = document.getElementById('selling_price');

            // Hide both containers first
            retailPriceContainer.classList.add('hidden');
            wholesalePriceContainer.classList.add('hidden');
            retailPriceInput.required = false;
            wholesalePriceInput.required = false;

            // Show appropriate fields based on selling type
            if (sellingType === 'retail') {
                retailPriceContainer.classList.remove('hidden');
                retailPriceInput.required = true;
            } else if (sellingType === 'wholesale') {
                wholesalePriceContainer.classList.remove('hidden');
                wholesalePriceInput.required = true;
            } else if (sellingType === 'both') {
                retailPriceContainer.classList.remove('hidden');
                wholesalePriceContainer.classList.remove('hidden');
                retailPriceInput.required = true;
                wholesalePriceInput.required = true;
            }
        }

        // Validate price against purchase price
        function validatePrice(inputElement, priceType) {
            const purchasePrice = parseFloat(document.getElementById('purchase_price').value) || 0;
            const price = parseFloat(inputElement.value) || 0;
            
            if (price > 0 && price < purchasePrice) {
                inputElement.setCustomValidity(priceType + ' price cannot be less than purchase price.');
                inputElement.classList.add('border-red-500');
                inputElement.classList.remove('border-gray-300');
                return false;
            } else {
                inputElement.setCustomValidity('');
                inputElement.classList.remove('border-red-500');
                inputElement.classList.add('border-gray-300');
                return true;
            }
        }

        // Initialize price fields on page load and set up event listeners
        document.addEventListener('DOMContentLoaded', function() {
            togglePriceFields();
            updateBaseUnit(); // Initialize base unit display
            
            const purchasePriceInput = document.getElementById('purchase_price');
            const retailPriceInput = document.getElementById('retail_price');
            const wholesalePriceInput = document.getElementById('wholesale_price');
            const sellingPriceInput = document.getElementById('selling_price');
            
            // Validate prices when purchase price changes
            if (purchasePriceInput) {
                purchasePriceInput.addEventListener('input', function() {
                    const retailContainer = document.getElementById('retail_price_container');
                    const wholesaleContainer = document.getElementById('wholesale_price_container');
                    if (retailPriceInput && retailContainer && !retailContainer.classList.contains('hidden')) {
                        validatePrice(retailPriceInput, 'Retail');
                    }
                    if (wholesalePriceInput && wholesaleContainer && !wholesaleContainer.classList.contains('hidden')) {
                        validatePrice(wholesalePriceInput, 'Wholesale');
                    }
                });
            }
            
            // Validate retail price
            if (retailPriceInput) {
                retailPriceInput.addEventListener('input', function() {
                    validatePrice(this, 'Retail');
                    const sellingType = document.getElementById('selling_type').value;
                    if (sellingType === 'retail') {
                        sellingPriceInput.value = this.value;
                    }
                    // Sync with hidden base unit retail price
                    const baseUnitRetailPriceHidden = document.getElementById('base-unit-retail-price-hidden');
                    if (baseUnitRetailPriceHidden) {
                        baseUnitRetailPriceHidden.value = this.value;
                    }
                    // Update base unit display
                    updateBaseUnit();
                });
            }

            // Validate wholesale price
            if (wholesalePriceInput) {
                wholesalePriceInput.addEventListener('input', function() {
                    validatePrice(this, 'Wholesale');
                    const sellingType = document.getElementById('selling_type').value;
                    if (sellingType === 'wholesale') {
                        sellingPriceInput.value = this.value;
                    }
                    // Sync with hidden base unit wholesale price
                    const baseUnitWholesalePriceHidden = document.getElementById('base-unit-wholesale-price-hidden');
                    if (baseUnitWholesalePriceHidden) {
                        baseUnitWholesalePriceHidden.value = this.value;
                    }
                    // Update base unit display
                    updateBaseUnit();
                });
            }
            
            // Update base unit display when selling price changes
            if (sellingPriceInput) {
                sellingPriceInput.addEventListener('input', function() {
                    updateBaseUnit();
                });
            }
            
            // Sync prices when selling type changes
            const sellingTypeSelect = document.getElementById('selling_type');
            if (sellingTypeSelect) {
                sellingTypeSelect.addEventListener('change', function() {
                    setTimeout(() => {
                        const retailPrice = document.getElementById('retail_price').value;
                        const wholesalePrice = document.getElementById('wholesale_price').value;
                        if (retailPrice) {
                            const hidden = document.getElementById('base-unit-retail-price-hidden');
                            if (hidden) hidden.value = retailPrice;
                        }
                        if (wholesalePrice) {
                            const hidden = document.getElementById('base-unit-wholesale-price-hidden');
                            if (hidden) hidden.value = wholesalePrice;
                        }
                        // Update base unit display
                        updateBaseUnit();
                    }, 100);
                });
            }
        });

        // Multiple Units Management
        let sellingUnitIndex = {{ isset($productUnits) && $productUnits->where('is_base_unit', false)->count() > 0 ? $productUnits->where('is_base_unit', false)->count() + 1 : 1 }};
        let conversionIndex = {{ isset($conversions) ? $conversions->count() : 0 }};

        // Update base unit when selected
        function updateBaseUnit() {
            const baseUnitSelect = document.getElementById('base_unit_id');
            const baseUnitId = baseUnitSelect.value;
            const unitIdHidden = document.getElementById('unit_id');
            const baseUnitIdHidden = document.getElementById('base-unit-id-hidden');
            const retailPriceInput = document.getElementById('retail_price');
            const wholesalePriceInput = document.getElementById('wholesale_price');
            const sellingPriceInput = document.getElementById('selling_price');
            const baseUnitDisplay = document.getElementById('base-unit-display');
            const baseUnitNameDisplay = document.getElementById('base-unit-name-display');
            const baseUnitPriceDisplay = document.getElementById('base-unit-price-display');
            
            // Update hidden unit_id for backward compatibility
            unitIdHidden.value = baseUnitId;
            
            // Update hidden base unit ID for ProductUnit creation
            if (baseUnitIdHidden) {
                baseUnitIdHidden.value = baseUnitId;
            }
            
            // Update base unit display
            if (baseUnitId) {
                const selectedOption = baseUnitSelect.options[baseUnitSelect.selectedIndex];
                const unitName = selectedOption ? selectedOption.text : '';
                
                if (baseUnitDisplay) {
                    baseUnitDisplay.classList.remove('hidden');
                }
                if (baseUnitNameDisplay) {
                    baseUnitNameDisplay.value = unitName;
                }
                
                // Update price display
                let displayPrice = '';
                if (sellingPriceInput && sellingPriceInput.value) {
                    displayPrice = parseFloat(sellingPriceInput.value).toFixed(2);
                } else if (retailPriceInput && retailPriceInput.value) {
                    displayPrice = parseFloat(retailPriceInput.value).toFixed(2);
                } else if (wholesalePriceInput && wholesalePriceInput.value) {
                    displayPrice = parseFloat(wholesalePriceInput.value).toFixed(2);
                }
                if (baseUnitPriceDisplay) {
                    baseUnitPriceDisplay.value = displayPrice;
                }
            } else {
                if (baseUnitDisplay) {
                    baseUnitDisplay.classList.add('hidden');
                }
            }
        }

        // Add selling unit
        function addSellingUnit() {
            const container = document.getElementById('selling-units-container');
            const baseUnitId = document.getElementById('base_unit_id').value;
            
            if (!baseUnitId) {
                showSnackbar('Please select a base unit first', 'warning');
                return;
            }

            const unitRow = document.createElement('div');
            unitRow.className = 'p-4 border border-gray-300 rounded-lg bg-gray-50';
            unitRow.id = `selling-unit-${sellingUnitIndex}`;
            
            const units = @json($units);
            const unitOptions = units.map(u => 
                `<option value="${u.id}" ${u.id == baseUnitId ? 'disabled' : ''}>${u.name} (${u.short_name})</option>`
            ).join('');

            unitRow.innerHTML = `
                <div class="flex items-center justify-between mb-3">
                    <label class="text-sm font-semibold text-gray-700">Selling Unit</label>
                    <button type="button" onclick="removeSellingUnit(${sellingUnitIndex})" class="text-red-600 hover:text-red-800">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Unit <span class="text-red-500">*</span></label>
                        <select name="units[${sellingUnitIndex}][unit_id]" 
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md selling-unit-select"
                                data-unit-index="${sellingUnitIndex}"
                                onchange="onSellingUnitChange(this)">
                            <option value="">Select Unit</option>
                            ${unitOptions}
                        </select>
                        <input type="hidden" name="units[${sellingUnitIndex}][is_base_unit]" value="0">
                        <p class="text-xs text-gray-500 mt-1">Price will be calculated from base unit if not set</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Price per Unit (Optional)</label>
                        <input type="number" 
                               name="units[${sellingUnitIndex}][selling_price]" 
                               step="0.01"
                               min="0"
                               max="999999.99"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md"
                               placeholder="Auto-calculated"
                               onblur="this.value = this.value ? parseFloat(this.value).toFixed(2) : ''">
                        <p class="text-xs text-gray-500 mt-1">Leave empty to calculate from base unit price</p>
                    </div>
                </div>
            `;
            
            container.appendChild(unitRow);
            sellingUnitIndex++;
        }
        
        // Handle selling unit change - auto-create conversion
        function onSellingUnitChange(selectElement) {
            const selectedUnitId = selectElement.value;
            const unitIndex = selectElement.getAttribute('data-unit-index');
            
            if (!selectedUnitId) {
                // Remove associated conversion if unit is deselected
                removeConversionForSellingUnit(unitIndex);
                return;
            }
            
            const baseUnitId = document.getElementById('base_unit_id').value;
            if (!baseUnitId) {
                showSnackbar('Please select a base unit first', 'warning');
                return;
            }
            
            // Check if conversion already exists for this selling unit
            const conversionsContainer = document.getElementById('conversions-container');
            const existingConversions = conversionsContainer.querySelectorAll('[id^="conversion-"]');
            let conversionExists = false;
            
            existingConversions.forEach(convRow => {
                const toUnitSelect = convRow.querySelector('select[name*="[to_unit_id]"]');
                const fromUnitHidden = convRow.querySelector('input[name*="[from_unit_id]"]');
                if (toUnitSelect && fromUnitHidden && 
                    toUnitSelect.value === selectedUnitId && 
                    fromUnitHidden.value === baseUnitId) {
                    conversionExists = true;
                }
            });
            
            if (!conversionExists) {
                // Auto-create conversion
                const conversionRow = document.createElement('div');
                conversionRow.className = 'p-3 border border-gray-200 rounded bg-white';
                conversionRow.id = `conversion-${conversionIndex}`;
                conversionRow.setAttribute('data-selling-unit-index', unitIndex);
                
                const units = @json($units);
                const baseUnitSelect = document.getElementById('base_unit_id');
                const baseUnitOption = baseUnitSelect.options[baseUnitSelect.selectedIndex];
                const baseUnitName = baseUnitOption ? baseUnitOption.text : '';
                const selectedUnitOption = Array.from(selectElement.options).find(opt => opt.value === selectedUnitId);
                const selectedUnitName = selectedUnitOption ? selectedUnitOption.text : '';
                
                const toUnitOptions = units.map(u => 
                    `<option value="${u.id}" ${u.id == selectedUnitId ? 'selected' : ''} ${u.id == baseUnitId ? 'disabled' : ''}>${u.name} (${u.short_name})</option>`
                ).join('');
                
                conversionRow.innerHTML = `
                    <div class="grid grid-cols-4 gap-3 items-end">
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">From Unit <span class="text-red-500">*</span></label>
                            <input type="hidden" name="conversions[${conversionIndex}][from_unit_id]" value="${baseUnitId}" class="conversion-from-unit-hidden">
                            <input type="text" 
                                   value="${baseUnitName}"
                                   readonly
                                   disabled
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm bg-gray-100 text-gray-700 cursor-not-allowed conversion-from-unit-display">
                            <span class="text-xs text-red-500 conversion-error hidden"></span>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">To Unit <span class="text-red-500">*</span></label>
                            <select name="conversions[${conversionIndex}][to_unit_id]" 
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm conversion-to-unit"
                                    data-selling-unit-index="${unitIndex}"
                                    onchange="validateConversionRow(this.closest('[id^=conversion-]')); syncConversionWithSellingUnit(this)">
                                <option value="">Select</option>
                                ${toUnitOptions}
                            </select>
                            <span class="text-xs text-red-500 conversion-error hidden"></span>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Conversion Factor <span class="text-red-500">*</span></label>
                            <input type="number" 
                                   name="conversions[${conversionIndex}][factor]" 
                                   step="0.01"
                                   min="0.01"
                                   max="999999.99"
                                   required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm conversion-factor"
                                   placeholder="e.g., 50.00"
                                   onchange="validateConversionRow(this.closest('[id^=conversion-]'))"
                                   onblur="this.value = parseFloat(this.value || 0).toFixed(2); validateConversionRow(this.closest('[id^=conversion-]'))">
                            <span class="text-xs text-gray-500">(1 from_unit = factor × to_unit)</span>
                            <span class="text-xs text-red-500 conversion-error hidden block"></span>
                        </div>
                        <div>
                            <button type="button" 
                                    onclick="removeConversion(${conversionIndex})" 
                                    class="w-full px-3 py-2 bg-red-100 hover:bg-red-200 text-red-700 rounded-md text-sm">
                                Remove
                            </button>
                        </div>
                    </div>
                `;
                
                conversionsContainer.appendChild(conversionRow);
                conversionIndex++;
                showSnackbar('Conversion factor automatically added for this selling unit', 'info');
            }
        }
        
        // Sync conversion to_unit when selling unit changes
        function syncConversionWithSellingUnit(toUnitSelect) {
            const unitIndex = toUnitSelect.getAttribute('data-selling-unit-index');
            if (unitIndex) {
                const sellingUnitSelect = document.querySelector(`select.selling-unit-select[data-unit-index="${unitIndex}"]`);
                if (sellingUnitSelect) {
                    sellingUnitSelect.value = toUnitSelect.value;
                }
            }
        }
        
        // Remove conversion associated with a selling unit
        function removeConversionForSellingUnit(unitIndex) {
            const conversionsContainer = document.getElementById('conversions-container');
            const conversions = conversionsContainer.querySelectorAll('[id^="conversion-"]');
            conversions.forEach(convRow => {
                const sellingUnitIndexAttr = convRow.getAttribute('data-selling-unit-index');
                if (sellingUnitIndexAttr === unitIndex) {
                    convRow.remove();
                }
            });
        }

        // Remove selling unit
        function removeSellingUnit(index) {
            const unitRow = document.getElementById(`selling-unit-${index}`);
            if (unitRow) {
                // Remove associated conversion
                removeConversionForSellingUnit(index);
                unitRow.remove();
                showSnackbar('Selling unit and its conversion removed', 'info');
            }
        }

        // Add conversion factor
        function addConversion() {
            const container = document.getElementById('conversions-container');
            const baseUnitId = document.getElementById('base_unit_id').value;
            
            if (!baseUnitId) {
                showSnackbar('Please select a base unit first', 'warning');
                return;
            }

            const conversionRow = document.createElement('div');
            conversionRow.className = 'p-3 border border-gray-200 rounded bg-white';
            conversionRow.id = `conversion-${conversionIndex}`;
            
            const units = @json($units);
            const unitOptions = units.map(u => 
                `<option value="${u.id}">${u.name} (${u.short_name})</option>`
            ).join('');

            // Get base unit name for display
            const baseUnitSelect = document.getElementById('base_unit_id');
            const baseUnitOption = baseUnitSelect.options[baseUnitSelect.selectedIndex];
            const baseUnitName = baseUnitOption ? baseUnitOption.text : '';

            conversionRow.innerHTML = `
                <div class="grid grid-cols-4 gap-3 items-end">
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">From Unit <span class="text-red-500">*</span></label>
                        <input type="hidden" name="conversions[${conversionIndex}][from_unit_id]" value="${baseUnitId}" class="conversion-from-unit-hidden">
                        <input type="text" 
                               value="${baseUnitName}"
                               readonly
                               disabled
                               class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm bg-gray-100 text-gray-700 cursor-not-allowed conversion-from-unit-display">
                        <span class="text-xs text-red-500 conversion-error hidden"></span>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">To Unit <span class="text-red-500">*</span></label>
                        <select name="conversions[${conversionIndex}][to_unit_id]" 
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm conversion-to-unit"
                                onchange="validateConversionRow(this.closest('[id^=conversion-]'))">
                            <option value="">Select</option>
                            ${unitOptions}
                        </select>
                        <span class="text-xs text-red-500 conversion-error hidden"></span>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Conversion Factor <span class="text-red-500">*</span></label>
                        <input type="number" 
                               name="conversions[${conversionIndex}][factor]" 
                               step="0.01"
                               min="0.01"
                               max="999999.99"
                               required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm conversion-factor"
                               placeholder="e.g., 50.00"
                               onchange="validateConversionRow(this.closest('[id^=conversion-]'))"
                               onblur="this.value = parseFloat(this.value || 0).toFixed(2); validateConversionRow(this.closest('[id^=conversion-]'))">
                        <span class="text-xs text-gray-500">(1 from_unit = factor × to_unit)</span>
                        <span class="text-xs text-red-500 conversion-error hidden block"></span>
                    </div>
                    <div>
                        <button type="button" 
                                onclick="removeConversion(${conversionIndex})" 
                                class="w-full px-3 py-2 bg-red-100 hover:bg-red-200 text-red-700 rounded-md text-sm">
                            Remove
                        </button>
                    </div>
                </div>
            `;
            
            container.appendChild(conversionRow);
            conversionIndex++;
            showSnackbar('Conversion factor row added. Please fill all fields.', 'info');
        }

        // Remove conversion
        function removeConversion(index) {
            const conversionRow = document.getElementById(`conversion-${index}`);
            if (conversionRow) {
                conversionRow.remove();
                showSnackbar('Conversion factor removed', 'info');
            }
        }
        
        // Validate a conversion row
        function validateConversionRow(row) {
            if (!row) return false;
            
            const fromUnitHidden = row.querySelector('.conversion-from-unit-hidden');
            const fromUnit = fromUnitHidden ? fromUnitHidden : row.querySelector('.conversion-from-unit');
            const toUnit = row.querySelector('.conversion-to-unit');
            const factor = row.querySelector('.conversion-factor');
            const errorSpans = row.querySelectorAll('.conversion-error');
            
            // Clear previous errors
            errorSpans.forEach(span => {
                span.textContent = '';
                span.classList.add('hidden');
            });
            
            let isValid = true;
            let errors = [];
            
            // Validate from unit
            if (!fromUnit || !fromUnit.value || fromUnit.value === '') {
                errors.push('From unit is required');
                isValid = false;
            }
            
            // Validate to unit
            if (!toUnit.value || toUnit.value === '') {
                errors.push('To unit is required');
                isValid = false;
            }
            
            // Check if from and to units are the same
            if (fromUnit && fromUnit.value && toUnit.value && fromUnit.value === toUnit.value) {
                errors.push('From unit and To unit cannot be the same');
                isValid = false;
                if (errorSpans[1]) {
                    errorSpans[1].textContent = 'Cannot be same as From unit';
                    errorSpans[1].classList.remove('hidden');
                }
            }
            
            // Validate factor
            if (factor.value) {
                const num = parseFloat(factor.value);
                if (isNaN(num) || num <= 0) {
                    errors.push('Conversion factor must be a positive number');
                    isValid = false;
                    if (errorSpans[2]) {
                        errorSpans[2].textContent = 'Conversion factor must be a positive number';
                        errorSpans[2].classList.remove('hidden');
                    }
                } else if (num < 0.01) {
                    errors.push('Conversion factor must be at least 0.01');
                    isValid = false;
                    if (errorSpans[2]) {
                        errorSpans[2].textContent = 'Conversion factor must be at least 0.01';
                        errorSpans[2].classList.remove('hidden');
                    }
                } else {
                    // Check decimal places
                    const decimalPlaces = (factor.value.toString().split('.')[1] || '').length;
                    if (decimalPlaces > 2) {
                        errors.push('Conversion factor can have maximum 2 decimal places');
                        isValid = false;
                        if (errorSpans[2]) {
                            errorSpans[2].textContent = 'Maximum 2 decimal places allowed';
                            errorSpans[2].classList.remove('hidden');
                        }
                    }
                }
            } else if (fromUnit && fromUnit.value && toUnit.value) {
                errors.push('Conversion factor is required');
                isValid = false;
                if (errorSpans[2]) {
                    errorSpans[2].textContent = 'Conversion factor is required';
                    errorSpans[2].classList.remove('hidden');
                }
            }
            
            return isValid;
        }
        
        // Validate all conversions
        function validateAllConversions() {
            const conversionsContainer = document.getElementById('conversions-container');
            if (!conversionsContainer) return true;
            
            const conversionRows = conversionsContainer.querySelectorAll('[id^="conversion-"]');
            let allValid = true;
            
            conversionRows.forEach(row => {
                if (!validateConversionRow(row)) {
                    allValid = false;
                }
            });
            
            return allValid;
        }
        
        // Sync prices before form submission and clean up conversion rows
        const productForm = document.getElementById('product-form');
        if (productForm) {
            productForm.addEventListener('submit', function(e) {
                // Validate all conversions first
                if (!validateAllConversions()) {
                    e.preventDefault();
                    showSnackbar('Please fix conversion errors before submitting', 'error');
                    return false;
                }
                
                const baseUnitId = document.getElementById('base_unit_id').value;
                const retailPrice = document.getElementById('retail_price').value;
                const wholesalePrice = document.getElementById('wholesale_price').value;
                
                // Ensure base unit data is set
                const baseUnitIdHidden = document.getElementById('base-unit-id-hidden');
                const baseUnitRetailHidden = document.getElementById('base-unit-retail-price-hidden');
                const baseUnitWholesaleHidden = document.getElementById('base-unit-wholesale-price-hidden');
                
                if (baseUnitIdHidden) baseUnitIdHidden.value = baseUnitId;
                if (baseUnitRetailHidden) baseUnitRetailHidden.value = retailPrice || '';
                if (baseUnitWholesaleHidden) baseUnitWholesaleHidden.value = wholesalePrice || '';
                
                // Clean up and reindex selling unit rows
                const sellingUnitsContainer = document.getElementById('selling-units-container');
                if (sellingUnitsContainer) {
                    const sellingUnitRows = Array.from(sellingUnitsContainer.querySelectorAll('[id^="selling-unit-"]'));
                    let newUnitIndex = 1; // Start from 1 because 0 is base unit
                    
                    sellingUnitRows.forEach(row => {
                        const unitSelect = row.querySelector('select[name*="[unit_id]"]');
                        const unitIdInput = row.querySelector('input[name*="[id]"]');
                        const unitValue = unitSelect ? unitSelect.value.trim() : '';
                        
                        // Remove empty rows (no unit selected) or rows matching base unit
                        if (!unitValue || unitValue === baseUnitId) {
                            row.remove();
                        } else {
                            // Reindex the complete row
                            row.id = `selling-unit-${newUnitIndex}`;
                            if (unitSelect) {
                                unitSelect.name = `units[${newUnitIndex}][unit_id]`;
                            }
                            
                            // Update all other fields in the row
                            const retailPriceInput = row.querySelector('input[name*="[retail_price]"]');
                            const wholesalePriceInput = row.querySelector('input[name*="[wholesale_price]"]');
                            const sellingPriceInput = row.querySelector('input[name*="[selling_price]"]');
                            
                            if (retailPriceInput) {
                                retailPriceInput.name = `units[${newUnitIndex}][retail_price]`;
                            }
                            if (wholesalePriceInput) {
                                wholesalePriceInput.name = `units[${newUnitIndex}][wholesale_price]`;
                            }
                            if (sellingPriceInput) {
                                sellingPriceInput.name = `units[${newUnitIndex}][selling_price]`;
                            }
                            if (unitIdInput) {
                                unitIdInput.name = `units[${newUnitIndex}][id]`;
                            }
                            
                            // Update remove button onclick
                            const removeBtn = row.querySelector('button[onclick*="removeSellingUnit"]');
                            if (removeBtn) {
                                removeBtn.setAttribute('onclick', `removeSellingUnit(${newUnitIndex})`);
                            }
                            
                            newUnitIndex++;
                        }
                    });
                }
                
                // Clean up and reindex conversion rows
                const conversionsContainer = document.getElementById('conversions-container');
                if (conversionsContainer) {
                    const conversionRows = Array.from(conversionsContainer.querySelectorAll('[id^="conversion-"]'));
                    let newIndex = 0;
                    
                    conversionRows.forEach(row => {
                        const fromUnitHidden = row.querySelector('input[name*="[from_unit_id]"]');
                        const fromUnitSelect = row.querySelector('select[name*="[from_unit_id]"]');
                        const toUnitSelect = row.querySelector('select[name*="[to_unit_id]"]');
                        const factorInput = row.querySelector('input[name*="[factor]"]');
                        const conversionIdInput = row.querySelector('input[name*="[id]"]');
                        
                        const fromUnit = fromUnitHidden || fromUnitSelect;
                        const fromUnitValue = fromUnit ? fromUnit.value.trim() : '';
                        const toUnitValue = toUnitSelect ? toUnitSelect.value.trim() : '';
                        const factorValue = factorInput ? factorInput.value.trim() : '';
                        
                        // Check if row is completely empty or partially filled (remove if not complete)
                        const isEmpty = !fromUnitValue && !toUnitValue && !factorValue;
                        const isComplete = fromUnitValue && toUnitValue && factorValue;
                        
                        if (isEmpty || !isComplete) {
                            // Remove empty or incomplete rows
                            row.remove();
                        } else {
                            // Reindex the complete row
                            row.id = `conversion-${newIndex}`;
                            if (fromUnitHidden) {
                                fromUnitHidden.name = `conversions[${newIndex}][from_unit_id]`;
                            } else if (fromUnitSelect) {
                                fromUnitSelect.name = `conversions[${newIndex}][from_unit_id]`;
                            }
                            if (toUnitSelect) {
                                toUnitSelect.name = `conversions[${newIndex}][to_unit_id]`;
                            }
                            if (factorInput) {
                                factorInput.name = `conversions[${newIndex}][factor]`;
                            }
                            if (conversionIdInput) {
                                conversionIdInput.name = `conversions[${newIndex}][id]`;
                            }
                            
                            // Update remove button onclick
                            const removeBtn = row.querySelector('button[onclick*="removeConversion"]');
                            if (removeBtn) {
                                removeBtn.setAttribute('onclick', `removeConversion(${newIndex})`);
                            }
                            
                            newIndex++;
                        }
                    });
                }
            });
        }
    </script>
</x-app-layout>



