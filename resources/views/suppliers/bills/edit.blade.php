<x-app-layout>
    <x-slot name="header">
        Edit Bill
    </x-slot>

    <!-- Breadcrumb -->
    <div class="mb-4">
        <nav class="text-sm text-gray-600">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-900">Dashboard</a>
            <span class="mx-2">></span>
            <a href="{{ route('suppliers.index') }}" class="hover:text-gray-900">Suppliers</a>
            <span class="mx-2">></span>
            <a href="{{ route('suppliers.show', $supplier) }}" class="hover:text-gray-900">{{ $supplier->name }}</a>
            <span class="mx-2">></span>
            <span class="text-gray-900 font-medium">Edit Bill</span>
        </nav>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <!-- Supplier wallet summary (row) -->
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <h3 class="text-lg font-semibold text-gray-900">{{ $supplier->name }}</h3>
            <div class="text-sm text-gray-600">
                Current bill
                <span class="font-semibold text-gray-900">#{{ $bill->bill_number ?? $bill->id }}</span>
                · PKR {{ number_format($bill->bill_amount, 2) }}
            </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="rounded-lg border border-gray-100 bg-gray-50 px-4 py-3">
                <p class="text-sm text-gray-500 mb-1">Total Paid</p>
                <p class="text-2xl font-bold text-green-600">PKR {{ number_format($debitTotal ?? 0, 2) }}</p>
            </div>
            <div class="rounded-lg border border-gray-100 bg-gray-50 px-4 py-3">
                <p class="text-sm text-gray-500 mb-1">Total</p>
                <p class="text-2xl font-bold text-gray-700">PKR {{ number_format($creditTotal ?? 0, 2) }}</p>
            </div>
            <div class="rounded-lg border border-gray-100 bg-gray-50 px-4 py-3">
                <p class="text-sm text-gray-500 mb-1">Remaining</p>
                <p class="text-2xl font-bold {{ ($balance ?? 0) > 0 ? 'text-red-600' : 'text-green-600' }}">
                    PKR {{ number_format($balance ?? 0, 2) }}
                </p>
            </div>
        </div>
    </div>

    <!-- Bill Edit Form -->
    <div>
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold mb-6">Edit Bill</h3>

                <form method="POST" action="{{ route('suppliers.bills.update', [$supplier, $bill]) }}" enctype="multipart/form-data" class="space-y-6" onsubmit="validateBillAmount()">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="bill_number" class="block text-sm font-medium text-gray-700 mb-2">
                                Bill Number
                            </label>
                            <input type="text" id="bill_number" name="bill_number" value="{{ old('bill_number', $bill->bill_number) }}" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500" 
                                   placeholder="Bill/Invoice number">
                            @error('bill_number')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="bill_date" class="block text-sm font-medium text-gray-700 mb-2">
                                Bill Date <span class="text-red-500">*</span>
                            </label>
                            <input type="date" id="bill_date" name="bill_date" required value="{{ old('bill_date', $bill->bill_date->format('Y-m-d')) }}" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                            @error('bill_date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="bill_amount" class="block text-sm font-medium text-gray-700 mb-2">
                                Bill Amount <span class="text-red-500">*</span>
                                <span class="text-xs text-gray-500" id="amount-hint">(Auto-calculated from products)</span>
                            </label>
                            <input type="number" id="bill_amount" name="bill_amount" step="0.01" min="0.01" required value="{{ old('bill_amount', $bill->bill_amount) }}" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500" 
                                   placeholder="0.00"
                                   onchange="updateCalculatedAmount()">
                            @error('bill_amount')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="reference_number" class="block text-sm font-medium text-gray-700 mb-2">
                                Reference Number
                            </label>
                            <input type="text" id="reference_number" name="reference_number" value="{{ old('reference_number', $bill->reference_number) }}" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500" 
                                   placeholder="Invoice/Receipt/Check number">
                            @error('reference_number')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label for="bill_image" class="block text-sm font-medium text-gray-700 mb-2">
                                Bill Hardcopy (Image)
                            </label>
                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center">
                                @if($bill->bill_image)
                                    <div id="current-image-div" class="mb-3">
                                        <img src="{{ asset('storage/' . $bill->bill_image) }}" alt="Current Bill Image" id="current-bill-image" class="mx-auto max-h-48 max-w-full object-contain rounded-lg border border-gray-300">
                                        <p class="mt-1 text-xs text-gray-500">Current image</p>
                                        <button type="button" onclick="removeCurrentImage()" class="mt-2 text-sm text-red-600 hover:text-red-800">
                                            Remove Current Image
                                        </button>
                                    </div>
                                @endif
                                <input type="file" 
                                       id="bill_image" 
                                       name="bill_image" 
                                       accept="image/*"
                                       class="hidden"
                                       onchange="previewBillImage(this)">
                                <label for="bill_image" class="cursor-pointer {{ $bill->bill_image ? 'hidden' : '' }}" id="upload-label">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-600">Click to {{ $bill->bill_image ? 'change' : 'upload' }} bill image</p>
                                    <p class="mt-1 text-xs text-gray-500">PNG, JPG, GIF up to 10MB</p>
                                </label>
                                <div id="bill-image-preview" class="mt-4 hidden">
                                    <img id="bill-preview" src="" alt="Bill Preview" class="mx-auto max-h-48 max-w-full object-contain rounded-lg">
                                    <button type="button" onclick="removeBillImage()" class="mt-2 text-sm text-red-600 hover:text-red-800">
                                        Remove New Image
                                    </button>
                                </div>
                                <input type="hidden" id="remove_image" name="remove_image" value="0">
                            </div>
                            @error('bill_image')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                                Description
                            </label>
                            <textarea id="description" name="description" rows="4" 
                                      class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500" 
                                      placeholder="Enter bill description">{{ old('description', $bill->description) }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Products Section -->
                    <div class="mt-4">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="flex justify-between items-center mb-4">
                                <h4 class="text-md font-semibold text-gray-900">Products</h4>
                                <button type="button" onclick="addProductRow()" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-md text-sm font-medium">
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    Add Product
                                </button>
                            </div>
                            
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 bg-white rounded-lg">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">Product Name</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">Quantity</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">Unit Price</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">Discount %</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">Tax</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">Total</th>
                                            <th class="px-3 py-2 text-center text-xs font-medium text-gray-700 uppercase">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="productsTableBody" class="bg-white divide-y divide-gray-200">
                                        <!-- Product rows will be added here dynamically -->
                                    </tbody>
                                    <tfoot>
                                        <tr class="bg-gray-50">
                                            <td colspan="6" class="px-3 py-3 text-right text-sm font-semibold text-gray-700">Grand Total:</td>
                                            <td id="grandTotal" class="px-3 py-3 text-sm font-bold text-gray-900">PKR 0.00</td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            
                            <!-- Auto-calculate amount from products -->
                            <input type="hidden" id="calculated_amount" name="calculated_amount" value="0">
                        </div>
                    </div>

                    <div class="flex justify-end space-x-4 pt-4 border-t">
                        <a href="{{ route('suppliers.show', $supplier) }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2 rounded-md font-medium">
                            Cancel
                        </a>
                        <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white px-6 py-2 rounded-md font-medium">
                            Update Bill
                        </button>
                    </div>
                </form>
            </div>
    </div>

    <!-- Products Data for JavaScript -->
    <script>
        @php
            $existingBillItems = $bill->items->map(function($item) {
                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'product_sku' => $item->product_sku,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'discount' => $item->discount,
                    'tax' => $item->tax,
                    'total' => $item->total,
                ];
            })->toArray();
        @endphp
        const productsData = @json($productsData ?? []);
        const categoriesData = @json(isset($categories) ? $categories->map(function($c) {
            return ['id' => $c->id, 'name' => $c->name];
        })->values() : []);
        const unitsData = @json(isset($units) ? $units->map(function($u) {
            return ['id' => $u->id, 'name' => $u->name, 'short_name' => $u->short_name];
        })->values() : []);
        const existingBillItems = @json($existingBillItems ?? []);
        
        let productRowIndex = 0;

        function ensureSharedProductsDatalist() {
            if (document.getElementById('supplier-products-list')) {
                return;
            }
            const dl = document.createElement('datalist');
            dl.id = 'supplier-products-list';
            dl.innerHTML = productsData.map(p => {
                const displayName = p.sku ? `${p.name} (${p.sku})` : p.name;
                return `<option value="${p.name}" label="${displayName}" data-id="${p.id}" data-sku="${p.sku || ''}" data-price="${p.purchase_price || 0}"></option>`;
            }).join('');
            document.body.appendChild(dl);
        }
        
        // SKU generation function
        function generateSku(productName = '') {
            let prefix = 'PRD';
            if (productName && productName.trim()) {
                const words = productName.trim().toUpperCase().split(/\s+/);
                let initials = '';
                words.forEach(word => {
                    if (word.length > 0) {
                        initials += word.charAt(0);
                    }
                });
                if (initials.length >= 2) {
                    prefix = initials.substring(0, 3);
                }
            }

            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const datePart = year + month + day;
            const randomPart = String(Math.floor(Math.random() * 10000)).padStart(4, '0');
            return prefix + '-' + datePart + '-' + randomPart;
        }
        
        function addProductRow(item = null) {
            ensureSharedProductsDatalist();
            const tbody = document.getElementById('productsTableBody');
            const row = document.createElement('tr');
            row.className = 'product-row';
            row.dataset.index = productRowIndex;
            
            const product = item && item.product_id ? productsData.find(p => p.id == item.product_id) : null;
            
            row.innerHTML = `
                <td class="px-3 py-2">
                    <div class="relative">
                        <input type="text" 
                               name="products[${productRowIndex}][product_name]" 
                               class="product-name-input w-full px-3 py-1 border border-gray-300 rounded text-sm" 
                               value="${item ? item.product_name : (product ? product.name : '')}" 
                               placeholder="Type or search product"
                               required
                               autocomplete="off"
                               list="supplier-products-list"
                               onchange="handleProductNameChange(this, ${productRowIndex})"
                               oninput="handleProductNameInput(this, ${productRowIndex}, false)">
                        <input type="hidden" name="products[${productRowIndex}][product_id]" class="product-id-input" value="${item ? (item.product_id || '') : (product ? product.id : '')}">
                        <input type="hidden" name="products[${productRowIndex}][category_id]" class="category-id-input" value="">
                        <input type="hidden" name="products[${productRowIndex}][product_sku]" class="product-sku-input" value="${item ? (item.product_sku || '') : (product ? product.sku : '')}">
                        <input type="hidden" name="products[${productRowIndex}][unit_id]" class="unit-id-input" value="${product ? product.unit_id : ''}">
                    </div>
                </td>
                <td class="px-3 py-2">
                    <input type="number" 
                           name="products[${productRowIndex}][quantity]" 
                           class="quantity-input w-full px-3 py-1 border border-gray-300 rounded text-sm" 
                           value="${item ? item.quantity : '1'}" 
                           min="0.01" 
                           step="0.01"
                           required
                           onchange="calculateRowTotal(${productRowIndex})">
                </td>
                <td class="px-3 py-2">
                    <input type="number" 
                           name="products[${productRowIndex}][unit_price]" 
                           class="unit-price-input w-full px-3 py-1 border border-gray-300 rounded text-sm" 
                           value="${item ? item.unit_price : (product ? product.purchase_price : '0.00')}" 
                           min="0" 
                           step="0.01"
                           required
                           onchange="calculateRowTotal(${productRowIndex})">
                </td>
                <td class="px-3 py-2">
                    <input type="number" 
                           name="products[${productRowIndex}][discount]" 
                           class="discount-input w-full px-3 py-1 border border-gray-300 rounded text-sm" 
                           value="${item ? item.discount : '0'}" 
                           min="0" 
                           max="100"
                           step="0.01"
                           onchange="calculateRowTotal(${productRowIndex})">
                </td>
                <td class="px-3 py-2">
                    <input type="number" 
                           name="products[${productRowIndex}][tax]" 
                           class="tax-input w-full px-3 py-1 border border-gray-300 rounded text-sm" 
                           value="${item ? item.tax : '0'}" 
                           min="0" 
                           step="0.01"
                           onchange="calculateRowTotal(${productRowIndex})">
                </td>
                <td class="px-3 py-2">
                    <input type="number" 
                           name="products[${productRowIndex}][total]" 
                           class="total-input w-full px-3 py-1 border border-gray-300 rounded text-sm font-semibold" 
                           value="${item ? item.total : '0.00'}" 
                           readonly>
                </td>
                <td class="px-3 py-2 text-center">
                    <button type="button" onclick="removeProductRow(this)" class="text-red-600 hover:text-red-800">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </td>
            `;
            
            tbody.appendChild(row);
            productRowIndex++;
            
            calculateRowTotal(parseInt(row.dataset.index));
            updateGrandTotal();
        }
        
        function handleProductNameChange(input, index) {
            handleProductNameInput(input, index, true);
        }
        
        function handleProductNameInput(input, index, fromChange = false) {
            const value = input.value.trim();
            if (!value) return;
            
            const selectedOption = [...document.querySelectorAll('#supplier-products-list option')].find(o => o.value === value);
            const row = input.closest('tr');
            
            if (selectedOption && fromChange) {
                const productId = selectedOption.getAttribute('data-id');
                const sku = selectedOption.getAttribute('data-sku');
                const price = selectedOption.getAttribute('data-price');
                
                row.querySelector('.product-id-input').value = productId || '';
                // SKU is stored in hidden field, auto-generated by backend
                const skuInput = row.querySelector('.product-sku-input');
                if (skuInput && !skuInput.value) {
                    skuInput.value = sku || '';
                }
                if (price) {
                    row.querySelector('.unit-price-input').value = parseFloat(price).toFixed(2);
                }
                calculateRowTotal(index);
            }
        }
        
        function calculateRowTotal(index) {
            const row = document.querySelector(`tr[data-index="${index}"]`);
            if (!row) return;
            
            const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
            const unitPrice = parseFloat(row.querySelector('.unit-price-input').value) || 0;
            const discount = parseFloat(row.querySelector('.discount-input').value) || 0;
            const tax = parseFloat(row.querySelector('.tax-input').value) || 0;
            
            const subtotal = quantity * unitPrice;
            const discountAmount = subtotal * (discount / 100);
            const afterDiscount = subtotal - discountAmount;
            const taxAmount = afterDiscount * (tax / 100);
            const total = afterDiscount + taxAmount;
            
            row.querySelector('.total-input').value = total.toFixed(2);
            updateGrandTotal();
        }
        
        function updateGrandTotal() {
            const totals = Array.from(document.querySelectorAll('.total-input')).map(input => 
                parseFloat(input.value) || 0
            );
            const grandTotal = totals.reduce((sum, val) => sum + val, 0);
            
            document.getElementById('grandTotal').textContent = 'PKR ' + grandTotal.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            
            document.getElementById('calculated_amount').value = grandTotal.toFixed(2);
            
            const amountInput = document.getElementById('bill_amount');
            const amountHint = document.getElementById('amount-hint');
            
            if (totals.length > 0 && grandTotal > 0) {
                amountInput.value = grandTotal.toFixed(2);
                amountInput.readOnly = true;
                amountInput.classList.add('bg-gray-100', 'cursor-not-allowed');
                if (amountHint) {
                    amountHint.textContent = '(Auto-calculated from products - cannot be edited)';
                    amountHint.classList.add('text-orange-600', 'font-medium');
                }
            } else {
                amountInput.readOnly = false;
                amountInput.classList.remove('bg-gray-100', 'cursor-not-allowed');
                if (amountHint) {
                    amountHint.textContent = '(Auto-calculated from products)';
                    amountHint.classList.remove('text-orange-600', 'font-medium');
                }
            }
        }
        
        function updateCalculatedAmount() {
            const amountInput = document.getElementById('bill_amount');
            const productRows = document.querySelectorAll('.product-row').length;
            
            if (productRows > 0) {
                updateGrandTotal();
                amountInput.readOnly = true;
                amountInput.classList.add('bg-gray-100', 'cursor-not-allowed');
            }
        }
        
        function removeProductRow(button) {
            const row = button.closest('tr');
            row.remove();
            updateGrandTotal();
            const productRows = document.querySelectorAll('.product-row').length;
            if (productRows === 0) {
                const amountInput = document.getElementById('bill_amount');
                amountInput.readOnly = false;
                amountInput.classList.remove('bg-gray-100', 'cursor-not-allowed');
                const amountHint = document.getElementById('amount-hint');
                if (amountHint) {
                    amountHint.textContent = '(Auto-calculated from products)';
                    amountHint.classList.remove('text-orange-600', 'font-medium');
                }
            }
        }
        
        function previewBillImage(input) {
            const preview = document.getElementById('bill-preview');
            const previewDiv = document.getElementById('bill-image-preview');
            const currentImageDiv = document.getElementById('current-image-div');
            const uploadLabel = document.getElementById('upload-label');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    previewDiv.classList.remove('hidden');
                    if (currentImageDiv) {
                        currentImageDiv.classList.add('hidden');
                    }
                    if (uploadLabel) {
                        uploadLabel.classList.add('hidden');
                    }
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        function removeBillImage() {
            const input = document.getElementById('bill_image');
            const previewDiv = document.getElementById('bill-image-preview');
            const currentImageDiv = document.getElementById('current-image-div');
            const uploadLabel = document.getElementById('upload-label');
            
            input.value = '';
            previewDiv.classList.add('hidden');
            
            if (currentImageDiv && uploadLabel) {
                currentImageDiv.classList.remove('hidden');
                uploadLabel.classList.remove('hidden');
            }
        }
        
        function removeCurrentImage() {
            const currentImageDiv = document.getElementById('current-image-div');
            const uploadLabel = document.getElementById('upload-label');
            const removeImageInput = document.getElementById('remove_image');
            
            if (removeImageInput) {
                removeImageInput.value = '1';
            }
            
            if (currentImageDiv) {
                currentImageDiv.classList.add('hidden');
            }
            if (uploadLabel) {
                uploadLabel.classList.remove('hidden');
            }
        }
        
        function validateBillAmount() {
            const productRows = document.querySelectorAll('.product-row').length;
            if (productRows > 0) {
                const grandTotal = parseFloat(document.getElementById('calculated_amount').value) || 0;
                const amountValue = parseFloat(document.getElementById('bill_amount').value) || 0;
                if (Math.abs(grandTotal - amountValue) > 0.01) {
                    document.getElementById('bill_amount').value = grandTotal.toFixed(2);
                }
            }
            return true;
        }

        // Initialize on page load - load existing bill items
        document.addEventListener('DOMContentLoaded', function() {
            ensureSharedProductsDatalist();
            if (existingBillItems && existingBillItems.length > 0) {
                existingBillItems.forEach(item => {
                    addProductRow(item);
                });
            }
            updateGrandTotal();
        });
    </script>
</x-app-layout>

