<x-app-layout>
    <x-slot name="header">ABC Inventory Analysis</x-slot>

    <!-- ABC Summary Cards -->
    @if(!empty($abcAnalysis['summary']))
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-600 via-indigo-700 to-purple-800 p-6 text-white shadow-xl shadow-indigo-200">
            <div class="pointer-events-none absolute -right-2 -top-4 select-none text-[7rem] font-black leading-none text-white/[0.08]">A</div>
            <div class="relative">
                <p class="text-xs font-semibold uppercase tracking-wider text-indigo-200 mb-2">Class A — High Value</p>
                <p class="text-3xl font-black tabular-nums">{{ $abcAnalysis['summary']['A']['count'] }} products</p>
                <p class="text-sm text-indigo-200 mt-2 tabular-nums">{{ $abcAnalysis['summary']['A']['percentage'] }}% of total revenue</p>
                <p class="text-xs text-indigo-300 mt-2 tabular-nums">PKR {{ number_format($abcAnalysis['summary']['A']['revenue'], 0) }}</p>
            </div>
        </div>

        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 p-6 text-white shadow-xl shadow-amber-200">
            <div class="pointer-events-none absolute -right-2 -top-4 select-none text-[7rem] font-black leading-none text-white/[0.12]">B</div>
            <div class="relative">
                <p class="text-xs font-semibold uppercase tracking-wider text-amber-100 mb-2">Class B — Medium Value</p>
                <p class="text-3xl font-black tabular-nums">{{ $abcAnalysis['summary']['B']['count'] }} products</p>
                <p class="text-sm text-amber-100 mt-2 tabular-nums">{{ $abcAnalysis['summary']['B']['percentage'] }}% of total revenue</p>
                <p class="text-xs text-amber-200 mt-2 tabular-nums">PKR {{ number_format($abcAnalysis['summary']['B']['revenue'], 0) }}</p>
            </div>
        </div>

        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-gray-600 to-gray-800 p-6 text-white shadow-xl shadow-gray-300">
            <div class="pointer-events-none absolute -right-2 -top-4 select-none text-[7rem] font-black leading-none text-white/[0.10]">C</div>
            <div class="relative">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-300 mb-2">Class C — Low Value</p>
                <p class="text-3xl font-black tabular-nums">{{ $abcAnalysis['summary']['C']['count'] }} products</p>
                <p class="text-sm text-gray-300 mt-2 tabular-nums">{{ $abcAnalysis['summary']['C']['percentage'] }}% of total revenue</p>
                <p class="text-xs text-gray-400 mt-2 tabular-nums">PKR {{ number_format($abcAnalysis['summary']['C']['revenue'], 0) }}</p>
            </div>
        </div>
    </div>
    @endif

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="rounded-2xl bg-white p-6 shadow-sm border border-gray-200/60">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-lg shadow-emerald-200">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-900">Revenue Distribution</h3>
                    <p class="text-xs text-gray-500">Share of revenue by ABC class</p>
                </div>
            </div>
            <div class="h-64">
                <canvas id="abcPieChart"></canvas>
            </div>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm border border-gray-200/60">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-rose-500 to-red-600 flex items-center justify-center shadow-lg shadow-rose-200">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-900">Pareto Chart (Cumulative %)</h3>
                    <p class="text-xs text-gray-500">Top products vs. cumulative revenue</p>
                </div>
            </div>
            <div class="h-64">
                <canvas id="paretoChart"></canvas>
            </div>
        </div>
    </div>

    @php
        $classThemes = [
            'A' => ['gradient' => 'bg-gradient-to-br from-indigo-600 to-indigo-700', 'badge' => 'bg-indigo-100 text-indigo-700'],
            'B' => ['gradient' => 'bg-gradient-to-br from-amber-500 to-orange-600', 'badge' => 'bg-amber-100 text-amber-700'],
            'C' => ['gradient' => 'bg-gradient-to-br from-gray-600 to-gray-700', 'badge' => 'bg-gray-200 text-gray-700'],
        ];
    @endphp

    @foreach(['A', 'B', 'C'] as $class)
        @if($abcAnalysis[$class]->count() > 0)
        @php $theme = $classThemes[$class]; @endphp
        <div class="rounded-2xl bg-white shadow-sm border border-gray-200/60 overflow-hidden mb-8">
            <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-5 border-b border-gray-100">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-10 h-10 rounded-xl {{ $theme['gradient'] }} flex items-center justify-center shrink-0 text-sm font-black text-white shadow-sm">
                        {{ $class }}
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-base font-bold text-gray-900">Class {{ $class }} Products</h3>
                        <p class="text-xs text-gray-500">Ranked by revenue contribution</p>
                    </div>
                </div>
                <span class="inline-flex items-center px-3 py-1 text-xs font-bold rounded-full {{ $theme['badge'] }} tabular-nums">
                    {{ $abcAnalysis[$class]->count() }} items
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px]">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="text-left py-3 px-5 text-xs font-semibold uppercase tracking-wider text-gray-500">#</th>
                            <th scope="col" class="text-left py-3 px-5 text-xs font-semibold uppercase tracking-wider text-gray-500">Product</th>
                            <th scope="col" class="text-left py-3 px-5 text-xs font-semibold uppercase tracking-wider text-gray-500">Category</th>
                            <th scope="col" class="text-right py-3 px-5 text-xs font-semibold uppercase tracking-wider text-gray-500">Revenue</th>
                            <th scope="col" class="text-right py-3 px-5 text-xs font-semibold uppercase tracking-wider text-gray-500">Units</th>
                            <th scope="col" class="text-right py-3 px-5 text-xs font-semibold uppercase tracking-wider text-gray-500">% Rev</th>
                            <th scope="col" class="text-right py-3 px-5 text-xs font-semibold uppercase tracking-wider text-gray-500">Cumulative</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($abcAnalysis[$class]->take(15) as $index => $item)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="py-3.5 px-5 text-sm text-gray-500 tabular-nums">{{ $index + 1 }}</td>
                                <td class="py-3.5 px-5 text-sm font-semibold text-gray-900">{{ $item['product']?->name ?? 'N/A' }}</td>
                                <td class="py-3.5 px-5 text-sm text-gray-500">{{ $item['product']?->category?->name ?? 'N/A' }}</td>
                                <td class="py-3.5 px-5 text-sm text-right font-medium text-gray-900 tabular-nums">PKR {{ number_format($item['revenue'], 0) }}</td>
                                <td class="py-3.5 px-5 text-sm text-right text-gray-600 tabular-nums">{{ number_format($item['quantity'], 0) }}</td>
                                <td class="py-3.5 px-5 text-sm text-right text-gray-600 tabular-nums">{{ $item['revenue_percentage'] }}%</td>
                                <td class="py-3.5 px-5 text-sm text-right text-gray-600 tabular-nums">{{ $item['cumulative_percentage'] }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    @endforeach

    <!-- Reorder Alerts -->
    @if($reorderAlerts->count() > 0)
    <div class="rounded-2xl bg-white shadow-sm border border-gray-200/60 overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-5 border-b border-gray-100">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-orange-500 to-red-500 flex items-center justify-center shadow-lg shadow-orange-200">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                </div>
                <div class="min-w-0">
                    <h3 class="text-base font-bold text-gray-900">Smart Reorder Alerts</h3>
                    <p class="text-xs text-gray-500">Stock, velocity, and suggested order quantities</p>
                </div>
            </div>
            <span class="inline-flex items-center px-3 py-1 text-xs font-bold rounded-full bg-red-100 text-red-700 tabular-nums">
                {{ $reorderAlerts->count() }} items
            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px]">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="text-left py-3 px-5 text-xs font-semibold uppercase tracking-wider text-gray-500">Product</th>
                        <th scope="col" class="text-center py-3 px-5 text-xs font-semibold uppercase tracking-wider text-gray-500">Urgency</th>
                        <th scope="col" class="text-right py-3 px-5 text-xs font-semibold uppercase tracking-wider text-gray-500">Stock</th>
                        <th scope="col" class="text-right py-3 px-5 text-xs font-semibold uppercase tracking-wider text-gray-500">Daily Velocity</th>
                        <th scope="col" class="text-right py-3 px-5 text-xs font-semibold uppercase tracking-wider text-gray-500">Days Left</th>
                        <th scope="col" class="text-right py-3 px-5 text-xs font-semibold uppercase tracking-wider text-gray-500">Safety Stock</th>
                        <th scope="col" class="text-right py-3 px-5 text-xs font-semibold uppercase tracking-wider text-gray-500">Suggested Qty</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($reorderAlerts as $alert)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="py-3.5 px-5">
                                <p class="text-sm font-semibold text-gray-900">{{ $alert['product']->name }}</p>
                            </td>
                            <td class="py-3.5 px-5 text-center">
                                @php
                                    $urgencyClass = match($alert['urgency']) {
                                        'critical' => 'bg-red-100 text-red-700',
                                        'high' => 'bg-orange-100 text-orange-700',
                                        default => 'bg-yellow-100 text-yellow-700',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold {{ $urgencyClass }}">{{ ucfirst($alert['urgency']) }}</span>
                            </td>
                            <td class="py-3.5 px-5 text-right text-sm font-medium tabular-nums">{{ number_format($alert['current_stock'], 0) }}</td>
                            <td class="py-3.5 px-5 text-right text-sm tabular-nums text-gray-600">{{ $alert['daily_velocity'] }}/day</td>
                            <td class="py-3.5 px-5 text-right">
                                <span class="text-sm font-bold tabular-nums {{ $alert['days_remaining'] <= 3 ? 'text-red-600' : ($alert['days_remaining'] <= 7 ? 'text-orange-600' : 'text-yellow-600') }}">
                                    {{ $alert['days_remaining'] }} days
                                </span>
                            </td>
                            <td class="py-3.5 px-5 text-right text-sm text-gray-600 tabular-nums">{{ number_format($alert['safety_stock'], 0) }}</td>
                            <td class="py-3.5 px-5 text-right">
                                <span class="text-sm font-bold text-indigo-600 tabular-nums">{{ number_format($alert['reorder_qty'], 0) }} units</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const summary = @json($abcAnalysis['summary'] ?? []);

            if (Object.keys(summary).length > 0) {
                new Chart(document.getElementById('abcPieChart'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Class A (High Value)', 'Class B (Medium)', 'Class C (Low Value)'],
                        datasets: [{
                            data: [summary.A?.revenue || 0, summary.B?.revenue || 0, summary.C?.revenue || 0],
                            backgroundColor: ['rgba(79, 70, 229, 0.85)', 'rgba(245, 158, 11, 0.85)', 'rgba(107, 114, 128, 0.85)'],
                            borderWidth: 3,
                            borderColor: '#fff',
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom', labels: { padding: 15, font: { size: 12 } } }
                        }
                    }
                });
            }

            const allProducts = [
                ...@json($abcAnalysis['A'] ?? []),
                ...@json($abcAnalysis['B'] ?? []),
                ...@json($abcAnalysis['C'] ?? [])
            ];

            if (allProducts.length > 0) {
                const labels = allProducts.slice(0, 20).map((p, i) => (i + 1).toString());
                const revenues = allProducts.slice(0, 20).map(p => p.revenue);
                const cumPcts = allProducts.slice(0, 20).map(p => p.cumulative_percentage);

                new Chart(document.getElementById('paretoChart'), {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            type: 'bar',
                            label: 'Revenue',
                            data: revenues,
                            backgroundColor: allProducts.slice(0, 20).map(p =>
                                p.class === 'A' ? 'rgba(79, 70, 229, 0.75)' :
                                p.class === 'B' ? 'rgba(245, 158, 11, 0.75)' : 'rgba(107, 114, 128, 0.75)'
                            ),
                            borderRadius: 6,
                            yAxisID: 'y',
                        }, {
                            type: 'line',
                            label: 'Cumulative %',
                            data: cumPcts,
                            borderColor: 'rgb(239, 68, 68)',
                            borderWidth: 2.5,
                            pointRadius: 3,
                            pointBackgroundColor: 'rgb(239, 68, 68)',
                            yAxisID: 'y1',
                            fill: false,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, position: 'left', ticks: { callback: v => 'PKR ' + (v/1000).toFixed(0) + 'k' }, grid: { color: 'rgba(0,0,0,0.04)' } },
                            y1: { beginAtZero: true, max: 100, position: 'right', grid: { drawOnChartArea: false }, ticks: { callback: v => v + '%' } },
                            x: { grid: { display: false } }
                        }
                    }
                });
            }
        });
    </script>
</x-app-layout>
