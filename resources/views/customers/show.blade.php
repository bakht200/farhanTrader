<x-app-layout>
    <x-slot name="header">
        Customer Details
    </x-slot>

    <style>[x-cloak] { display: none !important; }</style>

    <!-- Breadcrumb -->
    <div class="mb-4">
        <nav class="text-sm text-gray-600">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-900">Dashboard</a>
            <span class="mx-2">></span>
            <a href="{{ route('customers.index') }}" class="hover:text-gray-900">Customer</a>
            <span class="mx-2">></span>
            <span class="text-gray-900 font-medium">{{ $customer->name }}</span>
        </nav>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ $customer->name }}</h2>
                <p class="text-sm text-gray-500">Customer ID: {{ $customer->customer_id ?? 'CN-' . str_pad($customer->id, 3, '0', STR_PAD_LEFT) }}</p>
            </div>
            <div class="flex space-x-2">
                <button onclick="openDayWisePrintModal()" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md inline-flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                    </svg>
                    Print Day-wise Bills
                </button>
                <button onclick="openBillsSummaryModal()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md inline-flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2m3 2h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                    </svg>
                    Print Bills Summary
                </button>
                <a href="{{ route('customers.edit', $customer) }}" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-md">
                    Edit Customer
                </a>
                <a href="{{ route('customers.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-md">
                    Back to List
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h3 class="text-lg font-semibold mb-4">Customer Information</h3>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Name</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $customer->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Customer type</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $customer->customer_type ? $customer->customer_type : 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Email</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $customer->email ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Phone</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $customer->phone ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Address</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $customer->address ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">City</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $customer->city ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">State</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $customer->state ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Country</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $customer->country ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Postal Code</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $customer->postal_code ?? 'N/A' }}</dd>
                    </div>
                </dl>
            </div>

            <div>
                <h3 class="text-lg font-semibold mb-4">Customer Wallet</h3>
                <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-lg p-4 space-y-4">
                    <div class="border-b border-orange-200 pb-3">
                        <p class="text-sm text-gray-600 mb-1">Total Paid</p>
                        <p class="text-2xl font-bold text-green-600">PKR {{ number_format($customer->paid_amount ?? 0, 1) }}</p>
                    </div>
                    <div class="border-b border-orange-200 pb-3">
                        <p class="text-sm text-gray-600 mb-1">Total</p>
                        <p class="text-2xl font-bold text-gray-700">PKR {{ number_format($customer->total_price ?? 0, 1) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Remaining</p>
                        <p class="text-2xl font-bold {{ ($customer->unpaid_amount ?? 0) > 0 ? 'text-red-600' : 'text-green-600' }}">
                            PKR {{ number_format($customer->unpaid_amount ?? 0, 1) }}
                        </p>
                    </div>
                    <div class="pt-2 flex items-center justify-between">
                        @if(($customer->unpaid_amount ?? 0) > 0)
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
                        <span class="text-xs text-gray-600">Total Sales: {{ $customerBills->count() }}</span>
                    </div>
                </div>
                <div class="mt-3 text-sm text-gray-600">
                    Credit Limit: <span class="font-medium text-gray-900">PKR {{ number_format($customer->credit_limit, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Bills Section (collapsible, oldest first like ledger) -->
        @if(isset($customerBills) && $customerBills->count() > 0)
        <div class="mt-6" x-data="{ billsOpen: false }">
            <button
                type="button"
                @click="billsOpen = !billsOpen"
                class="w-full flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-3 text-left shadow-sm hover:bg-gray-50"
            >
                <div class="flex items-center gap-2">
                    <h3 class="text-lg font-semibold text-gray-900">Bills</h3>
                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700">
                        {{ $customerBills->count() }}
                    </span>
                </div>
                <svg
                    class="h-5 w-5 text-gray-500 transition-transform duration-200"
                    :class="{ 'rotate-180': billsOpen }"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            <div
                x-show="billsOpen"
                x-cloak
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-1"
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($customerBills as $bill)
                        <tr class="hover:bg-gray-50 divide-x divide-gray-200">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $bill->sale_number }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $bill->sale_date->format('d M Y') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">PKR {{ number_format($bill->total_amount, 1) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">PKR {{ number_format($bill->bill_paid_amount ?? 0, 1) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold {{ ($bill->bill_remaining ?? 0) > 0 ? 'text-red-600' : 'text-green-600' }}">
                                PKR {{ number_format($bill->bill_remaining ?? 0, 1) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if(($bill->bill_remaining ?? 0) > 0)
                                    @if(($bill->bill_paid_amount ?? 0) > 0)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Partial</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Unpaid</span>
                                    @endif
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Paid</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center space-x-3">
                                    <a href="{{ route('sales.show', $bill) }}" class="text-blue-600 hover:text-blue-900 inline-flex items-center" title="View sale">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Ledger Section -->
        <div class="mt-6">
            <h3 class="text-lg font-semibold mb-4">Ledger</h3>
            @if(count($ledgerEntries['rows']) > 0)
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
                                @if($row['type'] === 'Sale')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">Sale</span>
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
            <div class="text-center py-8 text-gray-500">
                No ledger entries found.
            </div>
            @endif
        </div>
    </div>

    <!-- Day-wise Print Modal -->
    <div id="day-wise-print-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Print Day-wise Bills</h3>
                    <button onclick="closeDayWisePrintModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="mb-4 space-y-4">
                    <div>
                        <label for="print-start-date" class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                        <input type="date" id="print-start-date" value="{{ date('Y-m-d') }}" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                    </div>
                    <div>
                        <label for="print-end-date" class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                        <input type="date" id="print-end-date" value="{{ date('Y-m-d') }}" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                    </div>
                </div>
                <div class="flex justify-end space-x-3">
                    <button onclick="closeDayWisePrintModal()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-md">
                        Cancel
                    </button>
                    <button onclick="printDayWiseBills({{ $customer->id }})" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md">
                        Print Bills
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- Bills Summary Modal -->
    <div id="bills-summary-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Print Bills Summary</h3>
                    <button onclick="closeBillsSummaryModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="mb-4 space-y-4">
                    <div>
                        <label for="summary-start-date" class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                        <input type="date" id="summary-start-date" value="{{ date('Y-m-d') }}" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                    </div>
                    <div>
                        <label for="summary-end-date" class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                        <input type="date" id="summary-end-date" value="{{ date('Y-m-d') }}" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                    </div>
                </div>
                <div class="flex justify-end space-x-3">
                    <button onclick="closeBillsSummaryModal()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-md">
                        Cancel
                    </button>
                    <button onclick="printBillsSummary({{ $customer->id }})" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md">
                        Print Summary
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openDayWisePrintModal() {
            document.getElementById('day-wise-print-modal').classList.remove('hidden');
        }

        function closeDayWisePrintModal() {
            document.getElementById('day-wise-print-modal').classList.add('hidden');
        }

        function openBillsSummaryModal() {
            document.getElementById('bills-summary-modal').classList.remove('hidden');
        }

        function closeBillsSummaryModal() {
            document.getElementById('bills-summary-modal').classList.add('hidden');
        }

        async function printDayWiseBills(customerId) {
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
            const startDate = document.getElementById('print-start-date').value;
            const endDate = document.getElementById('print-end-date').value;
            if (!startDate || !endDate) {
                alert('Please select both start and end dates');
                return;
            }

            if (new Date(startDate) > new Date(endDate)) {
                alert('Start date cannot be greater than end date');
                return;
            }

            const printWindow = window.open('', '_blank');
            if (!printWindow) {
                alert('Please allow popups for this website');
                return;
            }
            printWindow.document.write('<html><head><title>Loading...</title></head><body><p>Loading bills... Please wait.</p></body></html>');

            fetch(`/customers/${customerId}/day-wise-bills?start_date=${startDate}&end_date=${endDate}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert('Error: ' + data.error);
                    if (printWindow) printWindow.close();
                    return;
                }

                const sales = data.sales || [];
                const customer = data.customer || {};
                const startDate = new Date(data.start_date).toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' });
                const endDate = new Date(data.end_date).toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' });
                const dateRange = startDate === endDate ? startDate : `${startDate} to ${endDate}`;

                let printContent = `
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>Day-wise Bills - ${customer.name}</title>
                        <style>
                            @media print { 
                                @page { margin: 5mm; }
                                * { color: #000 !important; }
                            }
                            * { color: #000 !important; }
                            body { font-family: 'Arial', sans-serif; padding: 8px; max-width: 80mm; margin: 0 auto; font-size: 10px; }
                            .header { text-align: center; margin-bottom: 8px; border-bottom: 1px solid #000; padding-bottom: 6px; }
                            .header h2 { margin: 0; font-size: 14px; font-weight: bold; }
                            .header p { margin: 4px 0 0 0; font-size: 9px; }
                            .customer-info { margin-bottom: 8px; font-size: 9px; border-bottom: 1px solid #ddd; padding-bottom: 6px; }
                            .customer-info div { display: flex; justify-content: space-between; margin-bottom: 2px; }
                            .date-info { text-align: center; font-weight: bold; margin-bottom: 10px; font-size: 11px; }
                            .bill-section { margin-bottom: 20px; border: 1px solid #000; padding: 8px; page-break-inside: avoid; }
                            .bill-header { font-weight: bold; font-size: 11px; margin-bottom: 6px; border-bottom: 1px solid #000; padding-bottom: 4px; }
                            table { width: 100%; border-collapse: collapse; margin-bottom: 6px; font-size: 9px; }
                            th, td { padding: 3px 1px; text-align: left; border-bottom: 1px solid #ddd; }
                            th { font-weight: bold; border-bottom: 1px solid #000; }
                            td:nth-child(2), td:nth-child(3), td:nth-child(4) { text-align: right; }
                            .total-section { text-align: right; font-weight: bold; font-size: 10px; margin-top: 6px; padding-top: 4px; border-top: 1px solid #000; }
                            .summary-section { margin-top: 15px; border: 2px solid #000; padding: 10px; background-color: #f0f0f0; }
                            .summary-section h3 { text-align: center; font-size: 12px; font-weight: bold; margin-bottom: 8px; border-bottom: 1px solid #000; padding-bottom: 4px; }
                            .summary-row { display: flex; justify-content: space-between; margin-bottom: 4px; font-size: 10px; }
                            .footer { text-align: center; margin-top: 10px; padding-top: 8px; border-top: 1px solid #000; font-size: 8px; }
                        </style>
                    </head>
                    <body>
                        <div class="header">
                            <h2>${(window.FTReceipt && window.FTReceipt.displayTitle) ? window.FTReceipt.displayTitle() : 'Receipt'}</h2>
                            <p>Day-wise Bills Report</p>
                        </div>
                        <div class="customer-info">
                            <div><span>Customer:</span><span><strong>${customer.name || 'N/A'}</strong></span></div>
                            <div><span>Customer ID:</span><span>${customer.customer_id || 'N/A'}</span></div>
                            <div><span>Phone:</span><span>${customer.phone || 'N/A'}</span></div>
                        </div>
                        <div class="date-info">
                            Date Range: ${dateRange}
                        </div>
                `;

                let dayTotal = 0;
                let dayPaid = 0;
                let billCount = 0;

                if (sales.length === 0) {
                    printContent += `
                        <div style="text-align: center; padding: 20px; font-size: 11px;">
                            No bills found for this date range.
                        </div>
                    `;
                } else {
                    sales.forEach((sale, index) => {
                        const currentSaleTotal = parseFloat(sale.total_amount || 0);
                        const previousBalance = parseFloat(sale.previous_balance || 0);
                        const grandTotal = currentSaleTotal + previousBalance;
                        const paidAmount = parseFloat(sale.paid_amount || 0);
                        const regularPaidAmount = parseFloat(sale.regular_paid_amount || (paidAmount - parseFloat(sale.adj_paid_amount || 0)));
                        const adjPaidAmount = parseFloat(sale.adj_paid_amount || 0);
                        const adjBillNumber = sale.adj_bill_number || null;
                        // Calculate actual payment for this sale (excluding ADJ payments which are for previous balances)
                        const actualSalePayment = regularPaidAmount;
                        const balance = grandTotal - paidAmount;
                        const previousBalancePayment = parseFloat(sale.previous_balance_payment || 0);
                        
                        // Exclude ADJ bills from totals calculation (they are adjustment records)
                        // ADJ bills are already excluded from the backend query, but check just in case
                        const isAdjustment = sale.sale_number && sale.sale_number.startsWith('ADJ-');
                        // Check if this is a Previous Balance bill (PB- prefix or notes contains "Previous Balance")
                        const isPreviousBalance = sale.sale_number && (sale.sale_number.startsWith('PB-') || (sale.notes && sale.notes.includes('Previous Balance')));
                        
                        if (!isAdjustment) {
                            // Add sale amount (including PB bills - they are regular sales)
                            dayTotal += currentSaleTotal;
                            // Calculate actual payment for this sale (excluding ADJ payments)
                            // ADJ payments are extra payments applied to previous balances, not to current sale amounts
                            const actualSalePayment = paidAmount - adjPaidAmount;
                            dayPaid += actualSalePayment;
                            billCount++;
                        }

                        // Format sale date (could be datetime or date-only)
                        let saleDate = 'N/A';
                        if (sale.sale_date) {
                            // Check if already formatted as Y-m-d h:i A (datetime)
                            if (typeof sale.sale_date === 'string' && sale.sale_date.match(/^\d{4}-\d{2}-\d{2} \d{2}:\d{2} (AM|PM)$/)) {
                                saleDate = sale.sale_date;
                            } else {
                                // Format as date-only (Y-m-d)
                                const saleDateObj = new Date(sale.sale_date);
                                if (!isNaN(saleDateObj.getTime())) {
                                    const year = saleDateObj.getFullYear();
                                    const month = String(saleDateObj.getMonth() + 1).padStart(2, '0');
                                    const day = String(saleDateObj.getDate()).padStart(2, '0');
                                    saleDate = `${year}-${month}-${day}`;
                                }
                            }
                        }

                        printContent += `
                            <div class="bill-section">
                                <div class="bill-header">
                                    Bill #${index + 1}: ${sale.sale_number || 'N/A'}
                                    ${isAdjustment ? '<span style="margin-left: 8px; padding: 2px 6px; background-color: #dbeafe; color: #1e40af; border-radius: 4px; font-size: 9px; font-weight: bold;">ADJUSTMENT BILL</span>' : ''}
                                </div>
                                <div style="font-size: 8px; margin-bottom: 4px; color: #666;">
                                    Date & Time: ${saleDate}
                                    ${isAdjustment ? '<div style="margin-top: 2px; color: #1e40af; font-weight: bold;">Payment applied to previous sales</div>' : ''}
                                </div>
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th style="text-align: right;">Qty</th>
                                            <th style="text-align: right;">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                        `;

                        if (sale.items && sale.items.length > 0) {
                            sale.items.forEach(item => {
                                const itemName = item.product_name || 'N/A';
                                const quantity = parseFloat(item.quantity || 0);
                                const unitPrice = parseFloat(item.unit_price || 0);
                                const discount = parseFloat(item.discount || 0);
                                const itemTotal = (quantity * unitPrice) - discount;
                                const unitName = item.unit_name || item.unit_short_name || 'Pcs';

                                printContent += `
                                    <tr>
                                        <td>${itemName}</td>
                                        <td style="text-align: right;">${quantity.toFixed(1)} ${unitName}</td>
                                        <td style="text-align: right;">PKR ${itemTotal.toFixed(1)}</td>
                                    </tr>
                                `;
                            });
                        } else if (isAdjustment) {
                            // Show message for adjustment bills with no items
                            printContent += `
                                    <tr>
                                        <td colspan="4" style="text-align: center; padding: 15px; color: #1e40af; font-weight: bold; font-size: 10px;">
                                            This is a remaining payment applied to previous bills
                                        </td>
                                    </tr>
                            `;
                        }

                        printContent += `
                                    </tbody>
                                </table>
                                <div class="total-section">
                                    ${isAdjustment ? '<p style="font-size: 9px; margin-bottom: 4px; padding: 5px; background-color: #dbeafe; color: #1e40af; border-radius: 3px; text-align: center; font-weight: bold;">Remaining Payment of Old Bills</p>' : ''}
                                    <p style="font-size: 9px; margin-bottom: 2px; display: flex; justify-content: space-between;">
                                        <span>Subtotal:</span>
                                        <span>PKR ${currentSaleTotal.toFixed(1)}</span>
                                    </p>
                                    ${previousBalance > 0 ? '<p style="font-size: 9px; margin-bottom: 2px; display: flex; justify-content: space-between;"><span>Previous Balance:</span><span>PKR ' + previousBalance.toFixed(1) + '</span></p>' : ''}
                                    <p style="border-top: 1px solid #ddd; margin: 3px 0; padding-top: 3px;"></p>
                                    <p style="font-size: 11px; margin-bottom: 2px; display: flex; justify-content: space-between; font-weight: bold;">
                                        <span>Total Payable:</span>
                                        <span>PKR ${grandTotal.toFixed(1)}</span>
                                    </p>
                                    ${regularPaidAmount > 0 ? '<p style="font-size: 9px; margin-bottom: 2px; display: flex; justify-content: space-between;"><span>Amount Paid:</span><span>PKR ' + regularPaidAmount.toFixed(1) + '</span></p>' : ''}
                                    ${adjPaidAmount > 0 && adjBillNumber ? '<p style="font-size: 9px; margin-bottom: 2px; display: flex; justify-content: space-between;"><span>Previous Balance Paid (' + adjBillNumber + '):</span><span>PKR ' + adjPaidAmount.toFixed(1) + '</span></p>' : ''}
                                    ${paidAmount > 0 && (regularPaidAmount > 0 || adjPaidAmount > 0) ? '<p style="font-size: 9px; margin-bottom: 2px; display: flex; justify-content: space-between; font-weight: bold; border-top: 1px solid #ddd; padding-top: 3px; margin-top: 3px;"><span>Total Paid:</span><span>PKR ' + paidAmount.toFixed(1) + '</span></p>' : ''}
                                    ${balance > 0 ? '<p style="border-top: 1px solid #ddd; margin: 3px 0; padding-top: 3px;"></p><p style="font-size: 9px; margin-top: 2px; display: flex; justify-content: space-between; font-weight: bold; color: #000;"><span>Remaining Balance:</span><span>PKR ' + balance.toFixed(1) + '</span></p>' : ''}
                                </div>
                            </div>
                        `;
                    });

                    printContent += `
                        <div class="summary-section">
                            <h3>DAY SUMMARY</h3>
                            <div class="summary-row">
                                <span>Total Bills:</span>
                                <span><strong>${billCount}</strong></span>
                            </div>
                            <div class="summary-row">
                                <span>Total Amount:</span>
                                <span><strong>PKR ${dayTotal.toFixed(1)}</strong></span>
                            </div>
                            <div class="summary-row">
                                <span>Total Paid:</span>
                                <span><strong>PKR ${dayPaid.toFixed(1)}</strong></span>
                            </div>
                            <div class="summary-row">
                                <span>Total Remaining:</span>
                                <span><strong>PKR ${(dayTotal - dayPaid).toFixed(1)}</strong></span>
                            </div>
                        </div>
                    `;
                }

                printContent += `
                        <div class="footer">
                            <p>Thank you for your business!</p>
                            <p>This is a computer-generated report.</p>
                        </div>
                    </body>
                    </html>
                `;

                printWindow.document.open();
                printWindow.document.write(printContent);
                printWindow.document.close();
                printWindow.print();
                closeDayWisePrintModal();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading bills: ' + error.message);
            });
        }
        async function printBillsSummary(customerId) {
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
            const startDate = document.getElementById('summary-start-date').value;
            const endDate = document.getElementById('summary-end-date').value;
            if (!startDate || !endDate) {
                alert('Please select both start and end dates');
                return;
            }

            if (new Date(startDate) > new Date(endDate)) {
                alert('Start date cannot be greater than end date');
                return;
            }

            const printWindow = window.open('', '_blank');
            if (!printWindow) {
                alert('Please allow popups for this website');
                return;
            }
            printWindow.document.write('<html><head><title>Loading...</title></head><body><p>Loading summary... Please wait.</p></body></html>');

            fetch(`/customers/${customerId}/day-wise-bills?start_date=${startDate}&end_date=${endDate}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert('Error: ' + data.error);
                    if (printWindow) printWindow.close();
                    return;
                }

                const sales = data.sales || [];
                const customer = data.customer || {};
                const startDateStr = new Date(data.start_date).toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' });
                const endDateStr = new Date(data.end_date).toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' });
                const dateRange = startDateStr === endDateStr ? startDateStr : `${startDateStr} to ${endDateStr}`;

                let printContent = `
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>Bills Summary - ${customer.name}</title>
                        <style>
                            @media print { 
                                @page { margin: 5mm; }
                                * { color: #000 !important; }
                            }
                            * { color: #000 !important; }
                            body { font-family: 'Arial', sans-serif; padding: 10px; max-width: 80mm; margin: 0 auto; font-size: 11px; line-height: 1.4; }
                            .header { text-align: center; margin-bottom: 12px; border-bottom: 2px solid #000; padding-bottom: 8px; }
                            .header h2 { margin: 0; font-size: 16px; font-weight: bold; }
                            .header p { margin: 4px 0 0 0; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; }
                            .customer-info { margin-bottom: 10px; font-size: 10px; }
                            .customer-info div { display: flex; justify-content: space-between; margin-bottom: 2px; }
                            .date-range { text-align: center; margin-bottom: 15px; font-weight: bold; font-size: 11px; padding: 5px; background: #eee; border-radius: 4px; }
                            table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
                            th { text-align: left; border-bottom: 1px solid #000; padding: 6px 2px; font-size: 10px; text-transform: uppercase; }
                            td { padding: 8px 2px; border-bottom: 1px solid #eee; font-size: 11px; }
                            .text-right { text-align: right; }
                            .grand-total { border-top: 2px solid #000; margin-top: 10px; padding-top: 10px; }
                            .total-row { display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 12px; font-weight: bold; }
                            .footer { text-align: center; margin-top: 30px; border-top: 1px solid #ddd; padding-top: 10px; font-size: 9px; color: #666; }
                        </style>
                    </head>
                    <body>
                        <div class="header">
                            <h2>${(window.FTReceipt && window.FTReceipt.displayTitle) ? window.FTReceipt.displayTitle() : 'Receipt'}</h2>
                            <p>Bills Summary Report</p>
                        </div>
                        <div class="customer-info">
                            <div><span><strong>Customer:</strong></span><span>${customer.name || 'N/A'}</span></div>
                            <div><span><strong>ID:</strong></span><span>${customer.customer_id || 'N/A'}</span></div>
                            <div><span><strong>Phone:</strong></span><span>${customer.phone || 'N/A'}</span></div>
                        </div>
                        <div class="date-range">
                            ${dateRange}
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Bill #</th>
                                    <th class="text-right">Amount</th>
                                    <th class="text-right">Paid</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                let totalSummaryAmount = 0;
                let totalSummaryPaid = 0;
                let billCount = 0;

                if (sales.length === 0) {
                    printContent += `
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 20px;">No bills found for this period.</td>
                        </tr>
                    `;
                } else {
                    sales.forEach(sale => {
                        const amount = parseFloat(sale.total_amount || 0);
                        const paidAmount = parseFloat(sale.paid_amount || 0);
                        const adjPaidAmount = parseFloat(sale.adj_paid_amount || 0);
                        // Consistent with printDayWiseBills: total paid for this specific sale
                        const actualPayment = paidAmount - adjPaidAmount;
                        
                        totalSummaryAmount += amount;
                        totalSummaryPaid += actualPayment;
                        billCount++;

                        let saleDate = 'N/A';
                        if (sale.sale_date) {
                            const d = new Date(sale.sale_date);
                            if (!isNaN(d.getTime())) {
                                saleDate = d.toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: '2-digit' });
                            }
                        }

                        printContent += `
                            <tr>
                                <td>${saleDate}</td>
                                <td>${sale.sale_number || 'N/A'}</td>
                                <td class="text-right">PKR ${amount.toLocaleString('en-US', { minimumFractionDigits: 1, maximumFractionDigits: 1 })}</td>
                                <td class="text-right">PKR ${actualPayment.toLocaleString('en-US', { minimumFractionDigits: 1, maximumFractionDigits: 1 })}</td>
                            </tr>
                        `;
                    });
                }

                const totalRemaining = totalSummaryAmount - totalSummaryPaid;

                printContent += `
                            </tbody>
                        </table>
                        <div class="grand-total">
                            <div style="text-align: center; font-weight: bold; font-size: 13px; margin-bottom: 8px; border-bottom: 1px solid #000; padding-bottom: 4px;">DAY SUMMARY</div>
                            <div class="total-row">
                                <span>Total Bills:</span>
                                <span>${billCount}</span>
                            </div>
                            <div class="total-row">
                                <span>Total Amount:</span>
                                <span>PKR ${totalSummaryAmount.toLocaleString('en-US', { minimumFractionDigits: 1, maximumFractionDigits: 1 })}</span>
                            </div>
                            <div class="total-row">
                                <span>Total Paid:</span>
                                <span>PKR ${totalSummaryPaid.toLocaleString('en-US', { minimumFractionDigits: 1, maximumFractionDigits: 1 })}</span>
                            </div>
                            <div class="total-row" style="font-size: 14px; margin-top: 5px; border-top: 1px dashed #000; padding-top: 5px;">
                                <span>Total Remaining:</span>
                                <span>PKR ${totalRemaining.toLocaleString('en-US', { minimumFractionDigits: 1, maximumFractionDigits: 1 })}</span>
                            </div>
                        </div>
                        <div class="footer">
                            <p>End of Summary</p>
                            <p>Generated on: ${new Date().toLocaleString()}</p>
                        </div>
                    </body>
                    </html>
                `;

                printWindow.document.open();
                printWindow.document.write(printContent);
                printWindow.document.close();
                printWindow.print();
                closeBillsSummaryModal();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading bills summary: ' + error.message);
                if (printWindow) printWindow.close();
            });
        }
    </script>
</x-app-layout>


