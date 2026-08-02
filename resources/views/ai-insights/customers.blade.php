<x-app-layout>
    <x-slot name="header">Customer Segments</x-slot>

    <!-- Explanation -->
    <div class="bg-gradient-to-r from-indigo-50 to-blue-50 border border-indigo-200 rounded-2xl p-5 mb-6">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-white border border-indigo-200 flex items-center justify-center shadow-sm">
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-sm font-bold text-gray-900">RFM Segmentation — Recency, Frequency, Monetary</p>
                <p class="text-sm text-gray-600 mt-1.5 leading-relaxed">Customers are scored and grouped into segments based on how recently they purchased, how often, and how much they spend. Use this to target marketing and retention efforts.</p>
            </div>
        </div>
    </div>

    <!-- Segment Cards -->
    @if(count($rfm['segments']) > 0)
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 mb-6">
        @php
            $segmentColors = [
                'Champions' => ['bg' => 'from-emerald-500 to-emerald-700', 'text' => 'text-emerald-100', 'icon' => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z'],
                'Loyal Customers' => ['bg' => 'from-blue-500 to-blue-700', 'text' => 'text-blue-100', 'icon' => 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z'],
                'Potential Loyalists' => ['bg' => 'from-indigo-500 to-indigo-700', 'text' => 'text-indigo-100', 'icon' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6'],
                'New Customers' => ['bg' => 'from-cyan-500 to-cyan-700', 'text' => 'text-cyan-100', 'icon' => 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z'],
                'Promising' => ['bg' => 'from-violet-500 to-violet-700', 'text' => 'text-violet-100', 'icon' => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z'],
                'Need Attention' => ['bg' => 'from-amber-500 to-amber-700', 'text' => 'text-amber-100', 'icon' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9'],
                'At Risk' => ['bg' => 'from-orange-500 to-orange-700', 'text' => 'text-orange-100', 'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z'],
                "Can't Lose Them" => ['bg' => 'from-red-500 to-red-700', 'text' => 'text-red-100', 'icon' => 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                'Lost' => ['bg' => 'from-gray-500 to-gray-700', 'text' => 'text-gray-300', 'icon' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z'],
                'Others' => ['bg' => 'from-slate-500 to-slate-700', 'text' => 'text-slate-300', 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
            ];
        @endphp

        @foreach($rfm['segments'] as $segmentName => $segment)
            @php $colors = $segmentColors[$segmentName] ?? $segmentColors['Others']; @endphp
            <div class="relative overflow-hidden bg-gradient-to-br {{ $colors['bg'] }} rounded-2xl p-5 text-white shadow-lg">
                <div class="absolute -top-4 -right-4 w-20 h-20 bg-white/10 rounded-full pointer-events-none"></div>
                <div class="relative">
                    <div class="flex items-center justify-between mb-3">
                        <svg class="w-6 h-6 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $colors['icon'] }}"></path>
                        </svg>
                        <span class="text-2xl font-black tabular-nums">{{ $segment['count'] }}</span>
                    </div>
                    <p class="text-sm font-bold tracking-tight">{{ $segmentName }}</p>
                    <div class="mt-2 space-y-1 {{ $colors['text'] }}">
                        <p class="text-xs">Avg Spend: PKR {{ number_format($segment['avg_monetary'], 0) }}</p>
                        <p class="text-xs">Avg Orders: {{ $segment['avg_frequency'] }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    @endif

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200/60 p-6">
            <h3 class="text-base font-bold text-gray-900 mb-4">Segment Distribution</h3>
            <div class="h-72">
                <canvas id="segmentChart"></canvas>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200/60 p-6">
            <h3 class="text-base font-bold text-gray-900 mb-4">Revenue by Segment</h3>
            <div class="h-72">
                <canvas id="revenueSegmentChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Customer Details Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200/60 p-6">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between mb-5">
            <h3 class="text-base font-bold text-gray-900">Customer Details</h3>
            <span class="text-sm text-gray-500">{{ count($rfm['customers']) }} customers analyzed</span>
        </div>
        @if(count($rfm['customers']) > 0)
            <div class="overflow-x-auto rounded-xl border border-gray-200">
                <table class="w-full min-w-[720px]">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="text-left py-3 px-4 text-xs font-semibold uppercase tracking-wider text-gray-500">#</th>
                            <th scope="col" class="text-left py-3 px-4 text-xs font-semibold uppercase tracking-wider text-gray-500">Customer</th>
                            <th scope="col" class="text-center py-3 px-4 text-xs font-semibold uppercase tracking-wider text-gray-500">Segment</th>
                            <th scope="col" class="text-center py-3 px-4 text-xs font-semibold uppercase tracking-wider text-gray-500">R</th>
                            <th scope="col" class="text-center py-3 px-4 text-xs font-semibold uppercase tracking-wider text-gray-500">F</th>
                            <th scope="col" class="text-center py-3 px-4 text-xs font-semibold uppercase tracking-wider text-gray-500">M</th>
                            <th scope="col" class="text-right py-3 px-4 text-xs font-semibold uppercase tracking-wider text-gray-500">Last Purchase</th>
                            <th scope="col" class="text-right py-3 px-4 text-xs font-semibold uppercase tracking-wider text-gray-500">Orders</th>
                            <th scope="col" class="text-right py-3 px-4 text-xs font-semibold uppercase tracking-wider text-gray-500">Total Spend</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($rfm['customers']->take(25) as $index => $cust)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="py-3.5 px-4 text-sm text-gray-500 tabular-nums">{{ $index + 1 }}</td>
                                <td class="py-3.5 px-4">
                                    <p class="text-sm font-semibold text-gray-900">{{ $cust['customer']->name }}</p>
                                    <p class="text-xs text-gray-400">{{ $cust['customer']->phone ?? '' }}</p>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    @php
                                        $badgeColor = match($cust['segment']) {
                                            'Champions' => 'bg-emerald-100 text-emerald-700',
                                            'Loyal Customers' => 'bg-blue-100 text-blue-700',
                                            'At Risk' => 'bg-orange-100 text-orange-700',
                                            "Can't Lose Them" => 'bg-red-100 text-red-700',
                                            'Lost' => 'bg-gray-200 text-gray-700',
                                            'New Customers' => 'bg-cyan-100 text-cyan-700',
                                            default => 'bg-violet-100 text-violet-700',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-full text-xs font-bold {{ $badgeColor }}">{{ $cust['segment'] }}</span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-xs font-bold {{ $cust['r_score'] >= 4 ? 'bg-green-100 text-green-700' : ($cust['r_score'] >= 3 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                                        {{ $cust['r_score'] }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-xs font-bold {{ $cust['f_score'] >= 4 ? 'bg-green-100 text-green-700' : ($cust['f_score'] >= 3 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                                        {{ $cust['f_score'] }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-xs font-bold {{ $cust['m_score'] >= 4 ? 'bg-green-100 text-green-700' : ($cust['m_score'] >= 3 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                                        {{ $cust['m_score'] }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right text-sm text-gray-600 tabular-nums">{{ $cust['recency'] }}d ago</td>
                                <td class="py-3.5 px-4 text-right text-sm text-gray-600 tabular-nums">{{ $cust['frequency'] }}</td>
                                <td class="py-3.5 px-4 text-right text-sm font-bold text-gray-900 tabular-nums">PKR {{ number_format($cust['monetary'], 0) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-center text-gray-500 py-10 text-sm border border-dashed border-gray-300 rounded-xl bg-gray-50">No customer data available for RFM analysis.</p>
        @endif
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const segments = @json($rfm['segments']);
            const segmentNames = Object.keys(segments);
            const segmentCounts = segmentNames.map(name => segments[name].count);
            const segmentRevenues = segmentNames.map(name => segments[name].avg_monetary * segments[name].count);

            const colors = [
                'rgba(16, 185, 129, 0.85)', 'rgba(59, 130, 246, 0.85)', 'rgba(79, 70, 229, 0.85)',
                'rgba(6, 182, 212, 0.85)', 'rgba(139, 92, 246, 0.85)', 'rgba(245, 158, 11, 0.85)',
                'rgba(249, 115, 22, 0.85)', 'rgba(239, 68, 68, 0.85)', 'rgba(107, 114, 128, 0.85)',
                'rgba(148, 163, 184, 0.85)'
            ];

            if (segmentNames.length > 0) {
                new Chart(document.getElementById('segmentChart'), {
                    type: 'doughnut',
                    data: {
                        labels: segmentNames,
                        datasets: [{ data: segmentCounts, backgroundColor: colors, borderWidth: 3, borderColor: '#fff' }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'right', labels: { padding: 12, font: { size: 11 } } } }
                    }
                });

                new Chart(document.getElementById('revenueSegmentChart'), {
                    type: 'bar',
                    data: {
                        labels: segmentNames,
                        datasets: [{
                            label: 'Total Revenue',
                            data: segmentRevenues,
                            backgroundColor: colors,
                            borderRadius: 8,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: 'y',
                        plugins: { legend: { display: false } },
                        scales: {
                            x: { ticks: { callback: v => 'PKR ' + (v/1000).toFixed(0) + 'k' }, grid: { color: 'rgba(0,0,0,0.04)' } },
                            y: { grid: { display: false } }
                        }
                    }
                });
            }
        });
    </script>
</x-app-layout>
