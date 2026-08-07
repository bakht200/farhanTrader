<x-app-layout>
    <x-slot name="header">
        Create Supplier
    </x-slot>

    <!-- Breadcrumb -->
    <div class="mb-4">
        <nav class="text-sm text-gray-600">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-900">Dashboard</a>
            <span class="mx-2">></span>
            <a href="{{ route('suppliers.index') }}" class="hover:text-gray-900">Suppliers</a>
            <span class="mx-2">></span>
            <span class="text-gray-900 font-medium">Create Supplier</span>
        </nav>
    </div>

    <form method="POST" action="{{ route('suppliers.store') }}" class="space-y-6">
        @csrf

        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold mb-6">Supplier Information</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="supplier_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Supplier ID
                    </label>
                    <input type="text" id="supplier_id" name="supplier_id" value="{{ old('supplier_id') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
                           placeholder="Auto-generated if left empty">
                    @error('supplier_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="company_name" class="block text-sm font-medium text-gray-700 mb-2">Company Name</label>
                    <input type="text" id="company_name" name="company_name" value="{{ old('company_name') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                    <input type="text" id="phone" name="phone" value="{{ old('phone') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                </div>

                <div>
                    <label for="tax_id" class="block text-sm font-medium text-gray-700 mb-2">Tax ID</label>
                    <input type="text" id="tax_id" name="tax_id" value="{{ old('tax_id') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                </div>

                <div class="md:col-span-2">
                    <label for="address" class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                    <textarea id="address" name="address" rows="2"
                              class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">{{ old('address') }}</textarea>
                </div>

                <div>
                    <label for="city" class="block text-sm font-medium text-gray-700 mb-2">City</label>
                    <input type="text" id="city" name="city" value="{{ old('city') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                </div>

                <div>
                    <label for="state" class="block text-sm font-medium text-gray-700 mb-2">State</label>
                    <input type="text" id="state" name="state" value="{{ old('state') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                </div>

                <div>
                    <label for="country" class="block text-sm font-medium text-gray-700 mb-2">Country</label>
                    <input type="text" id="country" name="country" value="{{ old('country') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                </div>

                <div>
                    <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-2">Postal Code</label>
                    <input type="text" id="postal_code" name="postal_code" value="{{ old('postal_code') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                </div>
            </div>
        </div>

        <div class="flex space-x-2">
            <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white px-6 py-2 rounded-md">Create Supplier</button>
            <a href="{{ route('suppliers.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2 rounded-md">Cancel</a>
        </div>
    </form>

    <script>
        (function () {
            const form = document.querySelector('form[action*="suppliers"]');
            if (!form) return;

            form.addEventListener('submit', async function (event) {
                const offline = window.FTOffline && window.FTOffline.isOnline && !window.FTOffline.isOnline();
                if (!offline || !window.FTOffline.queueOfflineSupplier) {
                    return;
                }

                event.preventDefault();

                const fd = new FormData(form);
                const payload = {
                    name: (fd.get('name') || '').toString().trim(),
                    supplier_id: (fd.get('supplier_id') || '').toString().trim() || null,
                    company_name: (fd.get('company_name') || '').toString().trim() || null,
                    email: (fd.get('email') || '').toString().trim() || null,
                    phone: (fd.get('phone') || '').toString().trim() || null,
                    address: (fd.get('address') || '').toString().trim() || null,
                    city: (fd.get('city') || '').toString().trim() || null,
                    state: (fd.get('state') || '').toString().trim() || null,
                    country: (fd.get('country') || '').toString().trim() || null,
                    postal_code: (fd.get('postal_code') || '').toString().trim() || null,
                    tax_id: (fd.get('tax_id') || '').toString().trim() || null,
                };

                if (!payload.name) {
                    alert('Name is required.');
                    return;
                }

                try {
                    await window.FTOffline.queueOfflineSupplier(payload);
                    alert('Supplier saved offline. It will sync when you are back online.');
                    window.location.href = '{{ route('suppliers.index') }}';
                } catch (e) {
                    alert(e.message || 'Failed to save supplier offline.');
                }
            });
        })();
    </script>
</x-app-layout>




