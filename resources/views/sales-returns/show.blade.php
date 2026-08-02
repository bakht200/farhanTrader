<x-app-layout>
    <x-slot name="header">Sales Return Details</x-slot>
    
    <div class="mb-4">
        <nav class="text-sm text-gray-600">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-900">Dashboard</a>
            <span class="mx-2">></span>
            <a href="{{ route('sales.returns.index') }}" class="hover:text-gray-900">Sales Returns</a>
            <span class="mx-2">></span>
            <span class="text-gray-900 font-medium">{{ $return->return_number ?? 'RET-' . str_pad($return->id, 6, '0', STR_PAD_LEFT) }}</span>
        </nav>
    </div>

    <div class="bg-white shadow-sm rounded-lg p-6">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ $return->return_number ?? 'RET-' . str_pad($return->id, 6, '0', STR_PAD_LEFT) }}</h2>
                <p class="text-sm text-gray-500">Date: {{ $return->return_date ? $return->return_date->format('Y-m-d') : 'N/A' }}</p>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('sales.returns.edit', $return) }}" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-md">Edit</a>
                <a href="{{ route('sales.returns.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-md">Back</a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h3 class="text-lg font-semibold mb-4">Return Information</h3>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Return Number</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $return->return_number ?? 'RET-' . str_pad($return->id, 6, '0', STR_PAD_LEFT) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Customer</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $return->customer->name ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Return Date</dt>
                        <dd class="mt-1 text-gray-900">{{ $return->return_date ? $return->return_date->format('Y-m-d') : 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                {{ ucfirst($return->status ?? 'completed') }}
                            </span>
                        </dd>
                    </div>
                </dl>
            </div>
            <div>
                <h3 class="text-lg font-semibold mb-4">Financial Summary</h3>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Total Amount</dt>
                        <dd class="mt-1 text-lg font-bold text-gray-900">PKR {{ number_format($return->total_amount ?? 0, 2) }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</x-app-layout>




