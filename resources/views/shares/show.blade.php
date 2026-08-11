<x-app-layout>
    <x-slot name="header">
        Partner Share — {{ $share->periodLabel() }}
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
            <a href="{{ route('shares.index') }}" class="hover:text-gray-900">Partner Share</a>
            <span class="mx-2">></span>
            <span class="text-gray-900 font-medium">{{ $share->periodLabel() }}</span>
        </nav>
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
                <h2 class="text-xl font-bold text-gray-900">{{ $share->periodLabel() }}</h2>
                <p class="text-sm text-gray-500 mt-1">
                    {{ $share->period_start->format('d M Y') }} to {{ $share->period_end->format('d M Y') }}
                </p>
                <span class="inline-flex mt-2 items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                    {{ $share->isOpen() ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-800' }}">
                    {{ $share->isOpen() ? 'Still open — you can edit' : 'Finished — locked' }}
                </span>
                @if($share->isClosed() && $share->closed_at)
                    <p class="text-xs text-gray-500 mt-2">
                        Finished on {{ $share->closed_at->format('d M Y H:i') }}
                        @if($share->closedByUser)
                            by {{ $share->closedByUser->name }}
                        @endif
                    </p>
                @endif
            </div>

            @if($share->isOpen())
                <form method="POST" action="{{ route('shares.close', $share) }}"
                      onsubmit="return confirm('Finish {{ $share->periodLabel() }}?\n\nAfter this you cannot change partner money.');">
                    @csrf
                    <button type="submit" class="px-4 py-2.5 bg-orange-500 hover:bg-orange-600 text-white rounded-md text-sm font-semibold">
                        Finish this month
                    </button>
                </form>
            @endif
        </div>

        <div class="rounded-xl border-2 {{ $resultBg }} p-5 mb-6 text-center">
            <p class="text-sm font-medium text-gray-600 mb-1">Month result</p>
            <p class="text-lg font-bold {{ $resultColor }} mb-1">{{ $resultLabel }}</p>
            <p class="text-3xl font-black tabular-nums {{ $resultColor }}">
                PKR {{ number_format(abs($summary['net_profit']), 0) }}
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                <p class="text-sm text-gray-600">Money partners put in</p>
                <p class="mt-1 text-xl font-bold text-gray-900">PKR {{ number_format($summary['total_investment'], 0) }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                <p class="text-sm text-gray-600">Total sales</p>
                <p class="mt-1 text-xl font-bold text-gray-900">PKR {{ number_format($summary['revenue'], 0) }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                <p class="text-sm text-gray-600">Shop expenses</p>
                <p class="mt-1 text-xl font-bold text-orange-600">PKR {{ number_format($summary['total_expenses'], 0) }}</p>
            </div>
        </div>

        <h3 class="text-base font-semibold text-gray-900 mb-1">Who gets how much?</h3>
        <p class="text-sm text-gray-500 mb-3">More money put in = bigger share.</p>

        <div class="overflow-x-auto mb-6">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-3 text-left font-medium text-gray-600">Partner</th>
                        <th class="px-3 py-3 text-right font-medium text-gray-600">Money put in</th>
                        <th class="px-3 py-3 text-right font-medium text-gray-600">Their %</th>
                        <th class="px-3 py-3 text-right font-medium text-gray-600">Their {{ $isProfit ? 'profit' : 'loss' }}</th>
                        @if($share->isOpen())
                            <th class="px-3 py-3 text-right font-medium text-gray-600"></th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($summary['allocations'] as $row)
                        <tr>
                            <td class="px-3 py-3 font-medium text-gray-900">{{ $row['user']->name ?? '—' }}</td>
                            <td class="px-3 py-3 text-right tabular-nums align-top">
                                @if($share->isOpen())
                                    <form method="POST" action="{{ route('shares.investments.update', [$share, $row['investment']]) }}" class="inline-flex items-center gap-2 justify-end">
                                        @csrf
                                        @method('PUT')
                                        <input type="number" name="amount" step="0.01" min="0" required
                                               value="{{ $row['amount'] }}"
                                               class="w-32 px-2 py-1.5 border border-gray-300 rounded text-right">
                                        <button type="submit" class="text-blue-600 hover:text-blue-800 text-xs font-semibold">Save</button>
                                    </form>
                                @else
                                    PKR {{ number_format($row['amount'], 0) }}
                                @endif
                            </td>
                            <td class="px-3 py-3 text-right tabular-nums">{{ number_format($row['share_percent'], 1) }}%</td>
                            <td class="px-3 py-3 text-right tabular-nums font-bold {{ $row['profit_share'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                PKR {{ number_format(abs($row['profit_share']), 0) }}
                            </td>
                            @if($share->isOpen())
                                <td class="px-3 py-3 text-right">
                                    <form method="POST" action="{{ route('shares.investments.destroy', [$share, $row['investment']]) }}"
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
                            <td colspan="{{ $share->isOpen() ? 5 : 4 }}" class="px-3 py-8 text-center text-gray-500">No partners yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($share->isOpen())
            <div class="border-t border-gray-100 pt-5">
                <h3 class="text-base font-semibold text-gray-900 mb-1">Add partner money</h3>
                <form method="POST" action="{{ route('shares.investments.store', $share) }}" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Who?</label>
                        <select name="user_id" required class="w-full px-3 py-2.5 border border-gray-300 rounded-md">
                            <option value="">Select partner</option>
                            @foreach($investors as $investor)
                                <option value="{{ $investor->id }}">{{ $investor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">How much? (PKR)</label>
                        <input type="number" name="amount" step="0.01" min="0" required class="w-full px-3 py-2.5 border border-gray-300 rounded-md" placeholder="Example: 100000">
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
</x-app-layout>
