<x-app-layout>
    <x-slot name="header">
        All Branch Stock
    </x-slot>

    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Inventory by branch</h3>
            <p class="text-sm text-gray-600">Admin overview of product membership and quantities. Mutations still use the selected branch.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Branch</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($stocks as $row)
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-900">{{ $row->branch->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-900">
                                @if($row->product)
                                    <a href="{{ route('products.show', $row->product) }}" class="text-orange-600 hover:text-orange-800">{{ $row->product->getAttributes()['name'] ?? $row->product->name }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $row->product->sku ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm {{ (float) $row->stock_quantity <= 0 ? 'text-red-600 font-semibold' : 'text-gray-900' }}">
                                {{ number_format((float) $row->stock_quantity, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-gray-500">No branch stock rows.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4">
            {{ $stocks->links() }}
        </div>
    </div>
</x-app-layout>
