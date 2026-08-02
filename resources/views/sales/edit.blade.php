<x-app-layout>
    <x-slot name="header">Edit Sale</x-slot>
    
    <div class="mb-4">
        <nav class="text-sm text-gray-600">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-900">Dashboard</a>
            <span class="mx-2">></span>
            <a href="{{ route('sales.index') }}" class="hover:text-gray-900">Sales</a>
            <span class="mx-2">></span>
            <span class="text-gray-900 font-medium">Edit Sale</span>
        </nav>
    </div>

    <div class="bg-white shadow-sm rounded-lg p-6">
        <form method="POST" action="{{ route('sales.update', $sale) }}">
            @csrf
            @method('PUT')
            
            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sale Number</label>
                    <input type="text" value="{{ $sale->sale_number }}" disabled class="w-full px-4 py-2 border border-gray-300 rounded-md bg-gray-50">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                        <option value="completed" {{ $sale->status === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="pending" {{ $sale->status === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="cancelled" {{ $sale->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Payment Status</label>
                    <select name="payment_status" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                        <option value="paid" {{ ($sale->payment_status ?? 'paid') === 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="pending" {{ ($sale->payment_status ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="partial" {{ ($sale->payment_status ?? '') === 'partial' ? 'selected' : '' }}>Partial</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                    <textarea name="notes" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">{{ $sale->notes }}</textarea>
                </div>
                
                <div class="flex space-x-2">
                    <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-md">Update Sale</button>
                    <a href="{{ route('sales.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-md">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>




