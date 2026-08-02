<x-app-layout>
    <x-slot name="header">Sales Forecast</x-slot>

    <!-- Forecast Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-600 via-indigo-700 to-purple-800 p-6 text-white shadow-xl shadow-indigo-200">
            <div class="pointer-events-none absolute -right-6 -top-6 h-28 w-28 rounded-full bg-white/10"></div>
            <div class="relative flex items-start gap-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/20">
                    @if($forecast['trend_direction'] === 'upward')
                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    @else
                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path>
                        </svg>
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-semibold uppercase tracking-wider text-indigo-200">Trend direction</p>
                    <p class="mt-1 text-2xl font-black tabular-nums">{{ ucfirst($forecast['trend_direction']) }}</p>
                    <p class="mt-2 text-sm text-indigo-200 tabular-nums">PKR {{ number_format($forecast['trend_value'], 0) }}<span class="text-indigo-300">/month rate</span></p>
                </div>
            </div>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm border border-gray-200/60 hover:shadow-md transition-all duration-200">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Weekly average</p>
                    <p class="mt-2 text-2xl font-black text-gray-900 tabular-nums">PKR {{ number_format($forecast['weekly_average'], 0) }}</p>
                    <p class="mt-2 text-xs text-gray-500">Based on last 4 weeks</p>
                </div>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-100">
                    <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm border border-gray-200/60 hover:shadow-md transition-all duration-200">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Next week forecast</p>
                    <p class="mt-2 text-2xl font-black text-indigo-600 tabular-nums">PKR {{ number_format($forecast['next_week_forecast'], 0) }}</p>
                    <p class="mt-2 text-xs text-gray-500">AI predicted estimate</p>
                </div>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-violet-100">
                    <svg class="h-5 w-5 text-violet-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Sales Forecast Chart -->
    <div class="mb-8 rounded-2xl bg-white p-6 shadow-sm border border-gray-200/60">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-cyan-500 shadow-lg shadow-blue-200">
                    <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                    </svg>
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-base font-bold text-gray-900">12-Month History & Forecast</h3>
                        <span class="rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-bold uppercase tracking-wide text-indigo-700">AI</span>
                    </div>
                    <p class="mt-0.5 text-xs text-gray-500">Actuals vs. projected revenue trajectory</p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-4 text-xs text-gray-600">
                <span class="flex items-center gap-2">
                    <span class="h-3 w-3 shrink-0 rounded-full bg-blue-500"></span>
                    Actual
                </span>
                <span class="flex items-center gap-2">
                    <span class="h-3 w-3 shrink-0 rounded-full bg-orange-500"></span>
                    Forecast
                </span>
            </div>
        </div>
        <div class="h-80">
            <canvas id="forecastChart"></canvas>
        </div>
    </div>

    <!-- Trends Row -->
    <div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-2xl bg-white p-6 shadow-sm border border-gray-200/60">
            <div class="mb-5 flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 shadow-lg shadow-orange-200">
                    <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-900">Sales by Day of Week</h3>
                    <p class="text-xs text-gray-500">Revenue distribution across weekdays</p>
                </div>
            </div>
            <div class="h-64">
                <canvas id="dayChart"></canvas>
            </div>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm border border-gray-200/60">
            <div class="mb-5 flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-green-600 shadow-lg shadow-emerald-200">
                    <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-900">Month-over-Month Growth</h3>
                    <p class="text-xs text-gray-500">Percentage change vs. prior month</p>
                </div>
            </div>
            <div class="h-64">
                <canvas id="growthChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Category Performance -->
    <div class="rounded-2xl bg-white p-6 shadow-sm border border-gray-200/60">
        <div class="mb-5 flex items-start gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-blue-600 shadow-lg shadow-indigo-200">
                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                </svg>
            </div>
            <div>
                <h3 class="text-base font-bold text-gray-900">Category Performance</h3>
                <p class="text-xs text-gray-500">Last 30 days — revenue, units, and share</p>
            </div>
        </div>
        @if(count($trends['category_performance']) > 0)
            <div class="overflow-x-auto rounded-xl border border-gray-200">
                <table class="w-full min-w-[36rem]">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">#</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Category</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Revenue</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Units sold</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Share</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @php $totalCatRevenue = collect($trends['category_performance'])->sum('revenue'); @endphp
                        @foreach($trends['category_performance'] as $index => $cat)
                            @php $share = $totalCatRevenue > 0 ? ($cat['revenue'] / $totalCatRevenue) * 100 : 0; @endphp
                            <tr class="transition-colors hover:bg-gray-50">
                                <td class="px-4 py-3.5 text-sm tabular-nums text-gray-500">{{ $index + 1 }}</td>
                                <td class="px-4 py-3.5 text-sm font-semibold text-gray-900">{{ $cat['category'] }}</td>
                                <td class="px-4 py-3.5 text-right text-sm tabular-nums font-medium text-gray-900">PKR {{ number_format($cat['revenue'], 0) }}</td>
                                <td class="px-4 py-3.5 text-right text-sm tabular-nums text-gray-600">{{ number_format($cat['units_sold'], 0) }}</td>
                                <td class="w-48 px-4 py-3.5">
                                    <div class="flex items-center gap-2">
                                        <div class="h-2.5 flex-1 rounded-full bg-gray-100">
                                            <div class="h-2.5 rounded-full bg-indigo-500" style="width: {{ $share }}%"></div>
                                        </div>
                                        <span class="w-10 text-right text-xs tabular-nums font-medium text-gray-600">{{ number_format($share, 1) }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="rounded-xl border border-dashed border-gray-300 bg-gray-50 py-12 text-center text-sm text-gray-500">No category performance data available yet.</p>
        @endif
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const historical = @json($forecast['historical']);
            const forecastData = @json($forecast['forecast']);
            const dayPattern = @json($trends['day_pattern']);
            const monthlyGrowth = @json($trends['monthly_growth']);

            const allLabels = [...historical.map(h => h.month_short), ...forecastData.map(f => f.month_short)];
            const actualData = historical.map(h => h.amount);
            const forecastValues = [...new Array(historical.length).fill(null), ...forecastData.map(f => f.amount)];
            if (actualData.length > 0 && forecastData.length > 0) {
                forecastValues[actualData.length - 1] = actualData[actualData.length - 1];
            }

            new Chart(document.getElementById('forecastChart'), {
                type: 'line',
                data: {
                    labels: allLabels,
                    datasets: [{
                        label: 'Actual Sales',
                        data: [...actualData, ...new Array(forecastData.length).fill(null)],
                        borderColor: 'rgb(79, 70, 229)',
                        backgroundColor: 'rgba(79, 70, 229, 0.08)',
                        tension: 0.4,
                        fill: true,
                        borderWidth: 2.5,
                        pointBackgroundColor: 'rgb(79, 70, 229)',
                    }, {
                        label: 'Forecast',
                        data: forecastValues,
                        borderColor: 'rgb(249, 115, 22)',
                        backgroundColor: 'rgba(249, 115, 22, 0.08)',
                        borderDash: [6, 4],
                        tension: 0.4,
                        fill: true,
                        borderWidth: 2.5,
                        pointBackgroundColor: 'rgb(249, 115, 22)',
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { callback: v => 'PKR ' + (v/1000).toFixed(0) + 'k' }, grid: { color: 'rgba(0,0,0,0.04)' } },
                        x: { grid: { display: false } }
                    }
                }
            });

            if (dayPattern.length > 0) {
                new Chart(document.getElementById('dayChart'), {
                    type: 'bar',
                    data: {
                        labels: dayPattern.map(d => d.day),
                        datasets: [{
                            label: 'Revenue',
                            data: dayPattern.map(d => d.total),
                            backgroundColor: [
                                'rgba(239, 68, 68, 0.75)', 'rgba(79, 70, 229, 0.75)',
                                'rgba(16, 185, 129, 0.75)', 'rgba(245, 158, 11, 0.75)',
                                'rgba(139, 92, 246, 0.75)', 'rgba(236, 72, 153, 0.75)',
                                'rgba(107, 114, 128, 0.75)'
                            ],
                            borderRadius: 8,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, ticks: { callback: v => 'PKR ' + (v/1000).toFixed(0) + 'k' }, grid: { color: 'rgba(0,0,0,0.04)' } },
                            x: { grid: { display: false } }
                        }
                    }
                });
            }

            if (monthlyGrowth.length > 0) {
                new Chart(document.getElementById('growthChart'), {
                    type: 'bar',
                    data: {
                        labels: monthlyGrowth.map(m => m.month),
                        datasets: [{
                            label: 'Growth %',
                            data: monthlyGrowth.map(m => m.growth),
                            backgroundColor: monthlyGrowth.map(m => m.growth >= 0 ? 'rgba(16, 185, 129, 0.75)' : 'rgba(239, 68, 68, 0.75)'),
                            borderRadius: 8,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { ticks: { callback: v => v + '%' }, grid: { color: 'rgba(0,0,0,0.04)' } },
                            x: { grid: { display: false } }
                        }
                    }
                });
            }
        });
    </script>
</x-app-layout>
