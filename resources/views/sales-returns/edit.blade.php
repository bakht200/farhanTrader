<x-app-layout>
    <x-slot name="header">Edit Sales Return</x-slot>
    
    <div class="mb-4">
        <nav class="text-sm text-gray-600">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-900">Dashboard</a>
            <span class="mx-2">></span>
            <a href="{{ route('sales.returns.index') }}" class="hover:text-gray-900">Sales Returns</a>
            <span class="mx-2">></span>
            <span class="text-gray-900 font-medium">Edit Return</span>
        </nav>
    </div>

    <div class="bg-white shadow-sm rounded-lg p-6">
        <form method="POST" action="{{ route('sales.returns.update', $return) }}">
            @csrf
            @method('PUT')
            
            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Return Number</label>
                    <input type="text" value="{{ $return->return_number ?? 'RET-' . str_pad($return->id, 6, '0', STR_PAD_LEFT) }}" disabled class="w-full px-4 py-2 border border-gray-300 rounded-md bg-gray-50">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                        <option value="completed" {{ ($return->status ?? 'completed') === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="pending" {{ ($return->status ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="cancelled" {{ ($return->status ?? '') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                    <textarea name="notes" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">{{ $return->notes ?? '' }}</textarea>
                </div>
                
                <div class="flex space-x-2">
                    <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-md">Update Return</button>
                    <a href="{{ route('sales.returns.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-md">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>




