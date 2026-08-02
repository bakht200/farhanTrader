<x-app-layout>
    <x-slot name="header">
        Product Details
    </x-slot>

    <!-- Breadcrumb -->
    <div class="mb-4">
        <nav class="text-sm text-gray-600">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-900">Dashboard</a>
            <span class="mx-2">></span>
            <a href="{{ route('products.index') }}" class="hover:text-gray-900">Products</a>
            <span class="mx-2">></span>
            <span class="text-gray-900 font-medium">{{ $product->name }}</span>
        </nav>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex justify-between items-start mb-6">
            <div class="flex items-center">
                <div class="w-20 h-20 bg-gray-200 rounded-lg flex items-center justify-center mr-4">
                    @if($product->image)
                        <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="w-full h-full object-cover rounded-lg">
                    @else
                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    @endif
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">{{ $product->name }}</h2>
                    <p class="text-sm text-gray-500">SKU: {{ $product->sku ?? 'N/A' }}</p>
                </div>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('products.edit', $product) }}" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-md">
                    Edit Product
                </a>
                <a href="{{ route('products.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-md">
                    Back to List
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h3 class="text-lg font-semibold mb-4">Product Information</h3>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Name</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $product->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">SKU</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $product->sku ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Category</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $product->category->name ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Unit</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $product->unit->name ?? 'N/A' }} ({{ $product->unit->short_name ?? 'N/A' }})</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Description</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $product->description ?? 'No description' }}</dd>
                    </div>
                </dl>
            </div>

            <div>
                <h3 class="text-lg font-semibold mb-4">Pricing & Inventory</h3>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Purchase Price</dt>
                        <dd class="mt-1 text-sm text-gray-900">PKR {{ number_format($product->purchase_price, 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Selling Price</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-900">PKR {{ number_format($product->selling_price, 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Stock Quantity</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ number_format($product->stock_quantity) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Low Stock Threshold</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ number_format($product->low_stock_threshold) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1">
                            @if($product->is_active)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    Inactive
                                </span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Created By</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $product->createdBy->name ?? 'System' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Created At</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $product->created_at->format('Y-m-d h:i A') }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</x-app-layout>











