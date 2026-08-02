<x-app-layout>
    <x-slot name="header">Create Order</x-slot>
    
    <div class="mb-4">
        <nav class="text-sm text-gray-600">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-900">Dashboard</a>
            <span class="mx-2">></span>
            <a href="{{ route('orders.index') }}" class="hover:text-gray-900">Orders</a>
            <span class="mx-2">></span>
            <span class="text-gray-900 font-medium">Create Order</span>
        </nav>
    </div>

    <div class="bg-white shadow-sm rounded-lg p-6">
        <p class="text-gray-600">Order creation form will be implemented here.</p>
    </div>
</x-app-layout>




