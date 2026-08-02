<x-app-layout>
    <x-slot name="header">
        Customer Details
    </x-slot>

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
                        <span class="text-xs text-gray-600">Total Sales: {{ $customer->sales->count() }}</span>
                    </div>
                </div>
                <div class="mt-3 text-sm text-gray-600">
                    Credit Limit: <span class="font-medium text-gray-900">PKR {{ number_format($customer->credit_limit, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Recent Sales -->
        @if($customer->sales->count() > 0)
        <div class="mt-6">
            <h3 class="text-lg font-semibold mb-4">All Sales</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sale Number</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sale Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Payable</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paid</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Remaining Balance</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($customer->sales as $sale)
                        @php
                            $hasAdjBill = isset($sale->adj_bill_number);
                            $dbPaid = (float) ($sale->db_paid_amount ?? $sale->paid_amount ?? 0);
                            $prevBal = (float) ($sale->invoice_previous_balance ?? 0);
                            $totalPayable = (float) ($sale->total_payable ?? $sale->total_amount);
                            $remainingDue = (float) ($sale->remaining_balance_due ?? max(0, $totalPayable - $dbPaid));
                            $mergedPaidDisplay = (float) ($sale->paid_amount ?? 0);
                            $adjLinkedTotal = (float) ($sale->adj_paid_amount ?? 0);
                            $adjOnThisBill = round(max(0, $mergedPaidDisplay - $dbPaid), 2);
                            $adjTowardOlder = round(max(0, $adjLinkedTotal - $adjOnThisBill), 2);
                            $showAdjBreakdown = $hasAdjBill && $adjLinkedTotal > 0.005;
                            $adjLogByParent = $adjAllocationsFromLogs ?? [];
                            $adjLines = $adjLogByParent[$sale->sale_number] ?? [];
                            $adjLinesSum = collect($adjLines)->sum('amount');
                        @endphp
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $sale->sale_number }}
                                @if($hasAdjBill)
                                    <div class="mt-1">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800" title="Adjustment {{ $sale->adj_bill_number }} — extra from this checkout; see Paid column for how much went to older bills vs this bill">
                                            + ADJ
                                        </span>
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $sale->sale_date->format('Y-m-d') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">PKR {{ number_format($sale->total_amount, 2) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <div>PKR {{ number_format($totalPayable, 2) }}</div>
                                @if($prevBal > 0)
                                    <div class="text-xs font-normal text-gray-500 mt-0.5">incl. prev. PKR {{ number_format($prevBal, 2) }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 align-top">
                                @if($showAdjBreakdown)
                                    <div x-data="{ adjOpen: false }" class="max-w-xs">
                                        <div class="inline-flex items-center gap-1 flex-wrap">
                                            <span>PKR {{ number_format($mergedPaidDisplay, 2) }}</span>
                                            <button
                                                type="button"
                                                @click="adjOpen = !adjOpen"
                                                :title="adjOpen ? 'Hide ADJ details' : 'Show ADJ details'"
                                                :aria-expanded="adjOpen"
                                                class="inline-flex shrink-0 rounded p-0.5 text-blue-600 hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-1"
                                            >
                                                <span class="sr-only">Toggle ADJ breakdown</span>
                                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                                </svg>
                                            </button>
                                        </div>
                                        <div
                                            x-show="adjOpen"
                                            x-cloak
                                            x-transition
                                            class="mt-2 space-y-0.5 border-l-2 border-blue-200 pl-2 text-xs text-gray-500"
                                        >
                                            @if($adjTowardOlder > 0.005)
                                                <div class="font-medium text-gray-700">Toward older balance: PKR {{ number_format($adjTowardOlder, 2) }}</div>
                                                @if(count($adjLines) > 0)
                                                    <ul class="mt-1 list-none space-y-0.5 pl-0 text-[11px] text-gray-600">
                                                        @foreach($adjLines as $line)
                                                            <li>→ {{ $line['to'] }}: PKR {{ number_format($line['amount'], 2) }}</li>
                                                        @endforeach
                                                    </ul>
                                                    @if(abs($adjLinesSum - $adjTowardOlder) > 0.02)
                                                        <p class="mt-1 text-[11px] text-amber-700">Log total PKR {{ number_format($adjLinesSum, 2) }} vs ADJ PKR {{ number_format($adjTowardOlder, 2) }} — data mismatch; check payment logs.</p>
                                                    @endif
                                                @endif
                                            @endif
                                            @if($adjOnThisBill > 0.005)
                                                <div class="text-gray-700">On this bill (ADJ): PKR {{ number_format($adjOnThisBill, 2) }}</div>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    PKR {{ number_format($mergedPaidDisplay, 2) }}
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold {{ $remainingDue > 0 ? 'text-red-600' : 'text-green-600' }}">PKR {{ number_format($remainingDue, 2) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($sale->payment_status == 'paid')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Paid</span>
                                @elseif($sale->payment_status == 'partial')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Partial</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Pending</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Bills Section (same layout as Supplier Details bills) -->
        @if(isset($customerBills) && $customerBills->count() > 0)
        <div class="mt-6">
            <h3 class="text-lg font-semibold mb-4">Bills</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
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
                        <tr>
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

        <!-- Payment & Invoice Logs -->
        <div class="mt-6">
            <h3 class="text-lg font-semibold mb-4">Payment & Invoice Logs</h3>
            @if($paymentLogs && $paymentLogs->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date & Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Comment</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($paymentLogs as $log)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $log->created_at->format('Y-m-d h:i A') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($log->log_type == 'payment')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Payment</span>
                                @elseif($log->log_type == 'cash_received')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Cash Received</span>
                                @elseif($log->log_type == 'invoice_change')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">Invoice Change</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">{{ ucfirst($log->log_type) }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $log->reference_number ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($log->log_type == 'invoice_change' && $log->previous_amount != $log->new_amount)
                                    <div class="text-gray-900">
                                        <span class="line-through text-red-600">PKR {{ number_format($log->previous_amount ?? 0, 2) }}</span><br>
                                        <span class="text-green-600 font-semibold">PKR {{ number_format($log->new_amount ?? 0, 2) }}</span>
                                    </div>
                                @else
                                    <span class="text-green-600 font-semibold">PKR {{ number_format($log->amount, 2) }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($log->payment_status)
                                    @if($log->payment_status == 'paid')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Paid</span>
                                    @elseif($log->payment_status == 'partial')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Partial</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Pending</span>
                                    @endif
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <div>{{ $log->description }}</div>
                                @if($log->log_type == 'invoice_change' && $log->changes)
                                    <div class="mt-1 text-xs text-gray-500">
                                        @foreach($log->changes as $field => $change)
                                            @if($field == 'status')
                                                <div>Status: {{ $change['old'] }} → {{ $change['new'] }}</div>
                                            @elseif(in_array($field, ['total_amount', 'subtotal', 'tax_amount', 'discount_amount']))
                                                <div>{{ ucfirst(str_replace('_', ' ', $field)) }}: PKR {{ number_format($change['old'], 2) }} → PKR {{ number_format($change['new'], 2) }}</div>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                @if($log->comment)
                                    <div class="italic">{{ $log->comment }}</div>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $log->user->name ?? 'System' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-8 text-gray-500">
                <p>No payment or invoice logs found.</p>
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

        function printDayWiseBills(customerId) {
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
                            <h2>FARHAN TRADERS</h2>
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
        function printBillsSummary(customerId) {
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
                            <h2>FARHAN TRADERS</h2>
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


