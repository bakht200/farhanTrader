<x-app-layout>
    <x-slot name="header">Create Sale</x-slot>
    
    <div class="mb-4">
        <nav class="text-sm text-gray-600">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-900">Dashboard</a>
            <span class="mx-2">></span>
            <a href="{{ route('sales.index') }}" class="hover:text-gray-900">Sales</a>
            <span class="mx-2">></span>
            <span class="text-gray-900 font-medium">Create Sale</span>
        </nav>
    </div>

    <div class="bg-white shadow-sm rounded-lg p-6">
        <p class="text-gray-600">Use the POS system to create sales. <a href="{{ route('sales.pos.index') }}" class="text-orange-600 hover:text-orange-700 underline">Go to POS</a></p>
    </div>
</x-app-layout>




