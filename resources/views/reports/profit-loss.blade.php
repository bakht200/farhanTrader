<x-app-layout>
    <x-slot name="header">
        Sales Report
    </x-slot>

    @php
        $isProfit = ($summary['net_profit'] ?? 0) >= 0;
        $resultLabel = $isProfit ? 'PROFIT' : 'LOSS';
        $resultColor = $isProfit ? 'text-emerald-700' : 'text-red-700';
        $resultBg = $isProfit ? 'bg-emerald-50 border-emerald-200' : 'bg-red-50 border-red-200';
    @endphp

    <div class="mb-4">
        <nav class="text-sm text-gray-600">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-900">Dashboard</a>
            <span class="mx-2">></span>
            <span class="text-gray-900 font-medium">Sales Report</span>
        </nav>
    </div>

    <div class="mb-6 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900">
        <p class="font-semibold mb-1">How to use this report</p>
        <ol class="list-decimal list-inside space-y-1 text-blue-800">
            <li>Choose <strong>Day</strong>, <strong>Month</strong>, or <strong>Year</strong></li>
            <li>Pick the dates / months / years</li>
            <li>Press <strong>Show report</strong> — see profit or loss + all bills below</li>
        </ol>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-4 mb-6" x-data="{ mode: '{{ $mode }}' }">
        <form method="GET" action="{{ route('reports.profit-loss') }}" class="space-y-4">
            <p class="text-sm font-semibold text-gray-800">1) What period do you want?</p>
            <div class="flex flex-wrap gap-3">
                <label class="inline-flex items-center px-3 py-2 rounded-lg border cursor-pointer"
                       :class="mode === 'daily' ? 'border-orange-500 bg-orange-50' : 'border-gray-200'">
                    <input type="radio" name="mode" value="daily" x-model="mode" class="text-orange-500 focus:ring-orange-500" @change="$el.form.submit()">
                    <span class="ml-2 text-sm font-medium text-gray-800">By day</span>
                </label>
                <label class="inline-flex items-center px-3 py-2 rounded-lg border cursor-pointer"
                       :class="mode === 'monthly' ? 'border-orange-500 bg-orange-50' : 'border-gray-200'">
                    <input type="radio" name="mode" value="monthly" x-model="mode" class="text-orange-500 focus:ring-orange-500" @change="$el.form.submit()">
                    <span class="ml-2 text-sm font-medium text-gray-800">By month</span>
                </label>
                <label class="inline-flex items-center px-3 py-2 rounded-lg border cursor-pointer"
                       :class="mode === 'yearly' ? 'border-orange-500 bg-orange-50' : 'border-gray-200'">
                    <input type="radio" name="mode" value="yearly" x-model="mode" class="text-orange-500 focus:ring-orange-500" @change="$el.form.submit()">
                    <span class="ml-2 text-sm font-medium text-gray-800">By year</span>
                </label>
            </div>

            <div x-show="mode === 'daily'">
                <p class="text-sm font-semibold text-gray-800 mb-2">2) Choose dates</p>
                <div class="flex flex-col md:flex-row md:items-end gap-3">
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">From date</label>
                        <input type="date" name="start_date" value="{{ $filters['start_date'] }}"
                               class="px-3 py-2.5 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">To date</label>
                        <input type="date" name="end_date" value="{{ $filters['end_date'] }}"
                               class="px-3 py-2.5 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                    </div>
                    <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white px-5 py-2.5 rounded-md font-semibold">
                        Show report
                    </button>
                </div>
            </div>

            <div x-show="mode === 'monthly'" x-cloak>
                <p class="text-sm font-semibold text-gray-800 mb-2">2) Choose months</p>
                <div class="flex flex-col md:flex-row md:items-end gap-3">
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">From month</label>
                        <input type="month" name="start_month" value="{{ $filters['start_month'] }}"
                               class="px-3 py-2.5 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">To month</label>
                        <input type="month" name="end_month" value="{{ $filters['end_month'] }}"
                               class="px-3 py-2.5 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                    </div>
                    <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white px-5 py-2.5 rounded-md font-semibold">
                        Show report
                    </button>
                </div>
            </div>

            <div x-show="mode === 'yearly'" x-cloak>
                <p class="text-sm font-semibold text-gray-800 mb-2">2) Choose years</p>
                <div class="flex flex-col md:flex-row md:items-end gap-3">
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">From year</label>
                        <select name="start_year" class="px-3 py-2.5 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                            @foreach($years as $year)
                                <option value="{{ $year }}" @selected((int) $filters['start_year'] === (int) $year)>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">To year</label>
                        <select name="end_year" class="px-3 py-2.5 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                            @foreach($years as $year)
                                <option value="{{ $year }}" @selected((int) $filters['end_year'] === (int) $year)>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white px-5 py-2.5 rounded-md font-semibold">
                        Show report
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="mb-3 text-sm text-gray-700">
        Showing: <span class="font-bold text-gray-900">{{ $range['label'] }}</span>
    </div>

    <div class="rounded-xl border-2 {{ $resultBg }} p-5 mb-6 text-center">
        <p class="text-sm font-medium text-gray-600 mb-1">Result for this period</p>
        <p class="text-lg font-bold {{ $resultColor }} mb-1">{{ $resultLabel }}</p>
        <p class="text-3xl sm:text-4xl font-black tabular-nums {{ $resultColor }}">
            PKR {{ number_format(abs($summary['net_profit']), 0) }}
        </p>
        <p class="text-sm text-gray-600 mt-2">
            @if($isProfit)
                You made profit in this period
            @else
                You made a loss in this period
            @endif
        </p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
        <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-blue-500">
            <p class="text-sm text-gray-600">Total sales</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 tabular-nums">PKR {{ number_format($summary['revenue'], 0) }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ $summary['bill_count'] }} bills</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-orange-500">
            <p class="text-sm text-gray-600">Shop expenses</p>
            <p class="mt-1 text-2xl font-bold text-orange-600 tabular-nums">PKR {{ number_format($summary['total_expenses'], 0) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-gray-400">
            <p class="text-sm text-gray-600">Sales − product cost</p>
            <p class="mt-1 text-2xl font-bold tabular-nums {{ $summary['gross_profit'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                PKR {{ number_format($summary['gross_profit'], 0) }}
            </p>
            <p class="text-xs text-gray-400 mt-1">Before expenses</p>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="px-4 sm:px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">All bills in this period</h2>
                <p class="text-sm text-gray-500">Bill number · date &amp; time · amount</p>
            </div>
            <span class="text-sm text-gray-500">{{ $bills->count() }} bills</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-3 text-left font-medium text-gray-600">Bill number</th>
                        <th class="px-3 py-3 text-left font-medium text-gray-600">Date &amp; time</th>
                        <th class="px-3 py-3 text-right font-medium text-gray-600">Amount</th>
                        <th class="px-3 py-3 text-left font-medium text-gray-600">Paid?</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($bills as $bill)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-3">
                                <a href="{{ route('sales.show', $bill) }}" class="font-medium text-blue-600 hover:text-blue-800">
                                    #{{ $bill->sale_number }}
                                </a>
                            </td>
                            <td class="px-3 py-3 text-gray-700 tabular-nums">
                                {{ $bill->created_at?->format('d M Y H:i') ?? ($bill->sale_date?->format('d M Y') . ' —') }}
                            </td>
                            <td class="px-3 py-3 text-right font-semibold tabular-nums text-gray-900">
                                PKR {{ number_format($bill->total_amount, 0) }}
                            </td>
                            <td class="px-3 py-3">
                                @php
                                    $paidLabel = match($bill->payment_status) {
                                        'paid' => 'Fully paid',
                                        'partial' => 'Part paid',
                                        default => 'Not paid',
                                    };
                                    $paidClass = match($bill->payment_status) {
                                        'paid' => 'bg-emerald-100 text-emerald-800',
                                        'partial' => 'bg-amber-100 text-amber-800',
                                        default => 'bg-gray-100 text-gray-700',
                                    };
                                @endphp
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $paidClass }}">
                                    {{ $paidLabel }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-3 py-10 text-center text-gray-500">
                                No bills in this period. Try different dates.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
