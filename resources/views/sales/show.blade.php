<x-app-layout>
    <x-slot name="header">Sale Details</x-slot>
    
    <div class="mb-4">
        <nav class="text-sm text-gray-600">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-900">Dashboard</a>
            <span class="mx-2">></span>
            <a href="{{ route('sales.index') }}" class="hover:text-gray-900">Sales</a>
            <span class="mx-2">></span>
            <span class="text-gray-900 font-medium">{{ $sale->sale_number }}</span>
        </nav>
    </div>

    <div class="bg-white shadow-sm rounded-lg p-6">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ $sale->sale_number }}</h2>
                <p class="text-sm text-gray-500">Date: {{ $sale->sale_date->format('Y-m-d') }}</p>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('sales.edit', $sale) }}" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-md">Edit</a>
                <a href="{{ route('sales.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-md">Back</a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <h3 class="text-lg font-semibold mb-4">Sale Information</h3>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Sale Number</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $sale->sale_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Customer</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $sale->customer->name ?? 'Walk-in' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Sale Date</dt>
                        <dd class="mt-1 text-gray-900">{{ $sale->sale_date->format('Y-m-d') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                {{ ucfirst($sale->status) }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Payment Status</dt>
                        <dd class="mt-1">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                {{ ucfirst($sale->payment_status ?? 'Paid') }}
                            </span>
                        </dd>
                    </div>
                </dl>
            </div>
            <div>
                <h3 class="text-lg font-semibold mb-4">Financial Summary</h3>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Subtotal</dt>
                        <dd class="mt-1 text-sm text-gray-900">PKR {{ number_format($sale->subtotal ?? 0, 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Tax Amount</dt>
                        <dd class="mt-1 text-sm text-gray-900">PKR {{ number_format($sale->tax_amount ?? 0, 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Discount</dt>
                        <dd class="mt-1 text-sm text-gray-900">PKR {{ number_format($sale->discount_amount ?? 0, 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Total Amount</dt>
                        <dd class="mt-1 text-lg font-bold text-gray-900">PKR {{ number_format($sale->total_amount ?? 0, 2) }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        @if($sale->items->count() > 0)
        <div class="mt-6">
            <h3 class="text-lg font-semibold mb-4">Sale Items</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Unit Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Discount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($sale->items as $item)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $item->product_name ?? $item->product?->name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ number_format($item->quantity, 2) }} 
                                {{ $item->unit ? $item->unit->short_name : ($item->product && $item->product->unit ? $item->product->unit->short_name : 'Pcs') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">PKR {{ number_format($item->unit_price ?? 0, 2) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">PKR {{ number_format($item->discount ?? 0, 2) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">PKR {{ number_format($item->total ?? 0, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</x-app-layout>



