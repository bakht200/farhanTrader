<x-app-layout>
    <x-slot name="header">
        Add Transaction
    </x-slot>

    <div class="w-full max-w-none min-w-0 -mx-6 px-6 space-y-6">
    <!-- Breadcrumb -->
    <nav class="flex flex-wrap items-center gap-2 text-sm text-gray-500" aria-label="Breadcrumb">
        <a href="{{ route('dashboard') }}" class="hover:text-orange-600 transition-colors">Dashboard</a>
        <svg class="w-4 h-4 text-gray-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('suppliers.index') }}" class="hover:text-orange-600 transition-colors">Suppliers</a>
        <svg class="w-4 h-4 text-gray-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('suppliers.show', $supplier) }}" class="hover:text-orange-600 transition-colors truncate max-w-[min(100%,16rem)] sm:max-w-md md:max-w-none" title="{{ $supplier->name }}">{{ $supplier->name }}</a>
        <svg class="w-4 h-4 text-gray-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-900 font-medium">Add Transaction</span>
    </nav>

    @if(session('success'))
        <div class="flex gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-900 shadow-sm">
            <svg class="w-5 h-5 shrink-0 text-emerald-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p class="text-sm font-medium leading-relaxed">{{ session('success') }}</p>
        </div>
    @endif

    <div class="flex flex-col gap-6 lg:gap-8 w-full min-w-0">
        <!-- Wallet summary — full width, compact row on large screens -->
        <div class="w-full min-w-0 shrink-0">
            <div class="rounded-2xl border border-gray-200/80 bg-white shadow-sm overflow-hidden">
                <div class="flex flex-col lg:flex-row lg:items-stretch min-w-0">
                    <div class="bg-gradient-to-br from-orange-500 via-orange-500 to-amber-600 px-5 py-4 lg:py-5 lg:w-72 xl:w-80 shrink-0 lg:flex lg:flex-col lg:justify-center">
                        <p class="text-xs font-medium uppercase tracking-wider text-white/80">Supplier</p>
                        <h2 class="mt-1 text-lg font-semibold text-white leading-snug break-words">{{ $supplier->name }}</h2>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 p-4 sm:p-5 flex-1 min-w-0">
                        <div class="rounded-xl bg-emerald-50/80 border border-emerald-100/80 px-4 py-3 min-w-0">
                            <p class="text-xs font-medium text-emerald-700/90 uppercase tracking-wide">Total paid</p>
                            <p class="mt-1 text-lg sm:text-xl font-bold tabular-nums text-emerald-800 truncate" title="PKR {{ number_format($debitTotal ?? 0, 2) }}">PKR {{ number_format($debitTotal ?? 0, 2) }}</p>
                        </div>
                        <div class="rounded-xl bg-slate-50 border border-slate-100 px-4 py-3 min-w-0">
                            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total owed</p>
                            <p class="mt-1 text-lg sm:text-xl font-bold tabular-nums text-slate-800 truncate" title="PKR {{ number_format($creditTotal ?? 0, 2) }}">PKR {{ number_format($creditTotal ?? 0, 2) }}</p>
                        </div>
                        <div class="rounded-xl border px-4 py-3 min-w-0 {{ ($balance ?? 0) > 0 ? 'bg-red-50/90 border-red-100' : 'bg-emerald-50/80 border-emerald-100' }}">
                            <p class="text-xs font-medium uppercase tracking-wide {{ ($balance ?? 0) > 0 ? 'text-red-700/90' : 'text-emerald-700/90' }}">Remaining</p>
                            <p class="mt-1 text-lg sm:text-xl font-bold tabular-nums truncate {{ ($balance ?? 0) > 0 ? 'text-red-800' : 'text-emerald-800' }}" title="PKR {{ number_format($balance ?? 0, 2) }}">
                                PKR {{ number_format($balance ?? 0, 2) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transaction form — full width -->
        <div class="w-full min-w-0 flex-1">
            <div class="rounded-2xl border border-gray-200/80 bg-white shadow-sm overflow-hidden w-full min-w-0">
                <div class="border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white px-5 sm:px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">New transaction</h3>
                    <p class="mt-0.5 text-sm text-gray-500">Record a credit (purchase owed) or debit (payment) for this supplier.</p>
                </div>
                <div class="p-5 sm:p-6 lg:p-8 w-full min-w-0">
                <form method="POST" action="{{ route('suppliers.transactions.store', $supplier) }}" enctype="multipart/form-data" class="space-y-8" onsubmit="validateBillAmount()">
                    @csrf

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-4">Transaction details</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5 md:gap-6">
                        <div>
                            <label for="transaction_date" class="block text-sm font-medium text-gray-700 mb-1.5">
                                Transaction date <span class="text-red-500">*</span>
                            </label>
                            <input type="date" id="transaction_date" name="transaction_date" required value="{{ old('transaction_date', date('Y-m-d')) }}" 
                                   class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-orange-500/25 focus:border-orange-500 transition">
                            @error('transaction_date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700 mb-1.5">
                                Transaction Type <span class="text-red-500">*</span>
                            </label>
                            <select id="type" name="type" required onchange="toggleBillFields()" class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-orange-500/25 focus:border-orange-500 transition">
                                <option value="">Select Type</option>
                                <option value="credit" {{ old('type') == 'credit' ? 'selected' : '' }}>Credit (Amount Owed to Supplier)</option>
                                <option value="debit" {{ old('type') == 'debit' ? 'selected' : '' }}>Debit (Payment Made to Supplier)</option>
                            </select>
                            @error('type')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1.5 text-xs text-gray-500 leading-relaxed">
                                <span class="font-medium text-gray-600">Credit</span> = amount owed &nbsp;·&nbsp; <span class="font-medium text-gray-600">Debit</span> = payment made
                            </p>
                        </div>

                        <div>
                            <label for="amount" class="block text-sm font-medium text-gray-700 mb-1.5">
                                Amount <span class="text-red-500" id="amount-required">*</span>
                                <span class="text-xs font-normal text-gray-500" id="amount-hint">(Auto-calculated from products if added)</span>
                            </label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-xs font-medium text-gray-400">PKR</span>
                                <input type="number" id="amount" name="amount" step="0.01" min="0.01" required value="{{ old('amount') }}" 
                                       class="w-full pl-12 pr-3 py-2.5 text-sm border border-gray-200 rounded-lg bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-orange-500/25 focus:border-orange-500 transition tabular-nums" 
                                       placeholder="0.00"
                                       onchange="updateCalculatedAmount()">
                            </div>
                            @error('amount')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Bill Selection (for Debit/Payment) -->
                        <div id="billSelectionDiv" class="hidden md:col-span-2 xl:col-span-3">
                            <label for="supplier_bill_id" class="block text-sm font-medium text-gray-700 mb-1.5">
                                Pay against bill
                            </label>
                            <select id="supplier_bill_id" name="supplier_bill_id" class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-orange-500/25 focus:border-orange-500 transition">
                                <option value="">Select Bill (Optional)</option>
                                @if(isset($bills) && $bills->count() > 0)
                                    @foreach($bills as $bill)
                                        <option value="{{ $bill->id }}" {{ old('supplier_bill_id') == $bill->id ? 'selected' : '' }}>
                                            Bill #{{ $bill->bill_number ?? $bill->id }} - Amount: PKR {{ number_format($bill->bill_amount, 2) }} (Remaining: PKR {{ number_format($bill->remaining ?? 0, 2) }})
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                            @error('supplier_bill_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Create Bill Option (for Credit/Amount Owed) -->
                        <div id="createBillDiv" class="hidden md:col-span-2 xl:col-span-3">
                            <label class="flex items-start gap-3 cursor-pointer rounded-xl border border-gray-200 bg-gray-50/80 px-4 py-3 transition hover:border-orange-200 hover:bg-orange-50/30">
                                <input type="checkbox" id="create_bill" name="create_bill" value="1" {{ old('create_bill') ? 'checked' : '' }} onchange="toggleBillFields()" class="mt-0.5 rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                                <span>
                                    <span class="block text-sm font-medium text-gray-900">Create bill for this amount</span>
                                    <span class="block text-xs text-gray-500 mt-0.5">Enable bill number, line items, and optional paid amount.</span>
                                </span>
                            </label>
                        </div>

                        <div id="billFieldsDiv" class="hidden md:col-span-2 xl:col-span-3">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 md:gap-6 rounded-xl border border-orange-100/80 bg-gradient-to-br from-orange-50/40 to-amber-50/20 p-5 md:p-6">
                                <div>
                                    <label for="bill_number" class="block text-sm font-medium text-gray-700 mb-1.5">
                                        Bill number
                                    </label>
                                    <input type="text" id="bill_number" name="bill_number" value="{{ old('bill_number') }}" 
                                           class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-orange-500/25 focus:border-orange-500 transition" 
                                           placeholder="Invoice #">
                                    @error('bill_number')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="bill_date" class="block text-sm font-medium text-gray-700 mb-1.5">
                                        Bill date
                                    </label>
                                    <input type="date" id="bill_date" name="bill_date" value="{{ old('bill_date', date('Y-m-d')) }}" 
                                           class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-orange-500/25 focus:border-orange-500 transition">
                                    @error('bill_date')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="paid_amount" class="block text-sm font-medium text-gray-700 mb-1.5">
                                        Paid amount <span class="text-gray-400 font-normal">(optional)</span>
                                    </label>
                                    <input type="number" id="paid_amount" name="paid_amount" step="0.01" min="0" value="{{ old('paid_amount') }}" 
                                           class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-orange-500/25 focus:border-orange-500 transition tabular-nums" 
                                           placeholder="0.00"
                                           onchange="validatePaidAmount()"
                                           oninput="validatePaidAmount()">
                                    <p class="mt-1 text-xs text-gray-500">
                                        Enter amount if paying now (will create debit transaction)
                                        <span id="paid-amount-hint" class="text-orange-600 font-medium hidden"></span>
                                    </p>
                                    @error('paid_amount')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="md:col-span-2">
                                    <label for="bill_image" class="block text-sm font-medium text-gray-700 mb-1.5">
                                        Bill image
                                    </label>
                                    <div class="border-2 border-dashed border-gray-200 rounded-xl p-6 text-center bg-white/60 hover:border-orange-300 hover:bg-orange-50/20 transition-colors">
                                        <input type="file" 
                                               id="bill_image" 
                                               name="bill_image" 
                                               accept="image/*"
                                               class="hidden"
                                               onchange="previewBillImage(this)">
                                        <label for="bill_image" class="cursor-pointer">
                                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            </svg>
                                            <p class="mt-2 text-sm text-gray-600">Click to upload bill image</p>
                                            <p class="mt-1 text-xs text-gray-500">PNG, JPG, GIF up to 10MB</p>
                                        </label>
                                        <div id="bill-image-preview" class="mt-4 hidden">
                                            <img id="bill-preview" src="" alt="Bill Preview" class="mx-auto max-h-48 max-w-full object-contain rounded-lg">
                                            <button type="button" onclick="removeBillImage()" class="mt-3 text-sm font-medium text-red-600 hover:text-red-700 rounded-lg px-3 py-1.5 hover:bg-red-50 transition">
                                                Remove image
                                            </button>
                                        </div>
                                    </div>
                                    @error('bill_image')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                        <!-- Line items: full width (outside grid) -->
                        <div id="productsSectionDiv" class="hidden w-full min-w-0 max-w-none mt-6 lg:mt-8">
                            <div class="w-full min-w-0 rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                                <div class="flex w-full min-w-0 flex-col gap-3 border-b border-gray-100 bg-gray-50/80 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4 sm:px-5 sm:py-3.5">
                                    <div class="min-w-0 flex-1">
                                        <h4 class="text-sm font-semibold text-gray-900">Line items</h4>
                                        <p class="text-xs text-gray-500 mt-0.5 max-w-none">Same product again merges quantity automatically.</p>
                                    </div>
                                    <button type="button" onclick="addProductRow()" class="inline-flex w-full sm:w-auto shrink-0 items-center justify-center gap-2 rounded-lg bg-orange-500 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 transition">
                                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                        Add product
                                    </button>
                                </div>

                                <div id="productsTableWrapper" class="hidden w-full min-w-0 overflow-x-auto">
                                    <table class="w-full min-w-[52rem] xl:min-w-0 divide-y divide-gray-100 text-sm">
                                        <thead>
                                            <tr class="bg-gradient-to-r from-slate-50 to-gray-50">
                                                <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Product</th>
                                                <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Unit</th>
                                                <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Selling</th>
                                                <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Qty</th>
                                                <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Purchase</th>
                                                <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600" id="retail_price_header" style="display: none;">Retail</th>
                                                <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600" id="wholesale_price_header" style="display: none;">Wholesale</th>
                                                <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Total</th>
                                                <th class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-600 w-12"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="productsTableBody" class="divide-y divide-gray-100 bg-white">
                                            <!-- Product rows will be added here dynamically -->
                                        </tbody>
                                        <tfoot>
                                            <tr class="bg-slate-50/90">
                                                <td colspan="8" class="px-3 py-3 text-right text-sm font-semibold text-gray-700">Grand total</td>
                                                <td id="grandTotal" class="px-3 py-3 text-sm font-bold tabular-nums text-gray-900">PKR 0.00</td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>

                                <input type="hidden" id="calculated_amount" name="calculated_amount" value="0">
                            </div>
                        </div>

                        <div class="mt-5 md:mt-6 w-full min-w-0">
                            <label for="reference_number" class="block text-sm font-medium text-gray-700 mb-1.5">
                                Reference
                            </label>
                            <input type="text" id="reference_number" name="reference_number" value="{{ old('reference_number') }}" 
                                   class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-orange-500/25 focus:border-orange-500 transition" 
                                   placeholder="Invoice / receipt / cheque #">
                            @error('reference_number')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-4">Notes</p>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1.5">
                            Description
                        </label>
                        <textarea id="description" name="description" rows="4" 
                                  class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-orange-500/25 focus:border-orange-500 transition resize-y min-h-[6rem]" 
                                  placeholder="Optional details for this transaction">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pt-6 border-t border-gray-100">
                        <a href="{{ route('suppliers.show', $supplier) }}" class="inline-flex justify-center items-center rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-200 transition">
                            Cancel
                        </a>
                        <button type="submit" class="inline-flex justify-center items-center rounded-lg bg-orange-500 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 transition">
                            Save transaction
                        </button>
                    </div>
                </form>
                </div>
            </div>
        </div>
    </div>
    </div>

    <!-- Products Data for JavaScript -->
    <script>
        const productsData = @json($productsData ?? []);
        const categoriesData = @json(isset($categories) ? $categories->map(function($c) {
            return ['id' => $c->id, 'name' => $c->name];
        })->values() : []);
        const unitsData = @json(isset($units) ? $units->map(function($u) {
            return ['id' => $u->id, 'name' => $u->name, 'short_name' => $u->short_name];
        })->values() : []);
        
        let productRowIndex = 0;

        /** Always list every active unit; optionally pre-select one (e.g. product base unit). */
        function buildUnitSelectOptionsHtml(selectedUnitId) {
            const want = selectedUnitId != null && selectedUnitId !== '' ? String(selectedUnitId) : '';
            let html = '<option value="">Select Unit</option>';
            unitsData.forEach(u => {
                const sel = want && String(u.id) === want ? ' selected' : '';
                html += `<option value="${u.id}"${sel}>${u.name} (${u.short_name})</option>`;
            });
            return html;
        }

        function setUnitSelectOptions(unitSelect, selectedUnitId) {
            if (!unitSelect) return;
            unitSelect.innerHTML = buildUnitSelectOptionsHtml(selectedUnitId);
        }
        
        // SKU generation function (same as in ProductController)
        function generateSku(productName = '') {
            // Get initials from product name
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

            // Generate date part (YYYYMMDD)
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const datePart = year + month + day;

            // Generate random number part (4 digits)
            const randomPart = String(Math.floor(Math.random() * 10000)).padStart(4, '0');

            // Combine: PREFIX-YYYYMMDD-XXXX
            return prefix + '-' + datePart + '-' + randomPart;
        }
        
        function toggleBillFields() {
            const type = document.getElementById('type').value;
            const billSelectionDiv = document.getElementById('billSelectionDiv');
            const createBillDiv = document.getElementById('createBillDiv');
            const billFieldsDiv = document.getElementById('billFieldsDiv');
            const productsSectionDiv = document.getElementById('productsSectionDiv');
            const createBillCheckbox = document.getElementById('create_bill');
            const amountInput = document.getElementById('amount');

            if (type === 'debit') {
                // Show bill selection for payments (debit = payment made)
                billSelectionDiv.classList.remove('hidden');
                createBillDiv.classList.add('hidden');
                billFieldsDiv.classList.add('hidden');
                productsSectionDiv.classList.add('hidden');
                amountInput.required = true;
            } else if (type === 'credit') {
                // Show create bill option for new bills (credit = amount owed)
                billSelectionDiv.classList.add('hidden');
                createBillDiv.classList.remove('hidden');
                
                // Show bill fields and products section if checkbox is checked
                if (createBillCheckbox && createBillCheckbox.checked) {
                    billFieldsDiv.classList.remove('hidden');
                    productsSectionDiv.classList.remove('hidden');
                    amountInput.required = false;
                    document.getElementById('amount-required').style.display = 'none';
                    // Check if products exist and update amount field accordingly
                    updateGrandTotal();
                } else {
                    billFieldsDiv.classList.add('hidden');
                    productsSectionDiv.classList.add('hidden');
                    amountInput.required = true;
                    document.getElementById('amount-required').style.display = 'inline';
                    // Reset amount field to editable
                    amountInput.readOnly = false;
                    amountInput.classList.remove('bg-slate-100', 'cursor-not-allowed');
                    const amountHint = document.getElementById('amount-hint');
                    if (amountHint) {
                        amountHint.textContent = '(Auto-calculated from products if added)';
                        amountHint.classList.remove('text-orange-600', 'font-medium');
                    }
                }
            } else {
                billSelectionDiv.classList.add('hidden');
                createBillDiv.classList.add('hidden');
                billFieldsDiv.classList.add('hidden');
                productsSectionDiv.classList.add('hidden');
                amountInput.required = true;
            }
        }

        function addProductRow(productId = null, productName = null, sku = null) {
            // SKU will be auto-generated by backend, so we don't need it as parameter
            // Check if product already exists in the table
            if (productId || productName) {
                const existingRow = findExistingProductRow(productId, productName, sku);
                if (existingRow) {
                    // Increase quantity instead of adding new row
                    const quantityInput = existingRow.querySelector('.quantity-input');
                    const currentQty = parseFloat(quantityInput.value) || 0;
                    quantityInput.value = (currentQty + 1).toFixed(2);
                    calculateRowTotal(parseInt(existingRow.dataset.index));
                    updateGrandTotal();
                    return;
                }
            }
            
            ensureSharedProductsDatalist();
            const tbody = document.getElementById('productsTableBody');
            const row = document.createElement('tr');
            row.className = 'product-row';
            row.dataset.index = productRowIndex;
            
            // Get product data if productId is provided
            const product = productId ? productsData.find(p => p.id == productId) : null;
            
            row.innerHTML = `
                <td class="px-3 py-2.5 align-top">
                    <div class="relative min-w-0 w-full">
                        <input type="text" 
                               name="products[${productRowIndex}][product_name]" 
                               class="product-name-input w-full px-2.5 py-1.5 text-sm border border-gray-200 rounded-lg bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500" 
                               value="${product ? product.name : ''}" 
                               placeholder="Type or search product (auto-merge if exists)"
                               required
                               autocomplete="off"
                               list="supplier-products-list"
                               onchange="handleProductNameChange(this, ${productRowIndex})"
                               onblur="handleProductNameChange(this, ${productRowIndex})"
                               oninput="handleProductNameInput(this, ${productRowIndex}, false)"
                               onkeydown="if(event.key === 'Enter') { event.preventDefault(); handleProductNameChange(this, ${productRowIndex}); this.blur(); }">
                        <input type="hidden" name="products[${productRowIndex}][product_id]" class="product-id-input" value="${product ? product.id : ''}">
                        <input type="hidden" name="products[${productRowIndex}][category_id]" class="category-id-input" value="">
                        <input type="hidden" name="products[${productRowIndex}][product_sku]" class="product-sku-input" value="${product ? product.sku : ''}">
                        <input type="hidden" name="products[${productRowIndex}][discount]" class="discount-input" value="0">
                        <input type="hidden" name="products[${productRowIndex}][tax]" class="tax-input" value="0">
                    </div>
                </td>
                <td class="px-3 py-2.5 align-top">
                    <select name="products[${productRowIndex}][unit_id]" 
                            class="unit-select-input w-full min-w-0 px-2 py-1.5 text-sm border border-gray-200 rounded-lg bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500" 
                            required
                            onchange="handleUnitChange(this, ${productRowIndex})">
                        ${buildUnitSelectOptionsHtml(
                            product
                                ? (product.base_unit_id || product.unit_id || (product.available_units && product.available_units.find(u => u.is_base_unit)?.id) || '')
                                : ''
                        )}
                    </select>
                    <input type="hidden" class="unit-id-input" value="${product ? (product.base_unit_id || product.unit_id) : ''}">
                    <input type="hidden" name="products[${productRowIndex}][base_unit_id]" class="base-unit-id-input" value="${product ? product.base_unit_id : ''}">
                </td>
                <td class="px-3 py-2.5 align-top">
                    <select name="products[${productRowIndex}][selling_type]" 
                            class="selling-type-input w-full min-w-0 px-2 py-1.5 text-sm border border-gray-200 rounded-lg bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500" 
                            required
                            onchange="togglePriceFieldsForRow(${productRowIndex})">
                        <option value="">Select</option>
                        <option value="retail" ${product && product.selling_type == 'retail' ? 'selected' : ''}>Retail</option>
                        <option value="wholesale" ${product && product.selling_type == 'wholesale' ? 'selected' : ''}>Wholesale</option>
                        <option value="both" ${!product || product.selling_type == 'both' ? 'selected' : ''}>Both</option>
                    </select>
                </td>
                <td class="px-3 py-2.5 align-top">
                    <input type="number" 
                           name="products[${productRowIndex}][quantity]" 
                           class="quantity-input w-full min-w-0 px-2 py-1.5 text-sm tabular-nums border border-gray-200 rounded-lg bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500" 
                           value="1" 
                           min="0.01" 
                           step="0.01"
                           required
                           onchange="calculateRowTotal(${productRowIndex})">
                </td>
                <td class="px-3 py-2.5 align-top">
                    <input type="number" 
                           name="products[${productRowIndex}][unit_price]" 
                           class="unit-price-input w-full min-w-0 px-2 py-1.5 text-sm tabular-nums border border-gray-200 rounded-lg bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500" 
                           value="${product ? product.purchase_price : '0.00'}" 
                           min="0" 
                           step="0.01"
                           required
                           onchange="calculateRowTotal(${productRowIndex})">
                </td>
                <td class="px-3 py-2.5 align-top retail-price-cell" style="display: none;">
                    <input type="number" 
                           name="products[${productRowIndex}][retail_price]" 
                           class="retail-price-input w-full min-w-0 px-2 py-1.5 text-sm tabular-nums border border-gray-200 rounded-lg bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500" 
                           value="${product && product.retail_price ? product.retail_price : '0.00'}" 
                           min="0" 
                           step="0.01"
                           placeholder="0.00">
                </td>
                <td class="px-3 py-2.5 align-top wholesale-price-cell" style="display: none;">
                    <input type="number" 
                           name="products[${productRowIndex}][wholesale_price]" 
                           class="wholesale-price-input w-full min-w-0 px-2 py-1.5 text-sm tabular-nums border border-gray-200 rounded-lg bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500" 
                           value="${product && product.wholesale_price ? product.wholesale_price : '0.00'}" 
                           min="0" 
                           step="0.01"
                           placeholder="0.00">
                </td>
                <td class="px-3 py-2.5 align-top">
                    <input type="number" 
                           name="products[${productRowIndex}][total]" 
                           class="total-input w-full min-w-0 px-2 py-1.5 text-sm font-semibold tabular-nums border border-gray-200 rounded-lg bg-gray-50 text-gray-800" 
                           value="0.00" 
                           readonly>
                </td>
                <td class="px-3 py-2.5 text-center align-top">
                    <button type="button" onclick="removeProductRow(this)" class="inline-flex items-center justify-center rounded-lg p-2 text-red-600 bg-red-50 hover:bg-red-100 transition" title="Remove line">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </td>
            `;
            
            tbody.appendChild(row);
            productRowIndex++;
            
            // Update table visibility
            updateTableVisibility();
            
            // Initialize price fields visibility based on selling type
            togglePriceFieldsForRow(parseInt(row.dataset.index));
            
            // Calculate total for the new row
            calculateRowTotal(parseInt(row.dataset.index));
            updateGrandTotal();
        }
        
        /** Resolve datalist option without CSS selector injection (names/SKUs may contain quotes). */
        function resolveDatalistOption(listId, rawValue) {
            const dl = document.getElementById(listId);
            if (!dl || rawValue == null) return null;
            const value = String(rawValue).trim();
            if (!value) return null;
            const options = dl.querySelectorAll('option');
            const vLower = value.toLowerCase();
            for (const opt of options) {
                if (opt.value === value) return opt;
            }
            for (const opt of options) {
                const sku = (opt.getAttribute('data-sku') || '').trim();
                if (sku && sku === value) return opt;
            }
            for (const opt of options) {
                const optValue = opt.value.toLowerCase();
                const optSku = (opt.getAttribute('data-sku') || '').toLowerCase();
                if (optValue.includes(vLower) || (optSku && optSku.includes(vLower))) return opt;
            }
            return null;
        }

        // Find existing product row in the table (same product = merge qty)
        function findExistingProductRow(productId, productName, sku) {
            const rows = document.querySelectorAll('.product-row');
            const searchName = productName ? productName.trim().toLowerCase() : '';
            const searchSku = sku ? String(sku).trim().toLowerCase() : '';

            let resolvedId = '';
            if (productId) resolvedId = String(productId);
            if (!resolvedId && searchSku) {
                const bySku = productsData.find(p => String(p.sku || '').trim().toLowerCase() === searchSku);
                if (bySku) resolvedId = String(bySku.id);
            }

            for (let row of rows) {
                const rowProductId = String(row.querySelector('.product-id-input').value || '');
                const rowProductName = row.querySelector('.product-name-input').value.trim().toLowerCase();
                const rowSku = String(row.querySelector('.product-sku-input')?.value || '').trim().toLowerCase();

                if (resolvedId && rowProductId === resolvedId) return row;
                if (productId && rowProductId === String(productId)) return row;

                if (searchSku && rowSku && searchSku === rowSku) return row;

                if (searchName && rowProductName === searchName) return row;

                if (searchSku && rowProductId) {
                    const p = productsData.find(x => String(x.id) === rowProductId);
                    if (p && String(p.sku || '').trim().toLowerCase() === searchSku) return row;
                }
            }
            return null;
        }
        
        function handleProductNameChange(input, index) {
            handleProductNameInput(input, index, true);
        }
        
        function handleProductNameInput(input, index, fromChange = false) {
            const value = input.value.trim();
            if (!value) return;
            
            const row = input.closest('tr');
            if (!row || !row.classList.contains('product-row') || !row.isConnected) return;
            
            let selectedOption = resolveDatalistOption('supplier-products-list', value);
            
            if (selectedOption && fromChange) {
                // Existing product selected - check if already in table
                const productId = selectedOption.getAttribute('data-id');
                const sku = selectedOption.getAttribute('data-sku') || '';
                const pd = productId ? productsData.find(p => String(p.id) === String(productId)) : null;
                // If user picked the SKU option, input shows SKU — normalize to product name for matching/submit
                if (pd && pd.sku && value === String(pd.sku).trim()) {
                    input.value = pd.name;
                }
                const productName = pd ? pd.name : selectedOption.value;
                const price = selectedOption.getAttribute('data-price');
                
                const existingRow = findExistingProductRow(productId, productName, sku);
                
                if (existingRow && existingRow.dataset.index != index) {
                    // Product already exists in another row - update quantity and price
                    const quantityInput = existingRow.querySelector('.quantity-input');
                    const hiddenUnitPrice = existingRow.querySelector('.unit-price-input');
                    const currentQty = parseFloat(quantityInput.value) || 0;
                    
                    // Quantity add karo (increment)
                    quantityInput.value = (currentQty + 1).toFixed(2);
                    
                    // Price update karo (last added price) - update hidden unit_price
                    if (price && hiddenUnitPrice) {
                        hiddenUnitPrice.value = parseFloat(price).toFixed(2);
                    }
                    
                    calculateRowTotal(parseInt(existingRow.dataset.index));
                    updateGrandTotal();
                    
                    // Remove current row since product already exists
                    row.remove();
                    updateTableVisibility(); // Update table visibility after removal
                    return;
                }
                
                // Fill in product details for new row
                row.querySelector('.product-id-input').value = productId || '';
                const skuInput = row.querySelector('.product-sku-input');
                if (!skuInput.dataset.manuallyEdited) {
                    skuInput.value = sku || '';
                    skuInput.dataset.autoGenerated = sku ? 'false' : 'true';
                }
                // Update hidden unit_price field
                const hiddenUnitPrice = row.querySelector('.unit-price-input');
                if (hiddenUnitPrice && price) {
                    hiddenUnitPrice.value = parseFloat(price).toFixed(2);
                }
                
                // Fill unit, selling type, and prices from selected product
                const baseUnitId = selectedOption.getAttribute('data-base-unit-id') || selectedOption.getAttribute('data-unit-id');
                const sellingType = selectedOption.getAttribute('data-selling-type') || 'both';
                const retailPrice = selectedOption.getAttribute('data-retail-price') || '0.00';
                const wholesalePrice = selectedOption.getAttribute('data-wholesale-price') || '0.00';
                const availableUnitsJson = selectedOption.getAttribute('data-available-units');
                let availableUnits = [];
                
                try {
                    availableUnits = availableUnitsJson ? JSON.parse(availableUnitsJson) : [];
                } catch (e) {
                    console.error('Error parsing available units:', e);
                }
                
                // Unit dropdown: show all units; default to product base / default unit
                const unitSelect = row.querySelector('.unit-select-input');
                if (unitSelect) {
                    let defaultUnit = baseUnitId || '';
                    if (!defaultUnit && availableUnits.length > 0) {
                        const baseU = availableUnits.find(u => u.is_base_unit);
                        defaultUnit = baseU ? baseU.id : availableUnits[0].id;
                    }
                    setUnitSelectOptions(unitSelect, defaultUnit);
                }
                
                // Update hidden fields
                const unitHidden = row.querySelector('.unit-id-input');
                if (unitHidden && baseUnitId) {
                    unitHidden.value = baseUnitId;
                }
                
                const baseUnitHidden = row.querySelector('.base-unit-id-input');
                if (baseUnitHidden && baseUnitId) {
                    baseUnitHidden.value = baseUnitId;
                }
                
                const sellingTypeSelect = row.querySelector('.selling-type-input');
                if (sellingTypeSelect) {
                    sellingTypeSelect.value = sellingType;
                }
                
                const retailPriceInput = row.querySelector('.retail-price-input');
                if (retailPriceInput) {
                    retailPriceInput.value = parseFloat(retailPrice).toFixed(2);
                }
                
                const wholesalePriceInput = row.querySelector('.wholesale-price-input');
                if (wholesalePriceInput) {
                    wholesalePriceInput.value = parseFloat(wholesalePrice).toFixed(2);
                }
                
                // Toggle price fields based on selling type
                togglePriceFieldsForRow(index);
                
                calculateRowTotal(index);
            } else if (value && fromChange) {
                // New product - check if same name or SKU already exists in table
                const bySku = productsData.find(p => String(p.sku || '').trim().toLowerCase() === value.toLowerCase());
                const existingRow = bySku
                    ? findExistingProductRow(String(bySku.id), bySku.name, bySku.sku)
                    : findExistingProductRow(null, value, null);
                
                if (existingRow && existingRow.dataset.index != index) {
                    // Same product name exists - increase quantity
                    const quantityInput = existingRow.querySelector('.quantity-input');
                    const currentQty = parseFloat(quantityInput.value) || 0;
                    quantityInput.value = (currentQty + 1).toFixed(2);
                    calculateRowTotal(parseInt(existingRow.dataset.index));
                    updateGrandTotal();
                    
                    // Remove current row
                    row.remove();
                    return;
                }
                
                // New product - clear product_id to indicate it's a new product
                row.querySelector('.product-id-input').value = '';
                const skuInput = row.querySelector('.product-sku-input');
                // Auto-generate SKU if empty or was auto-generated
                if (!skuInput.value || skuInput.dataset.autoGenerated === 'true') {
                    skuInput.value = generateSku(value);
                    skuInput.dataset.autoGenerated = 'true';
                }
                // Update hidden unit_price if needed
                const hiddenUnitPrice = row.querySelector('.unit-price-input');
                if (hiddenUnitPrice && (!hiddenUnitPrice.value || hiddenUnitPrice.value == '0.00')) {
                    // Set default purchase price from retail or wholesale price if available
                    const retailPriceInput = row.querySelector('.retail-price-input');
                    const wholesalePriceInput = row.querySelector('.wholesale-price-input');
                    if (retailPriceInput && retailPriceInput.value) {
                        hiddenUnitPrice.value = parseFloat(retailPriceInput.value).toFixed(2);
                    } else if (wholesalePriceInput && wholesalePriceInput.value) {
                        hiddenUnitPrice.value = parseFloat(wholesalePriceInput.value).toFixed(2);
                    }
                }
            }
        }
        
        // SKU functions removed - SKU is now auto-generated by backend when product is created
        
        // Toggle price fields based on selling type for a specific row
        function togglePriceFieldsForRow(index) {
            const row = document.querySelector(`tr[data-index="${index}"]`);
            if (!row) return;
            
            const sellingTypeSelect = row.querySelector('.selling-type-input');
            const retailPriceCell = row.querySelector('.retail-price-cell');
            const wholesalePriceCell = row.querySelector('.wholesale-price-cell');
            const retailPriceInput = row.querySelector('.retail-price-input');
            const wholesalePriceInput = row.querySelector('.wholesale-price-input');
            
            if (!sellingTypeSelect || !retailPriceCell || !wholesalePriceCell) return;
            
            const sellingType = sellingTypeSelect.value;
            
            // Hide both cells first
            retailPriceCell.style.display = 'none';
            wholesalePriceCell.style.display = 'none';
            if (retailPriceInput) retailPriceInput.required = false;
            if (wholesalePriceInput) wholesalePriceInput.required = false;
            
            // Show appropriate fields based on selling type
            if (sellingType === 'retail') {
                retailPriceCell.style.display = 'table-cell';
                if (retailPriceInput) retailPriceInput.required = true;
                updatePriceHeaders();
            } else if (sellingType === 'wholesale') {
                wholesalePriceCell.style.display = 'table-cell';
                if (wholesalePriceInput) wholesalePriceInput.required = true;
                updatePriceHeaders();
            } else if (sellingType === 'both') {
                retailPriceCell.style.display = 'table-cell';
                wholesalePriceCell.style.display = 'table-cell';
                if (retailPriceInput) retailPriceInput.required = true;
                if (wholesalePriceInput) wholesalePriceInput.required = true;
                updatePriceHeaders();
            }
            
            // Always update headers after toggling
            updatePriceHeaders();
        }
        
        // Update table headers based on visible price fields
        function updatePriceHeaders() {
            const rows = document.querySelectorAll('.product-row');
            let showRetail = false;
            let showWholesale = false;
            
            rows.forEach(row => {
                const sellingType = row.querySelector('.selling-type-input')?.value;
                if (sellingType === 'retail' || sellingType === 'both') {
                    showRetail = true;
                }
                if (sellingType === 'wholesale' || sellingType === 'both') {
                    showWholesale = true;
                }
            });
            
            const retailHeader = document.getElementById('retail_price_header');
            const wholesaleHeader = document.getElementById('wholesale_price_header');
            
            if (retailHeader) {
                retailHeader.style.display = showRetail ? 'table-cell' : 'none';
            }
            if (wholesaleHeader) {
                wholesaleHeader.style.display = showWholesale ? 'table-cell' : 'none';
            }
        }
        
        function calculateRowTotal(index) {
            const row = document.querySelector(`tr[data-index="${index}"]`);
            if (!row) return;
            
            const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
            // Use hidden unit_price (purchase price) for calculation
            const unitPrice = parseFloat(row.querySelector('.unit-price-input').value) || 0;
            
            // Calculate total without discount and tax
            const total = quantity * unitPrice;
            
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
            
            // Update the hidden calculated_amount field
            document.getElementById('calculated_amount').value = grandTotal.toFixed(2);
            
            // Also update the amount field if products exist
            const productRows = document.querySelectorAll('.product-row').length;
            const amountInput = document.getElementById('amount');
            const amountHint = document.getElementById('amount-hint');
            
            if (productRows > 0 && grandTotal > 0) {
                // Products exist - make amount readonly and match product total
                amountInput.value = grandTotal.toFixed(2);
                amountInput.readOnly = true;
                amountInput.classList.add('bg-slate-100', 'cursor-not-allowed');
                if (amountHint) {
                    amountHint.textContent = '(Auto-calculated from products - cannot be edited)';
                    amountHint.classList.add('text-orange-600', 'font-medium');
                }
            } else {
                // No products - allow manual amount entry
                amountInput.readOnly = false;
                amountInput.classList.remove('bg-slate-100', 'cursor-not-allowed');
                if (amountHint) {
                    amountHint.textContent = '(Auto-calculated from products if added)';
                    amountHint.classList.remove('text-orange-600', 'font-medium');
                }
            }
            
            // Validate paid amount when bill amount changes
            validatePaidAmount();
        }
        
        function updateCalculatedAmount() {
            // If user manually changes amount, ensure it's valid
            const amountInput = document.getElementById('amount');
            const productRows = document.querySelectorAll('.product-row').length;
            
            if (productRows > 0) {
                // If products exist, recalculate from products and prevent manual changes
                updateGrandTotal();
                // Restore readonly state if user somehow changed it
                amountInput.readOnly = true;
                amountInput.classList.add('bg-slate-100', 'cursor-not-allowed');
            } else {
                // Validate paid amount when amount is manually changed
                validatePaidAmount();
            }
        }
        
        // Helper function to update table visibility
        function updateTableVisibility() {
            const productRows = document.querySelectorAll('.product-row').length;
            const tableWrapper = document.getElementById('productsTableWrapper');
            
            if (productRows > 0) {
                // Show table if there are products
                if (tableWrapper) {
                    tableWrapper.classList.remove('hidden');
                }
            } else {
                // Hide table when no products remain
                if (tableWrapper) {
                    tableWrapper.classList.add('hidden');
                }
                
                // No products left - make amount editable again
                const amountInput = document.getElementById('amount');
                if (amountInput) {
                    amountInput.readOnly = false;
                    amountInput.classList.remove('bg-slate-100', 'cursor-not-allowed');
                }
                const amountHint = document.getElementById('amount-hint');
                if (amountHint) {
                    amountHint.textContent = '(Auto-calculated from products if added)';
                    amountHint.classList.remove('text-orange-600', 'font-medium');
                }
            }
        }
        
        function removeProductRow(button) {
            const row = button.closest('tr');
            row.remove();
            updateGrandTotal();
            updatePriceHeaders(); // Update headers after removing a row
            updateTableVisibility(); // Update table visibility
        }

        function previewBillImage(input) {
            const preview = document.getElementById('bill-preview');
            const previewDiv = document.getElementById('bill-image-preview');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    previewDiv.classList.remove('hidden');
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        function removeBillImage() {
            const input = document.getElementById('bill_image');
            const previewDiv = document.getElementById('bill-image-preview');
            
            input.value = '';
            previewDiv.classList.add('hidden');
        }
        
        function validatePaidAmount() {
            const paidAmountInput = document.getElementById('paid_amount');
            const amountInput = document.getElementById('amount');
            const createBillCheckbox = document.getElementById('create_bill');
            const paidAmountHint = document.getElementById('paid-amount-hint');
            
            if (!paidAmountInput || !amountInput) return;
            
            const paidAmount = parseFloat(paidAmountInput.value) || 0;
            const billAmount = parseFloat(amountInput.value) || 0;
            
            // Only validate if creating a bill
            if (createBillCheckbox && createBillCheckbox.checked && billAmount > 0) {
                if (paidAmount > billAmount) {
                    paidAmountInput.classList.add('border-red-500');
                    if (paidAmountHint) {
                        paidAmountHint.textContent = ' (Cannot exceed bill amount: PKR ' + billAmount.toFixed(2) + ')';
                        paidAmountHint.classList.remove('hidden');
                    }
                    return false;
                } else {
                    paidAmountInput.classList.remove('border-red-500');
                    if (paidAmountHint) {
                        const remaining = billAmount - paidAmount;
                        if (paidAmount > 0) {
                            paidAmountHint.textContent = ' (Remaining: PKR ' + remaining.toFixed(2) + ')';
                            paidAmountHint.classList.remove('hidden');
                        } else {
                            paidAmountHint.classList.add('hidden');
                        }
                    }
                }
            } else {
                paidAmountInput.classList.remove('border-red-500');
                if (paidAmountHint) {
                    paidAmountHint.classList.add('hidden');
                }
            }
            return true;
        }
        
        function validateBillAmount() {
            const productRows = document.querySelectorAll('.product-row').length;
            const createBillCheckbox = document.getElementById('create_bill');
            const type = document.getElementById('type').value;
            
            // Only validate if creating a bill with products
            if (type === 'credit' && createBillCheckbox && createBillCheckbox.checked && productRows > 0) {
                const grandTotal = parseFloat(document.getElementById('calculated_amount').value) || 0;
                const amountValue = parseFloat(document.getElementById('amount').value) || 0;
                
                // Ensure amount matches product total
                if (Math.abs(grandTotal - amountValue) > 0.01) {
                    document.getElementById('amount').value = grandTotal.toFixed(2);
                }
            }
            
            // Also validate paid amount
            validatePaidAmount();
            return true;
        }
        
        // Handle unit change (catalog product keeps inventory base_unit_id; line unit_id is what user picked)
        function handleUnitChange(select, index) {
            const row = select.closest('tr');
            const productId = row.querySelector('.product-id-input').value;
            const baseUnitHidden = row.querySelector('.base-unit-id-input');
            if (productId && baseUnitHidden) {
                const product = productsData.find(p => p.id == productId);
                if (product && product.base_unit_id) {
                    baseUnitHidden.value = product.base_unit_id;
                }
            } else if (!productId && baseUnitHidden) {
                const uid = select.value;
                if (uid) {
                    baseUnitHidden.value = uid;
                }
            }
        }

        function ensureSharedProductsDatalist() {
            if (document.getElementById('supplier-products-list')) {
                return;
            }
            const dl = document.createElement('datalist');
            dl.id = 'supplier-products-list';
            dl.innerHTML = productsData.map(p => {
                const displayName = p.sku ? `${p.name} (${p.sku})` : p.name;
                return `<option value="${p.name}" label="${displayName}" data-display="${displayName}" data-id="${p.id}" data-sku="${p.sku || ''}" data-price="${p.purchase_price || 0}" data-unit-id="${p.unit_id || ''}" data-base-unit-id="${p.base_unit_id || ''}" data-selling-type="${p.selling_type || 'both'}" data-retail-price="${p.retail_price || 0}" data-wholesale-price="${p.wholesale_price || 0}" data-available-units='${JSON.stringify(p.available_units || [])}'></option>`;
            }).join('') + productsData.filter(p => p.sku).map(p => {
                const displayName = `${p.name} (${p.sku})`;
                return `<option value="${p.sku}" label="${displayName}" data-display="${displayName}" data-id="${p.id}" data-sku="${p.sku || ''}" data-price="${p.purchase_price || 0}" data-unit-id="${p.unit_id || ''}" data-base-unit-id="${p.base_unit_id || ''}" data-selling-type="${p.selling_type || 'both'}" data-retail-price="${p.retail_price || 0}" data-wholesale-price="${p.wholesale_price || 0}" data-available-units='${JSON.stringify(p.available_units || [])}'></option>`;
            }).join('');
            document.body.appendChild(dl);
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            ensureSharedProductsDatalist();
            toggleBillFields();
        });
    </script>
</x-app-layout>

