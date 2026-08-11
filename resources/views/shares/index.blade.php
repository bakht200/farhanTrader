<x-app-layout>
    <x-slot name="header">
        Partner Share
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
            <span class="text-gray-900 font-medium">Partner Share</span>
        </nav>
    </div>

    {{-- Simple how-to --}}
    <div class="mb-6 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900">
        <p class="font-semibold mb-1">How this page works (3 steps)</p>
        <ol class="list-decimal list-inside space-y-1 text-blue-800">
            <li><strong>Add money</strong> each partner put in this month</li>
            <li>System shows <strong>profit or loss</strong> from sales</li>
            <li>Who put more money gets more share · Click <strong>Finish this month</strong> when month ends</li>
        </ol>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 px-4 py-3 text-red-800">
            <ul class="list-disc list-inside text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4 mb-5">
            <div>
                <h2 class="text-xl font-bold text-gray-900">This month: {{ $currentShare->periodLabel() }}</h2>
                <p class="text-sm text-gray-500 mt-1">
                    {{ $currentShare->period_start->format('d M Y') }} to {{ $currentShare->period_end->format('d M Y') }}
                </p>
                <span class="inline-flex mt-2 items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                    {{ $currentShare->isOpen() ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-800' }}">
                    {{ $currentShare->isOpen() ? 'Still open — you can edit' : 'Finished — locked' }}
                </span>
            </div>

            <div class="flex flex-wrap gap-2">
                @if($currentShare->isOpen())
                    <form method="POST" action="{{ route('shares.close', $currentShare) }}"
                          onsubmit="return confirm('Finish {{ $currentShare->periodLabel() }}?\n\nAfter this you cannot change investments.\nNext month will start as a new share.');">
                        @csrf
                        <button type="submit" class="px-4 py-2.5 bg-orange-500 hover:bg-orange-600 text-white rounded-md text-sm font-semibold">
                            Finish this month
                        </button>
                    </form>
                @endif
            </div>
        </div>

        {{-- Big result banner --}}
        <div class="rounded-xl border-2 {{ $resultBg }} p-5 mb-6 text-center">
            <p class="text-sm font-medium text-gray-600 mb-1">This month result</p>
            <p class="text-lg font-bold {{ $resultColor }} mb-1">{{ $resultLabel }}</p>
            <p class="text-3xl sm:text-4xl font-black tabular-nums {{ $resultColor }}">
                PKR {{ number_format(abs($summary['net_profit']), 0) }}
            </p>
            <p class="text-sm text-gray-600 mt-2">
                @if($isProfit)
                    Business made profit this month
                @else
                    Business made a loss this month (sold for less than cost)
                @endif
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                <p class="text-sm text-gray-600">Money partners put in</p>
                <p class="mt-1 text-xl font-bold text-gray-900 tabular-nums">PKR {{ number_format($summary['total_investment'], 0) }}</p>
                <p class="text-xs text-gray-400 mt-1">Not used in profit math — only for sharing %</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                <p class="text-sm text-gray-600">Total sales</p>
                <p class="mt-1 text-xl font-bold text-gray-900 tabular-nums">PKR {{ number_format($summary['revenue'], 0) }}</p>
                <p class="text-xs text-gray-400 mt-1">Money from customers this month</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                <p class="text-sm text-gray-600">Shop expenses</p>
                <p class="mt-1 text-xl font-bold text-orange-600 tabular-nums">PKR {{ number_format($summary['total_expenses'], 0) }}</p>
                <p class="text-xs text-gray-400 mt-1">Taken out before final profit/loss</p>
            </div>
        </div>

        <h3 class="text-base font-semibold text-gray-900 mb-1">Who gets how much?</h3>
        <p class="text-sm text-gray-500 mb-3">More money put in = bigger share of profit or loss.</p>

        <div class="overflow-x-auto mb-6">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-3 text-left font-medium text-gray-600">Partner name</th>
                        <th class="px-3 py-3 text-right font-medium text-gray-600">Money put in</th>
                        <th class="px-3 py-3 text-right font-medium text-gray-600">Their %</th>
                        <th class="px-3 py-3 text-right font-medium text-gray-600">Their {{ $isProfit ? 'profit' : 'loss' }}</th>
                        @if($currentShare->isOpen())
                            <th class="px-3 py-3 text-right font-medium text-gray-600"></th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($summary['allocations'] as $row)
                        <tr>
                            <td class="px-3 py-3 text-gray-900 font-medium">
                                {{ $row['user']->name ?? '—' }}
                            </td>
                            <td class="px-3 py-3 text-right tabular-nums">PKR {{ number_format($row['amount'], 0) }}</td>
                            <td class="px-3 py-3 text-right tabular-nums">{{ number_format($row['share_percent'], 1) }}%</td>
                            <td class="px-3 py-3 text-right tabular-nums font-bold {{ $row['profit_share'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                PKR {{ number_format(abs($row['profit_share']), 0) }}
                                <span class="text-xs font-normal">{{ $row['profit_share'] >= 0 ? 'profit' : 'loss' }}</span>
                            </td>
                            @if($currentShare->isOpen())
                                <td class="px-3 py-3 text-right">
                                    <form method="POST"
                                          action="{{ route('shares.investments.destroy', [$currentShare, $row['investment']]) }}"
                                          class="inline"
                                          onsubmit="return confirm('Remove this partner money entry?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-medium">Remove</button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $currentShare->isOpen() ? 5 : 4 }}" class="px-3 py-8 text-center text-gray-500">
                                No partners added yet. Fill the form below.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($currentShare->isOpen())
            <div class="border-t border-gray-100 pt-5">
                <h3 class="text-base font-semibold text-gray-900 mb-1">Step 1 — Add partner money</h3>
                <p class="text-sm text-gray-500 mb-4">Pick partner and type how much money they put in. If partner already exists, amount will update.</p>
                <form method="POST" action="{{ route('shares.investments.store', $currentShare) }}" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Who?</label>
                        <select name="user_id" required class="w-full px-3 py-2.5 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                            <option value="">Select partner</option>
                            @foreach($investors as $investor)
                                <option value="{{ $investor->id }}" @selected(old('user_id') == $investor->id)>
                                    {{ $investor->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">How much money? (PKR)</label>
                        <input type="number" name="amount" step="0.01" min="0" required value="{{ old('amount') }}"
                               class="w-full px-3 py-2.5 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
                               placeholder="Example: 100000">
                    </div>
                    <div>
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-md font-semibold">
                            Save partner money
                        </button>
                    </div>
                    <input type="hidden" name="notes" value="">
                </form>
            </div>
        @endif
    </div>

    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="px-4 sm:px-6 py-4 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-900">Old months</h2>
            <p class="text-sm text-gray-500">Finished months are locked. Open months can still change.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-3 text-left font-medium text-gray-600">Month</th>
                        <th class="px-3 py-3 text-left font-medium text-gray-600">Status</th>
                        <th class="px-3 py-3 text-right font-medium text-gray-600">Money put in</th>
                        <th class="px-3 py-3 text-right font-medium text-gray-600">Profit / Loss</th>
                        <th class="px-3 py-3 text-right font-medium text-gray-600"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($history as $item)
                        @php
                            $itemInvestment = $item->isClosed()
                                ? (float) $item->total_investment
                                : (float) $item->investments->sum('amount');
                            $itemProfit = $item->isClosed() ? (float) $item->net_profit : null;
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-3 text-gray-900 font-medium">{{ $item->periodLabel() }}</td>
                            <td class="px-3 py-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $item->isOpen() ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-700' }}">
                                    {{ $item->isOpen() ? 'Open' : 'Finished' }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-right tabular-nums">PKR {{ number_format($itemInvestment, 0) }}</td>
                            <td class="px-3 py-3 text-right tabular-nums">
                                @if($itemProfit !== null)
                                    <span class="font-semibold {{ $itemProfit >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                        {{ $itemProfit >= 0 ? 'Profit' : 'Loss' }}
                                        PKR {{ number_format(abs($itemProfit), 0) }}
                                    </span>
                                @else
                                    <span class="text-gray-400">Still calculating…</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-right">
                                <a href="{{ route('shares.show', $item) }}" class="text-blue-600 hover:text-blue-800 font-medium">See details</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($history->hasPages())
            <div class="px-4 py-4 border-t border-gray-100">
                {{ $history->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
