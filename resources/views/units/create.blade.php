<x-app-layout>
    <x-slot name="header">
        Create Unit
    </x-slot>

    <!-- Breadcrumb -->
    <div class="mb-4">
        <nav class="text-sm text-gray-600">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-900">Dashboard</a>
            <span class="mx-2">></span>
            <a href="{{ route('units.index') }}" class="hover:text-gray-900">Units</a>
            <span class="mx-2">></span>
            <span class="text-gray-900 font-medium">Create Unit</span>
        </nav>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <form method="POST" action="{{ route('units.store') }}">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Unit Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Unit Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="name" 
                           name="name" 
                           value="{{ old('name') }}" 
                           required
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
                           placeholder="e.g., Kilograms, Liters, Pieces">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Short Name -->
                <div>
                    <label for="short_name" class="block text-sm font-medium text-gray-700 mb-2">
                        Short Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="short_name" 
                           name="short_name" 
                           value="{{ old('short_name') }}" 
                           required
                           maxlength="10"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
                           placeholder="e.g., kg, L, pcs">
                    @error('short_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Status
                    </label>
                    <div class="flex items-center">
                        <input type="checkbox" 
                               id="is_active" 
                               name="is_active" 
                               value="1"
                               {{ old('is_active', true) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                        <label for="is_active" class="ml-2 text-sm text-gray-700">
                            Active
                        </label>
                    </div>
                </div>
            </div>

            <!-- Footer Buttons -->
            <div class="flex justify-end space-x-4 mt-6">
                <a href="{{ route('units.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2 rounded-md font-medium">
                    Cancel
                </a>
                <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white px-6 py-2 rounded-md font-medium">
                    Add Unit
                </button>
            </div>
        </form>
    </div>
</x-app-layout>













