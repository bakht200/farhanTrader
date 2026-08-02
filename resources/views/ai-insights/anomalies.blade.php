<x-app-layout>
    <x-slot name="header">Anomaly Detection</x-slot>

    <!-- Explanation -->
    <div class="bg-gradient-to-r from-red-50 to-orange-50 border border-red-200 rounded-2xl p-5 mb-6">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-white border border-red-200 flex items-center justify-center shadow-sm">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            <div>
                <p class="text-sm font-bold text-gray-900">Statistical Anomaly Detection</p>
                <p class="text-sm text-gray-600 mt-1.5 leading-relaxed">This AI uses Z-score analysis to detect unusual patterns in your sales data. It flags transactions, discounts, and price changes that deviate significantly from normal behavior.</p>
            </div>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-6">
        @php
            $highSeverity = collect($anomalies)->where('severity', 'high')->count();
            $mediumSeverity = collect($anomalies)->where('severity', 'medium')->count();
            $salesAnomalies = collect($anomalies)->where('type', 'sales_amount')->count();
            $discountAnomalies = collect($anomalies)->where('type', 'high_discount')->count();
            $priceAnomalies = collect($anomalies)->where('type', 'price_drop')->count();
        @endphp

        <div class="bg-white rounded-2xl shadow-sm border border-gray-200/60 p-5 hover:shadow-md transition-all duration-200">
            <div class="flex items-start justify-between gap-3 mb-3">
                <p class="text-sm font-semibold text-gray-500">Total Anomalies</p>
                <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-gradient-to-br from-red-500 to-orange-500 flex items-center justify-center shadow-lg shadow-red-200">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-black tracking-tight text-gray-900">{{ count($anomalies) }}</p>
            <div class="flex items-center gap-3 mt-3 text-xs">
                <span class="px-2 py-0.5 rounded-full bg-red-100 text-red-700 font-bold">{{ $highSeverity }} high</span>
                <span class="px-2 py-0.5 rounded-full bg-orange-100 text-orange-700 font-bold">{{ $mediumSeverity }} medium</span>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-200/60 p-5 hover:shadow-md transition-all duration-200">
            <div class="flex items-center justify-between mb-3">
                <p class="text-sm font-semibold text-gray-500">By Type</p>
                <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
            </div>
            <div class="space-y-2.5 mt-1">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Sales Amount</span>
                    <span class="text-sm font-bold tabular-nums text-gray-900">{{ $salesAnomalies }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">High Discounts</span>
                    <span class="text-sm font-bold tabular-nums text-gray-900">{{ $discountAnomalies }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Price Drops</span>
                    <span class="text-sm font-bold tabular-nums text-gray-900">{{ $priceAnomalies }}</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-200/60 p-5 hover:shadow-md transition-all duration-200">
            <div class="flex items-center justify-between mb-3">
                <p class="text-sm font-semibold text-gray-500">Detection Period</p>
                <div class="w-10 h-10 rounded-xl bg-purple-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>
            <p class="text-lg font-black text-gray-900">Last 90 Days</p>
            <p class="text-xs text-gray-500 mt-2 leading-relaxed">Z-score threshold > 2.0</p>
        </div>
    </div>

    <!-- Anomalies List -->
    @if(count($anomalies) > 0)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200/60 p-5 md:p-6">
            <h3 class="text-base font-bold text-gray-900 mb-5">Detected Anomalies</h3>
            <div class="space-y-4">
                @foreach($anomalies as $anomaly)
                    <div class="flex items-start gap-4 bg-white rounded-2xl border-2 p-5 {{ $anomaly['severity'] === 'high' ? 'border-red-200 bg-red-50/30' : 'border-orange-200 bg-orange-50/30' }}">
                        <div class="flex-shrink-0">
                            @if($anomaly['severity'] === 'high')
                                <div class="w-10 h-10 rounded-xl bg-red-100 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                    </svg>
                                </div>
                            @else
                                <div class="w-10 h-10 rounded-xl bg-orange-100 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            @endif
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2 mb-2">
                                @php
                                    $typeLabel = match($anomaly['type']) {
                                        'sales_amount' => 'Sales Amount',
                                        'high_discount' => 'High Discount',
                                        'price_drop' => 'Price Drop',
                                        default => 'Unknown',
                                    };
                                    $typeBadge = match($anomaly['type']) {
                                        'sales_amount' => 'bg-blue-100 text-blue-700',
                                        'high_discount' => 'bg-purple-100 text-purple-700',
                                        'price_drop' => 'bg-red-100 text-red-700',
                                        default => 'bg-gray-100 text-gray-700',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $typeBadge }}">{{ $typeLabel }}</span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $anomaly['severity'] === 'high' ? 'bg-red-100 text-red-700' : 'bg-orange-100 text-orange-700' }}">
                                    {{ ucfirst($anomaly['severity']) }} Severity
                                </span>
                            </div>
                            <p class="text-sm font-semibold text-gray-900 leading-snug">{{ $anomaly['message'] }}</p>
                            <div class="flex flex-wrap items-center gap-x-2 gap-y-1 mt-2.5 text-xs text-gray-500">
                                <span>{{ $anomaly['date'] }}</span>
                                @if(isset($anomaly['z_score']))
                                    <span class="text-gray-300">|</span>
                                    <span>Z-Score: <strong>{{ abs($anomaly['z_score']) }}</strong></span>
                                @endif
                                @if(isset($anomaly['expected']))
                                    <span class="text-gray-300">|</span>
                                    <span>Expected: <strong>PKR {{ number_format($anomaly['expected'], 0) }}</strong></span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200/60 p-14 text-center">
            <div class="mx-auto w-20 h-20 rounded-2xl bg-gradient-to-br from-emerald-100 to-green-100 flex items-center justify-center mb-6">
                <svg class="w-10 h-10 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-2">No Anomalies Detected</h3>
            <p class="text-sm text-gray-500 max-w-md mx-auto leading-relaxed">Your recent sales data falls within expected statistical ranges. Monitoring continues on a rolling 90-day window.</p>
        </div>
    @endif
</x-app-layout>
