<x-app-layout>
    <x-slot name="header">Create Invoice</x-slot>
    
    <div class="mb-4">
        <nav class="text-sm text-gray-600">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-900">Dashboard</a>
            <span class="mx-2">></span>
            <a href="{{ route('sales.invoices.index') }}" class="hover:text-gray-900">Invoices</a>
            <span class="mx-2">></span>
            <span class="text-gray-900 font-medium">Create Invoice</span>
        </nav>
    </div>

    <div class="bg-white shadow-sm rounded-lg p-6">
        <p class="text-gray-600">Invoice creation form will be implemented here.</p>
    </div>
</x-app-layout>




