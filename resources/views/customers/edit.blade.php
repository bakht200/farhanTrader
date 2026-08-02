<x-app-layout>
    <x-slot name="header">
        Edit Customer
    </x-slot>

    <!-- Breadcrumb -->
    <div class="mb-4">
        <nav class="text-sm text-gray-600">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-900">Dashboard</a>
            <span class="mx-2">></span>
            <a href="{{ route('customers.index') }}" class="hover:text-gray-900">Customer</a>
            <span class="mx-2">></span>
            <span class="text-gray-900 font-medium">Edit Customer</span>
        </nav>
    </div>

    <form method="POST" action="{{ route('customers.update', $customer) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold mb-6">Customer Information</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="name" name="name" value="{{ old('name', $customer->name) }}" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="customer_type" class="block text-sm font-medium text-gray-700 mb-2">Customer type</label>
                    <input type="text" id="customer_type" name="customer_type"
                           value="{{ old('customer_type', $customer->customer_type) }}" maxlength="100"
                           list="customer-type-suggestions"
                           placeholder="Pick a suggestion or type a new customer type"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                    <datalist id="customer-type-suggestions">
                        @foreach($customerTypeOptions as $opt)
                            <option value="{{ $opt }}"></option>
                        @endforeach
                    </datalist>
                    <p class="mt-1 text-xs text-gray-500">You can enter any new type; it is saved with the customer.</p>
                    @error('customer_type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="customer_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Customer ID
                    </label>
                    <input type="text" id="customer_id" name="customer_id" value="{{ old('customer_id', $customer->customer_id) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                    @error('customer_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $customer->email) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                    <input type="text" id="phone" name="phone" value="{{ old('phone', $customer->phone) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                </div>

                <div class="md:col-span-2">
                    <label for="address" class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                    <textarea id="address" name="address" rows="2"
                              class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">{{ old('address', $customer->address) }}</textarea>
                </div>

                <div>
                    <label for="city" class="block text-sm font-medium text-gray-700 mb-2">City</label>
                    <input type="text" id="city" name="city" value="{{ old('city', $customer->city) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                </div>

                <div>
                    <label for="state" class="block text-sm font-medium text-gray-700 mb-2">State</label>
                    <input type="text" id="state" name="state" value="{{ old('state', $customer->state) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                </div>

                <div>
                    <label for="country" class="block text-sm font-medium text-gray-700 mb-2">Country</label>
                    <input type="text" id="country" name="country" value="{{ old('country', $customer->country) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                </div>

                <div>
                    <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-2">Postal Code</label>
                    <input type="text" id="postal_code" name="postal_code" value="{{ old('postal_code', $customer->postal_code) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                </div>

                <div>
                    <label for="credit_limit" class="block text-sm font-medium text-gray-700 mb-2">Credit Limit</label>
                    <input type="number" id="credit_limit" name="credit_limit" value="{{ old('credit_limit', $customer->credit_limit ?? 0) }}" step="0.01" min="0"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                    @error('credit_limit')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="flex space-x-2">
            <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white px-6 py-2 rounded-md">Update Customer</button>
            <a href="{{ route('customers.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2 rounded-md">Cancel</a>
        </div>
    </form>
</x-app-layout>




