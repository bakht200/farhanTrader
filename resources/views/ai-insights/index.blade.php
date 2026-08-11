<x-app-layout>
    <x-slot name="header">AI Insights</x-slot>

    <style>[x-cloak] { display: none !important; }</style>

    <!-- Top Summary -->
    <div class="mb-8" x-data="{ showStats: false }">
        <div class="flex items-center justify-between mb-3">
            <p class="text-sm font-medium text-gray-600">Summary</p>
            <button
                type="button"
                @click="showStats = !showStats"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50"
                :title="showStats ? 'Hide values' : 'Show values'"
            >
                <!-- Eye (show) -->
                <svg x-show="!showStats" class="h-5 w-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
                <!-- Eye off (hide) -->
                <svg x-show="showStats" x-cloak class="h-5 w-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                </svg>
                <span x-text="showStats ? 'Hide' : 'Show'"></span>
            </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-5">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200/60 p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total Profit</p>
                <p class="mt-2 text-3xl font-black text-emerald-600 tabular-nums" x-show="showStats" x-cloak>PKR {{ number_format($topStats['total_profit'], 0) }}</p>
                <p class="mt-2 text-3xl font-black text-emerald-600/40 tabular-nums select-none" x-show="!showStats">••••••••</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200/60 p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total Lost</p>
                <p class="mt-2 text-3xl font-black text-red-600 tabular-nums" x-show="showStats" x-cloak>PKR {{ number_format($topStats['total_lost'], 0) }}</p>
                <p class="mt-2 text-3xl font-black text-red-600/40 tabular-nums select-none" x-show="!showStats">••••••••</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200/60 p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total Stock</p>
                <p class="mt-2 text-3xl font-black text-indigo-600 tabular-nums" x-show="showStats" x-cloak>{{ number_format($topStats['total_stock'], 0) }}</p>
                <p class="mt-2 text-3xl font-black text-indigo-600/40 tabular-nums select-none" x-show="!showStats">••••••••</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200/60 p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total Stock Value</p>
                <p class="mt-2 text-3xl font-black text-amber-600 tabular-nums" x-show="showStats" x-cloak>PKR {{ number_format($topStats['total_stock_value'] ?? 0, 0) }}</p>
                <p class="mt-2 text-3xl font-black text-amber-600/40 tabular-nums select-none" x-show="!showStats">••••••••</p>
                <p class="mt-1 text-[11px] text-gray-400" x-show="showStats" x-cloak>What’s left in shop</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200/60 p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Supplier Total</p>
                <p class="mt-2 text-3xl font-black text-sky-600 tabular-nums" x-show="showStats" x-cloak>PKR {{ number_format($topStats['supplier_total'] ?? 0, 0) }}</p>
                <p class="mt-2 text-3xl font-black text-sky-600/40 tabular-nums select-none" x-show="!showStats">••••••••</p>
                <p class="mt-1 text-[11px] text-gray-400" x-show="showStats" x-cloak>
                    All supplier bills · Remaining PKR {{ number_format($topStats['supplier_remaining'] ?? 0, 0) }}
                </p>
            </div>
        </div>
    </div>

    <!-- Business Health Score -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Overall Score - Hero Card -->
        <div class="bg-gradient-to-br from-indigo-600 via-indigo-700 to-purple-800 rounded-2xl p-8 text-white shadow-xl shadow-indigo-200 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-40 h-40 bg-white/10 rounded-full -translate-y-12 translate-x-12"></div>
            <div class="absolute bottom-0 left-0 w-24 h-24 bg-white/5 rounded-full translate-y-8 -translate-x-8"></div>
            <div class="relative">
                <div class="flex items-center gap-2 mb-6">
                    <div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    </div>
                    <p class="text-sm font-semibold text-indigo-200 uppercase tracking-wider">Business Health</p>
                </div>
                <div class="flex items-end gap-4">
                    <div class="relative inline-flex items-center justify-center w-32 h-32">
                        <svg class="w-32 h-32 transform -rotate-90" viewBox="0 0 36 36">
                            <path d="M18 2.0845a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                fill="none" stroke="rgba(255,255,255,0.15)" stroke-width="3"/>
                            <path d="M18 2.0845a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                fill="none"
                                stroke="{{ $healthScore['overall'] >= 70 ? '#34d399' : ($healthScore['overall'] >= 40 ? '#fbbf24' : '#f87171') }}"
                                stroke-width="3"
                                stroke-dasharray="{{ $healthScore['overall'] }}, 100"
                                stroke-linecap="round"/>
                        </svg>
                        <div class="absolute text-center">
                            <span class="text-4xl font-black tabular-nums">{{ $healthScore['overall'] }}</span>
                            <span class="block text-xs text-indigo-200 -mt-0.5">/ 100</span>
                        </div>
                    </div>
                    <div class="pb-3">
                        <p class="text-sm text-indigo-200">
                            @if($healthScore['overall'] >= 70)
                                Excellent — your business is thriving
                            @elseif($healthScore['overall'] >= 40)
                                Average — room for improvement
                            @else
                                Needs attention — review metrics below
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metric Cards Grid -->
        <div class="lg:col-span-2 grid grid-cols-2 gap-4">
            <!-- Sales Momentum -->
            @php $val = $healthScore['sales_momentum']; @endphp
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200/60 p-5 hover:shadow-md transition-all duration-200">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                    <span class="text-2xl font-black text-gray-900 tabular-nums">{{ $val }}%</span>
                </div>
                <p class="text-sm font-semibold text-gray-700 mb-2">Sales Momentum</p>
                <div class="w-full bg-gray-100 rounded-full h-2">
                    <div class="h-2 rounded-full transition-all duration-500 {{ $val >= 60 ? 'bg-emerald-500' : ($val >= 40 ? 'bg-amber-500' : 'bg-red-500') }}"
                        style="width: {{ $val }}%"></div>
                </div>
            </div>

            <!-- Inventory Health -->
            @php $val = $healthScore['inventory_health']; @endphp
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200/60 p-5 hover:shadow-md transition-all duration-200">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                    <span class="text-2xl font-black text-gray-900 tabular-nums">{{ $val }}%</span>
                </div>
                <p class="text-sm font-semibold text-gray-700 mb-2">Inventory Health</p>
                <div class="w-full bg-gray-100 rounded-full h-2">
                    <div class="h-2 rounded-full transition-all duration-500 {{ $val >= 60 ? 'bg-blue-500' : ($val >= 40 ? 'bg-amber-500' : 'bg-red-500') }}"
                        style="width: {{ $val }}%"></div>
                </div>
            </div>

            <!-- Customer Retention -->
            @php $val = $healthScore['customer_retention']; @endphp
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200/60 p-5 hover:shadow-md transition-all duration-200">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 rounded-xl bg-violet-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <span class="text-2xl font-black text-gray-900 tabular-nums">{{ $val }}%</span>
                </div>
                <p class="text-sm font-semibold text-gray-700 mb-2">Customer Retention</p>
                <div class="w-full bg-gray-100 rounded-full h-2">
                    <div class="h-2 rounded-full transition-all duration-500 {{ $val >= 60 ? 'bg-violet-500' : ($val >= 40 ? 'bg-amber-500' : 'bg-red-500') }}"
                        style="width: {{ $val }}%"></div>
                </div>
            </div>

            <!-- Revenue Diversity -->
            @php $val = $healthScore['revenue_diversity']; @endphp
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200/60 p-5 hover:shadow-md transition-all duration-200">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
                        </svg>
                    </div>
                    <span class="text-2xl font-black text-gray-900 tabular-nums">{{ $val }}%</span>
                </div>
                <p class="text-sm font-semibold text-gray-700 mb-2">Revenue Diversity</p>
                <div class="w-full bg-gray-100 rounded-full h-2">
                    <div class="h-2 rounded-full transition-all duration-500 {{ $val >= 60 ? 'bg-amber-500' : ($val >= 40 ? 'bg-orange-400' : 'bg-red-500') }}"
                        style="width: {{ $val }}%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Smart Insights -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200/60 p-6 mb-8">
        <div class="flex items-center gap-3 mb-5">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-lg shadow-blue-200">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                </svg>
            </div>
            <div>
                <h3 class="text-base font-bold text-gray-900">Smart Insights</h3>
                <p class="text-xs text-gray-500">Auto-generated from your data</p>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @foreach($insights as $insight)
                @php
                    $styles = match($insight['type']) {
                        'positive' => ['bg' => 'bg-emerald-50', 'border' => 'border-emerald-200', 'dot' => 'bg-emerald-500', 'text' => 'text-emerald-800'],
                        'negative' => ['bg' => 'bg-red-50', 'border' => 'border-red-200', 'dot' => 'bg-red-500', 'text' => 'text-red-800'],
                        'warning'  => ['bg' => 'bg-amber-50', 'border' => 'border-amber-200', 'dot' => 'bg-amber-500', 'text' => 'text-amber-800'],
                        default    => ['bg' => 'bg-blue-50', 'border' => 'border-blue-200', 'dot' => 'bg-blue-500', 'text' => 'text-blue-800'],
                    };
                @endphp
                <div class="flex items-start gap-3 p-4 rounded-xl {{ $styles['bg'] }} border {{ $styles['border'] }}">
                    <span class="mt-1.5 w-2.5 h-2.5 rounded-full {{ $styles['dot'] }} flex-shrink-0 ring-2 ring-white"></span>
                    <p class="text-sm leading-relaxed {{ $styles['text'] }}">{{ $insight['message'] }}</p>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Reorder Alerts -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200/60 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-orange-500 to-red-500 flex items-center justify-center shadow-lg shadow-orange-200">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-900">Reorder Alerts</h3>
                    <p class="text-xs text-gray-500">Products running low based on sales velocity</p>
                </div>
                @if($reorderAlerts->count() > 0)
                    <span class="ml-2 px-3 py-1 text-xs font-bold bg-red-100 text-red-700 rounded-full">{{ $reorderAlerts->count() }}</span>
                @endif
            </div>
            <a href="{{ route('ai-insights.inventory') }}" class="text-sm text-indigo-600 hover:text-indigo-700 font-semibold flex items-center gap-1">
                View All
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path></svg>
            </a>
        </div>

        @if($reorderAlerts->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="text-left py-3 px-6 text-xs font-semibold uppercase tracking-wider text-gray-500">Product</th>
                            <th class="text-center py-3 px-4 text-xs font-semibold uppercase tracking-wider text-gray-500">Urgency</th>
                            <th class="text-right py-3 px-4 text-xs font-semibold uppercase tracking-wider text-gray-500">Stock</th>
                            <th class="text-right py-3 px-4 text-xs font-semibold uppercase tracking-wider text-gray-500">Daily Sales</th>
                            <th class="text-right py-3 px-4 text-xs font-semibold uppercase tracking-wider text-gray-500">Days Left</th>
                            <th class="text-right py-3 px-6 text-xs font-semibold uppercase tracking-wider text-gray-500">Order Qty</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($reorderAlerts->take(8) as $alert)
                            <tr class="hover:bg-gray-50/80 transition-colors">
                                <td class="py-4 px-6">
                                    <p class="text-sm font-semibold text-gray-900">{{ $alert['product']->name }}</p>
                                    <p class="text-xs text-gray-400 mt-0.5">{{ $alert['product']->category?->name ?? '' }}</p>
                                </td>
                                <td class="py-4 px-4 text-center">
                                    @php
                                        $uClass = match($alert['urgency']) {
                                            'critical' => 'bg-red-100 text-red-700',
                                            'high' => 'bg-orange-100 text-orange-700',
                                            default => 'bg-amber-100 text-amber-700',
                                        };
                                    @endphp
                                    <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold {{ $uClass }}">
                                        {{ ucfirst($alert['urgency']) }}
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-right text-sm font-medium text-gray-900 tabular-nums">{{ number_format($alert['current_stock'], 0) }}</td>
                                <td class="py-4 px-4 text-right text-sm text-gray-600 tabular-nums">{{ $alert['daily_velocity'] }}/d</td>
                                <td class="py-4 px-4 text-right">
                                    <span class="text-sm font-bold tabular-nums {{ $alert['days_remaining'] <= 3 ? 'text-red-600' : ($alert['days_remaining'] <= 7 ? 'text-orange-500' : 'text-amber-500') }}">
                                        {{ $alert['days_remaining'] }}d
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-indigo-100 text-indigo-700 tabular-nums">
                                        {{ number_format($alert['reorder_qty'], 0) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-16">
                <div class="w-16 h-16 mx-auto bg-emerald-100 rounded-2xl flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <p class="text-sm font-bold text-gray-900">All stock levels are healthy</p>
                <p class="text-xs text-gray-500 mt-1">No reorder alerts at this time</p>
            </div>
        @endif
    </div>
</x-app-layout>
