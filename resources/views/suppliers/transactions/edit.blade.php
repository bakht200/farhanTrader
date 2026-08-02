<x-app-layout>
    <x-slot name="header">
        Edit Transaction
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
            <span class="text-gray-900 font-medium">Edit Transaction</span>
        </nav>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Wallet Summary Card -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold mb-4 text-gray-900">{{ $supplier->name }}</h3>
                <div class="space-y-4">
                    <div class="border-b pb-4">
                        <p class="text-sm text-gray-500 mb-1">Total Paid</p>
                        <p class="text-2xl font-bold text-green-600">PKR {{ number_format($debitTotal ?? 0, 2) }}</p>
                    </div>
                    <div class="border-b pb-4">
                        <p class="text-sm text-gray-500 mb-1">Total</p>
                        <p class="text-2xl font-bold text-gray-700">PKR {{ number_format($creditTotal ?? 0, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Remaining</p>
                        <p class="text-2xl font-bold {{ ($balance ?? 0) > 0 ? 'text-red-600' : 'text-green-600' }}">
                            PKR {{ number_format($balance ?? 0, 2) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transaction Form -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold mb-6">Edit Transaction</h3>

                <form method="POST" action="{{ route('suppliers.transactions.update', [$supplier, $transaction]) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700 mb-2">
                                Transaction Type <span class="text-red-500">*</span>
                            </label>
                            <select id="type" name="type" required class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                                <option value="credit" {{ old('type', $transaction->type) == 'credit' ? 'selected' : '' }}>Credit (Amount Owed to Supplier)</option>
                                <option value="debit" {{ old('type', $transaction->type) == 'debit' ? 'selected' : '' }}>Debit (Payment Made to Supplier)</option>
                            </select>
                            @error('type')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500">
                                Credit = Amount owed | Debit = Payment made
                            </p>
                        </div>

                        <div>
                            <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">
                                Amount <span class="text-red-500">*</span>
                            </label>
                            <input type="number" id="amount" name="amount" step="0.01" min="0.01" required value="{{ old('amount', $transaction->amount) }}" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500" 
                                   placeholder="0.00">
                            @error('amount')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Bill Selection (for Debit/Payment) -->
                        <div id="billSelectionDiv" class="{{ old('type', $transaction->type) == 'debit' ? '' : 'hidden' }}">
                            <label for="supplier_bill_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Select Bill to Pay Against
                            </label>
                            <select id="supplier_bill_id" name="supplier_bill_id" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                                <option value="">Select Bill (Optional)</option>
                                @if(isset($bills) && $bills->count() > 0)
                                    @foreach($bills as $bill)
                                        @php
                                            $remaining = $bill->bill_amount - $bill->transactions()->where('type', 'debit')->sum('amount');
                                        @endphp
                                        <option value="{{ $bill->id }}" {{ old('supplier_bill_id', $transaction->supplier_bill_id) == $bill->id ? 'selected' : '' }}>
                                            Bill #{{ $bill->bill_number ?? $bill->id }} - Amount: PKR {{ number_format($bill->bill_amount, 2) }} (Remaining: PKR {{ number_format($remaining, 2) }})
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                            @error('supplier_bill_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="transaction_date" class="block text-sm font-medium text-gray-700 mb-2">
                                Transaction Date <span class="text-red-500">*</span>
                            </label>
                            <input type="date" id="transaction_date" name="transaction_date" required value="{{ old('transaction_date', $transaction->transaction_date->format('Y-m-d')) }}" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                            @error('transaction_date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="reference_number" class="block text-sm font-medium text-gray-700 mb-2">
                                Reference Number
                            </label>
                            <input type="text" id="reference_number" name="reference_number" value="{{ old('reference_number', $transaction->reference_number) }}" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500" 
                                   placeholder="Invoice/Receipt/Check number">
                            @error('reference_number')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                            Description
                        </label>
                        <textarea id="description" name="description" rows="4" 
                                  class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500" 
                                  placeholder="Enter transaction description">{{ old('description', $transaction->description) }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end space-x-4 pt-4 border-t">
                        <a href="{{ route('suppliers.show', $supplier) }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2 rounded-md font-medium">
                            Cancel
                        </a>
                        <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white px-6 py-2 rounded-md font-medium">
                            Update Transaction
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleBillSelection() {
            const type = document.getElementById('type').value;
            const billSelectionDiv = document.getElementById('billSelectionDiv');
            
            if (type === 'debit') {
                billSelectionDiv.classList.remove('hidden');
            } else {
                billSelectionDiv.classList.add('hidden');
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            toggleBillSelection();
            document.getElementById('type').addEventListener('change', toggleBillSelection);
        });
    </script>
</x-app-layout>











