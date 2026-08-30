<x-app-layout>
    <x-slot name="header">
        Supplier Details
    </x-slot>

    <style>[x-cloak] { display: none !important; }</style>

    <div data-ftpos-page="supplier-show" data-ftpos-supplier-id="{{ $supplier->id }}">
    <!-- Breadcrumb -->
    <div class="mb-4">
        <nav class="text-sm text-gray-600">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-900">Dashboard</a>
            <span class="mx-2">></span>
            <a href="{{ route('suppliers.index') }}" class="hover:text-gray-900">Suppliers</a>
            <span class="mx-2">></span>
            <span class="text-gray-900 font-medium">{{ $supplier->name }}</span>
        </nav>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ $supplier->name }}</h2>
                <p class="text-sm text-gray-500">Supplier ID: {{ $supplier->supplier_id ?? 'SN-' . str_pad($supplier->id, 3, '0', STR_PAD_LEFT) }}</p>
                @if($supplier->is_anonymous)
                    <p class="mt-2 text-sm text-slate-600">Cash purchases from people who are not a saved supplier.</p>
                @endif
            </div>
            <div class="flex space-x-2">
                @unless($supplier->is_anonymous)
                <a href="{{ route('suppliers.edit', $supplier) }}" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-md">
                    Edit Supplier
                </a>
                @endunless
                <a href="{{ route('suppliers.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-md">
                    Back to List
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h3 class="text-lg font-semibold mb-4">Supplier Information</h3>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Name</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $supplier->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Company Name</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $supplier->company_name ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Email</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $supplier->email ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Phone</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $supplier->phone ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Address</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $supplier->address ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">City</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $supplier->city ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">State</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $supplier->state ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Country</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $supplier->country ?? 'N/A' }}</dd>
                    </div>
                </dl>
            </div>

            <div>
                <h3 class="text-lg font-semibold mb-4">Supplier Wallet</h3>
                <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-lg p-4 space-y-4">
                    <div class="border-b border-orange-200 pb-3">
                        <p class="text-sm text-gray-600 mb-1">Total Paid</p>
                        <p class="text-2xl font-bold text-green-600">PKR {{ number_format($debitTotal ?? 0, 1) }}</p>
                    </div>
                    <div class="border-b border-orange-200 pb-3">
                        <p class="text-sm text-gray-600 mb-1">Total</p>
                        <p class="text-2xl font-bold text-gray-700">PKR {{ number_format($creditTotal ?? 0, 1) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Remaining</p>
                        <p class="text-2xl font-bold {{ ($balance ?? 0) > 0 ? 'text-red-600' : 'text-green-600' }}">
                            PKR {{ number_format($balance ?? 0, 1) }}
                        </p>
                    </div>
                    <div class="pt-2">
                        @if(($balance ?? 0) > 0)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                <span class="w-1.5 h-1.5 mr-1.5 bg-red-500 rounded-full"></span>
                                Unpaid
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <span class="w-1.5 h-1.5 mr-1.5 bg-green-500 rounded-full"></span>
                                Paid
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Bills Section (collapsible, oldest first) -->
        @if(isset($bills) && $bills->count() > 0)
        <div class="mt-6" x-data="{ billsOpen: false }">
            <button
                type="button"
                @click="billsOpen = !billsOpen"
                class="w-full flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-3 text-left shadow-sm hover:bg-gray-50"
            >
                <div class="flex items-center gap-2">
                    <h3 class="text-lg font-semibold text-gray-900">Bills</h3>
                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700">
                        {{ $bills->count() }}
                    </span>
                </div>
                <svg class="h-5 w-5 text-gray-500 transition-transform duration-200" :class="{ 'rotate-180': billsOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            <div
                x-show="billsOpen"
                x-cloak
                x-transition
                class="mt-3 overflow-x-auto border border-gray-200 rounded-lg"
            >
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr class="divide-x divide-gray-200">
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bill Number</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bill Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bill Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paid</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Remaining</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bill Image</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($bills as $bill)
                        <tr class="hover:bg-gray-50 divide-x divide-gray-200">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">#{{ $bill->bill_number ?? $bill->id }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $bill->bill_date->format('d M Y') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">PKR {{ number_format($bill->bill_amount, 1) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">PKR {{ number_format($bill->paid_amount ?? 0, 1) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold {{ ($bill->remaining ?? 0) > 0 ? 'text-red-600' : 'text-green-600' }}">
                                PKR {{ number_format($bill->remaining ?? 0, 1) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if(($bill->remaining ?? 0) > 0)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Unpaid</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Paid</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($bill->bill_image)
                                    <button onclick="viewBillImage('{{ asset('storage/' . $bill->bill_image) }}')"
                                            class="text-blue-600 hover:text-blue-900 inline-flex items-center">
                                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                        View Image
                                    </button>
                                @else
                                    <span class="text-gray-400 text-sm">No image</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center space-x-3">
                                    <a href="{{ route('suppliers.bills.edit', [$supplier, $bill]) }}" class="text-green-600 hover:text-green-900" title="Edit Bill">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </a>
                                    <button onclick="printSupplierBillReceipt({{ $bill->id }})"
                                            class="text-blue-600 hover:text-blue-900 inline-flex items-center" title="Print Receipt">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2 2v4h10z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Transactions Section (collapsible) -->
        <div class="mt-6" x-data="{ txOpen: false }">
            <div class="flex items-center gap-3">
                <button
                    type="button"
                    @click="txOpen = !txOpen"
                    class="flex-1 flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-3 text-left shadow-sm hover:bg-gray-50"
                >
                    <div class="flex items-center gap-2">
                        <h3 class="text-lg font-semibold text-gray-900">Transactions</h3>
                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700">
                            {{ isset($transactions) ? $transactions->count() : 0 }}
                        </span>
                    </div>
                    <svg class="h-5 w-5 text-gray-500 transition-transform duration-200" :class="{ 'rotate-180': txOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <a href="{{ route('suppliers.transactions.create', $supplier) }}" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-md text-sm font-medium inline-flex items-center whitespace-nowrap">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Add Transaction
                </a>
            </div>

            <div x-show="txOpen" x-cloak x-transition class="mt-3">
                @if(isset($transactions) && $transactions->count() > 0)
                <div class="overflow-x-auto border border-gray-200 rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr class="divide-x divide-gray-200">
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase font-bold text-gray-900">Remaining</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @php $runningBalance = $balance; @endphp
                            @foreach($transactions as $transaction)
                            <tr class="hover:bg-gray-50 divide-x divide-gray-200">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $transaction->transaction_date->format('d M Y') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($transaction->type === 'credit')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Credit (Owed)</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Debit (Paid)</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold {{ $transaction->type === 'credit' ? 'text-red-600' : 'text-green-600' }}">
                                    PKR {{ number_format($transaction->amount, 1) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $transaction->description ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $transaction->reference_number ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold {{ $runningBalance > 0 ? 'text-red-600' : 'text-green-600' }}">
                                    PKR {{ number_format($runningBalance, 1) }}
                                </td>
                                @php
                                    if ($transaction->type === 'credit') {
                                        $runningBalance -= $transaction->amount;
                                    } else {
                                        $runningBalance += $transaction->amount;
                                    }
                                @endphp
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="{{ route('suppliers.transactions.edit', [$supplier, $transaction]) }}" class="text-blue-600 hover:text-blue-900" title="Edit">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-8 text-gray-500 border border-gray-200 rounded-lg">
                    No transactions found. Add a transaction to get started.
                </div>
                @endif
            </div>
        </div>

        <!-- Ledger Section (same layout as customer detail) -->
        <div class="mt-6">
            <h3 class="text-lg font-semibold mb-4">Ledger</h3>
            @if(isset($ledgerEntries) && count($ledgerEntries['rows']) > 0)
            <div class="overflow-x-auto border border-gray-300 rounded-lg">
                <table class="min-w-full border-collapse">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap border border-gray-300">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap border border-gray-300">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap border border-gray-300">Ref #</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase border border-gray-300">Narration</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase whitespace-nowrap border border-gray-300">Debit</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase whitespace-nowrap border border-gray-300">Credit</th>
                            <th class="px-4 py-3 text-right text-xs font-bold text-gray-900 uppercase whitespace-nowrap border border-gray-300">Balance</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        @foreach($ledgerEntries['rows'] as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 border border-gray-300">
                                {{ $row['date'] ? $row['date']->format('d/m/Y') : '—' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap border border-gray-300">
                                @if($row['type'] === 'Credit')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">Credit</span>
                                @elseif($row['type'] === 'Payment')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Payment</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">{{ $row['type'] }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 border border-gray-300">{{ $row['ref'] }}</td>
                            <td class="px-4 py-3 text-sm text-gray-900 max-w-md border border-gray-300">{{ $row['narration'] }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-medium text-green-600 border border-gray-300">
                                {{ $row['debit'] !== null ? 'PKR ' . number_format($row['debit'], 2) : '' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-medium text-gray-900 border border-gray-300">
                                {{ $row['credit'] !== null ? 'PKR ' . number_format($row['credit'], 2) : '' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-bold border border-gray-300 {{ $row['balance'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                                PKR {{ number_format($row['balance'], 2) }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="4" class="px-4 py-3 text-sm font-bold text-gray-900 text-right border border-gray-300">Total</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-bold text-green-600 border border-gray-300">PKR {{ number_format($ledgerEntries['total_debit'], 2) }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-bold text-gray-900 border border-gray-300">PKR {{ number_format($ledgerEntries['total_credit'], 2) }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-bold border border-gray-300 {{ $ledgerEntries['final_balance'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                                PKR {{ number_format($ledgerEntries['final_balance'], 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @else
            <div class="text-center py-8 text-gray-500 border border-gray-200 rounded-lg">
                No ledger entries found.
            </div>
            @endif
        </div>

        <!-- Products List -->
        @if($supplier->products->count() > 0)
        <div class="mt-6">
            <h3 class="text-lg font-semibold mb-4">Products Supplied</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Purchase Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock Quantity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Value</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($supplier->products as $product)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $product->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $product->sku ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">PKR {{ number_format($product->purchase_price, 1) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($product->stock_quantity) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">PKR {{ number_format($product->purchase_price * $product->stock_quantity, 1) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
    </div>

    <script>
        function viewBillImage(imageUrl) {
            // Create a modal to display the image
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50';
            modal.onclick = function(e) {
                if (e.target === modal) {
                    document.body.removeChild(modal);
                }
            };
            
            modal.innerHTML = `
                <div class="relative max-w-4xl max-h-full p-4">
                    <button onclick="this.closest('.fixed').remove()" 
                            class="absolute top-2 right-2 text-white bg-red-600 hover:bg-red-700 rounded-full p-2 z-10">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                    <img src="${imageUrl}" alt="Bill Image" class="max-w-full max-h-screen rounded-lg shadow-lg">
                </div>
            `;
            
            document.body.appendChild(modal);
        }
        
        async function printSupplierBillReceipt(billId) {
            try {
                if (window.FTReceipt?.requireConfigured) {
                    await window.FTReceipt.requireConfigured();
                }
            } catch (e) {
                return;
            }
            const receiptHeader = (window.FTReceipt && window.FTReceipt.headerHtml)
                ? window.FTReceipt.headerHtml(typeof receiptDocTitle !== 'undefined' ? receiptDocTitle : 'Order Receipt')
                : '';
            const supplierId = {{ $supplier->id }};
            const printWindow = window.open('', '_blank');
            
            // Get CSRF token from meta tag or form
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || 
                             document.querySelector('input[name="_token"]')?.value || '';
            
            fetch(`/suppliers/${supplierId}/bills/${billId}/receipt`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {})
                }
            })
            .then(async response => {
                if (!response.ok) {
                    const text = await response.text();
                    throw new Error(text || 'Failed to load receipt');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    const bill = data.bill;
                    const supplier = data.supplier;
                    const billItems = data.bill_items || [];
                    const paymentHistory = data.payment_history || [];
                    
                    let printContent = `
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <title>Bill Receipt - ${bill.bill_number || bill.id}</title>
                            <style>
                                @media print {
                                    @page { margin: 10mm; }
                                }
                                * {
                                    color: #000 !important;
                                }
                                body { 
                                    font-family: 'Arial', sans-serif; 
                                    padding: 20px; 
                                    max-width: 80mm; 
                                    margin: 0 auto; 
                                    font-size: 12px;
                                    color: #000 !important;
                                }
                                .header { 
                                    text-align: center; 
                                    margin-bottom: 15px; 
                                    border-bottom: 2px solid #000; 
                                    padding-bottom: 10px; 
                                }
                                .header h2 { 
                                    margin: 0; 
                                    font-size: 18px; 
                                    font-weight: bold;
                                }
                                .header p {
                                    margin: 5px 0 0 0;
                                    font-size: 11px;
                                }
                                .business-info {
                                    padding-top: 10px;
                                    font-size: 10px;
                                    color: #000 !important;
                                }
                                .business-service {
                                    font-weight: bold;
                                    margin-bottom: 6px;
                                    font-size: 10px;
                                    color: #000 !important;
                                }
                                .business-service i {
                                    font-style: italic;
                                }
                                .business-contact {
                                    display: flex;
                                    justify-content: space-between;
                                    align-items: flex-start;
                                    margin-top: 6px;
                                    font-size: 7px;
                                    color: #000 !important;
                                }
                                .business-contact-left {
                                    text-align: left;
                                }
                                .business-contact-right {
                                    text-align: right;
                                }
                                .bill-info { 
                                    margin-bottom: 15px; 
                                    font-size: 11px;
                                }
                                .bill-info div { 
                                    display: flex; 
                                    justify-content: space-between; 
                                    margin-bottom: 3px;
                                }
                                .amount-section {
                                    margin-top: 15px;
                                    padding-top: 10px;
                                    border-top: 2px solid #000;
                                }
                                .amount-row {
                                    display: flex;
                                    justify-content: space-between;
                                    margin-bottom: 5px;
                                    font-size: 11px;
                                }
                                .total-row {
                                    display: flex;
                                    justify-content: space-between;
                                    font-weight: bold;
                                    font-size: 14px;
                                    margin-top: 10px;
                                    padding-top: 10px;
                                    border-top: 1px solid #ddd;
                                }
                                .payment-history {
                                    margin-top: 15px;
                                    padding-top: 10px;
                                    border-top: 1px solid #ddd;
                                }
                                .payment-history-title {
                                    font-weight: bold;
                                    font-size: 12px;
                                    margin-bottom: 8px;
                                    text-align: center;
                                }
                                .payment-item {
                                    display: flex;
                                    justify-content: space-between;
                                    margin-bottom: 5px;
                                    font-size: 10px;
                                    padding: 3px 0;
                                    border-bottom: 1px dotted #ddd;
                                }
                                .payment-date {
                                    color: #000 !important;
                                }
                                .payment-amount {
                                    color: #000 !important;
                                    font-weight: bold;
                                }
                                .products-section {
                                    margin-top: 15px;
                                    padding-top: 10px;
                                    border-top: 1px solid #000;
                                }
                                .products-title {
                                    font-weight: bold;
                                    font-size: 12px;
                                    margin-bottom: 8px;
                                    text-align: center;
                                    color: #000 !important;
                                }
                                .products-table {
                                    width: 100%;
                                    font-size: 10px;
                                    border-collapse: collapse;
                                    margin-bottom: 10px;
                                }
                                .products-table th {
                                    border-bottom: 1px solid #000;
                                    padding: 5px 2px;
                                    text-align: left;
                                    font-weight: bold;
                                    color: #000 !important;
                                }
                                .products-table td {
                                    padding: 4px 2px;
                                    border-bottom: 1px dotted #ddd;
                                    color: #000 !important;
                                }
                                .products-table .text-right {
                                    text-align: right;
                                }
                                .products-table .text-center {
                                    text-align: center;
                                }
                                .product-name {
                                    font-weight: bold;
                                    color: #000 !important;
                                }
                                .product-sku {
                                    font-size: 9px;
                                    color: #000 !important;
                                }
                                .footer { 
                                    text-align: center; 
                                    margin-top: 20px; 
                                    padding-top: 15px; 
                                    border-top: 2px solid #000; 
                                    font-size: 10px;
                                }
                            </style>
                        </head>
                        <body>
                            ${(window.FTReceipt && window.FTReceipt.headerHtml) ? window.FTReceipt.headerHtml('Supplier Bill Receipt') : ''}
                            <div class="bill-info">
                                <div>
                                    <span>Bill Number:</span>
                                    <span><strong>#${bill.bill_number || bill.id}</strong></span>
                                </div>
                                <div>
                                    <span>Date:</span>
                                    <span>${new Date(bill.bill_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</span>
                                </div>
                                <div>
                                    <span>Supplier:</span>
                                    <span>${supplier.name}</span>
                                </div>
                                ${supplier.company_name ? `
                                <div>
                                    <span>Company:</span>
                                    <span>${supplier.company_name}</span>
                                </div>
                                ` : ''}
                                ${bill.reference_number ? `
                                <div>
                                    <span>Reference:</span>
                                    <span>${bill.reference_number}</span>
                                </div>
                                ` : ''}
                                ${bill.description ? `
                                <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #ddd;">
                                    <div style="font-weight: bold; margin-bottom: 5px; color: #000;">Description:</div>
                                    <div style="color: #000;">${bill.description}</div>
                                </div>
                                ` : ''}
                            </div>
                            ${billItems.length > 0 ? `
                            <div class="products-section">
                                <div class="products-title">Products</div>
                                <table class="products-table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th class="text-right">Qty</th>
                                            <th class="text-right">Price</th>
                                            <th class="text-right">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${billItems.map(item => `
                                            <tr>
                                                <td>
                                                    <div class="product-name">${item.product_name}</div>
                                                    ${item.product_sku ? `<div class="product-sku">SKU: ${item.product_sku}</div>` : ''}
                                                </td>
                                                <td class="text-right">${parseFloat(item.quantity).toFixed(2)}</td>
                                                <td class="text-right">PKR ${parseFloat(item.unit_price).toFixed(2)}</td>
                                                <td class="text-right"><strong>PKR ${parseFloat(item.total).toFixed(2)}</strong></td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                            ` : ''}
                            <div class="amount-section">
                                <div class="amount-row">
                                    <span>Bill Amount:</span>
                                    <span style="color: #000 !important;">PKR ${parseFloat(bill.bill_amount).toFixed(2)}</span>
                                </div>
                                <div class="amount-row">
                                    <span>Paid Amount:</span>
                                    <span style="color: #000 !important;">PKR ${parseFloat(bill.paid_amount || 0).toFixed(2)}</span>
                                </div>
                                <div class="amount-row">
                                    <span>Remaining:</span>
                                    <span style="color: #000 !important; font-weight: bold;">
                                        PKR ${parseFloat(bill.remaining || 0).toFixed(2)}
                                    </span>
                                </div>
                                <div class="total-row">
                                    <span>Status:</span>
                                    <span style="color: #000 !important;">
                                        ${(bill.remaining || 0) > 0 ? 'Unpaid' : 'Paid'}
                                    </span>
                                </div>
                            </div>
                            ${paymentHistory.length > 0 ? `
                            <div class="payment-history">
                                <div class="payment-history-title">Payment History</div>
                                ${paymentHistory.map(payment => `
                                    <div class="payment-item">
                                        <div>
                                            <span class="payment-date">${new Date(payment.date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })}</span>
                                            ${payment.reference_number ? `<div style="font-size: 9px; color: #000 !important;">Ref: ${payment.reference_number}</div>` : ''}
                                        </div>
                                        <span class="payment-amount">PKR ${parseFloat(payment.amount).toFixed(2)}</span>
                                    </div>
                                `).join('')}
                            </div>
                            ` : ''}
                            <div class="footer">
                                <p>Thank you for your business!</p>
                                <p>This is a computer-generated receipt.</p>
                            </div>
                        </body>
                        </html>
                    `;
                    
                    printWindow.document.write(printContent);
                    printWindow.document.close();
                    printWindow.print();
                } else {
                    alert('Error: ' + (data.message || 'Failed to load receipt'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading receipt. Please try again.');
            });
        }
    </script>
</x-app-layout>



