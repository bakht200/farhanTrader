<x-app-layout>
    <x-slot name="header">
        Dashboard
    </x-slot>

    @if(! empty($needsBranchSelection))
        <div class="bg-white rounded-lg shadow p-8 text-center text-gray-600">
            Select a branch to see this store’s dashboard.
        </div>
    @else
    <!-- Welcome Message -->
    <div class="bg-gray-50 rounded-lg p-4 mb-4">
        <p class="text-gray-700">
            👋 Hi {{ explode(' ', $user->name)[0] }}, here's what's happening with your store today.
        </p>
    </div>

    <!-- Top Row - Metric Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <!-- Weekly Earning Card -->
        <div class="bg-white rounded-lg shadow-lg p-6 relative overflow-hidden">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Weekly Earning</p>
                    <div class="flex items-center gap-2">
                        <p id="weeklyEarningAmount" class="text-2xl font-bold text-gray-900" data-amount="{{ number_format($weeklyEarning, 2) }}">PKR ••••••</p>
                
                    </div>
                    <div class="flex items-center mt-2">
                        <svg class="w-4 h-4 text-green-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                        <span class="text-sm text-green-500 font-medium">{{ abs($weeklyEarningIncrease) }}% increase compare to last week</span>
                    </div>
                </div>
                <div class="text-4xl opacity-20">
                    <svg class="w-16 h-16 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                            <button id="toggleWeeklyEarning" type="button" class="text-gray-500 hover:text-gray-700 transition-colors cursor-pointer">
                            <svg id="eyeIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                            </svg>
                        </button>
                </div>
            </div>
        </div>

        <!-- Total Customers Card -->
        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg shadow-lg p-6 text-white relative overflow-hidden">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm text-orange-100 mb-1">Total Customers</p>
                    <p class="text-3xl font-bold">{{ number_format($totalCustomers) }}</p>
                </div>
                <div class="flex items-center space-x-2">
                    <svg class="w-12 h-12 text-orange-200 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m0-4a4 4 0 110-8 4 4 0 010 8zm8 4a3 3 0 10-6 0"></path>
                    </svg>
                    <button class="text-orange-100 hover:text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Receivable Card -->
        <div class="bg-gradient-to-br from-purple-600 to-purple-700 rounded-lg shadow-lg p-6 text-white relative overflow-hidden">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm text-purple-100 mb-1">Receivable (Unpaid)</p>
                    <p class="text-3xl font-bold">PKR {{ number_format($totalReceivable, 2) }}</p>
                </div>
                <div class="flex items-center space-x-2">
                    <svg class="w-12 h-12 text-purple-200 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <button class="text-purple-100 hover:text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="mb-6">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Recent Transactions</h3>
                <a href="{{ route('sales.index') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">View All</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-2 px-2 text-xs font-semibold text-gray-600">#</th>
                            <th class="text-left py-2 px-2 text-xs font-semibold text-gray-600">Order Details</th>
                            <th class="text-left py-2 px-2 text-xs font-semibold text-gray-600">Payment</th>
                            <th class="text-left py-2 px-2 text-xs font-semibold text-gray-600">Status</th>
                            <th class="text-right py-2 px-2 text-xs font-semibold text-gray-600">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentTransactions as $index => $transaction)
                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="py-3 px-2 text-sm text-gray-600">{{ $index + 1 }}</td>
                            <td class="py-3 px-2">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">
                                        @php
                                            // Check if this is an adjustment transaction (no items, ADJ- prefix)
                                            $isAdjustment = str_starts_with($transaction->sale_number ?? '', 'ADJ-') || $transaction->items->isEmpty();
                                            
                                            if ($isAdjustment) {
                                                $productName = 'Balance Adjustment';
                                            } else {
                                                $firstItem = $transaction->items->first();
                                                $productName = $firstItem?->product_name ?? $firstItem?->product?->name ?? 'N/A';
                                            }
                                        @endphp
                                        {{ $productName }}
                                    </p>
                                    <p class="text-xs text-gray-500">{{ $transaction->created_at->diffForHumans() }}</p>
                                </div>
                            </td>
                            <td class="py-3 px-2">
                                <p class="text-sm text-gray-600">{{ $transaction->payment_status ?? 'Cash' }}</p>
                                <p class="text-xs text-gray-500">#{{ $transaction->sale_number ?? $transaction->id }}</p>
                            </td>
                            <td class="py-3 px-2">
                                @php
                                    $statusClass = match($transaction->status ?? 'completed') {
                                        'completed' => 'bg-green-100 text-green-800',
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'returned' => 'bg-red-100 text-red-800',
                                        default => 'bg-gray-100 text-gray-800'
                                    };
                                @endphp
                                <span class="px-2 py-1 rounded-full text-xs font-medium {{ $statusClass }}">
                                    {{ ucfirst($transaction->status ?? 'Success') }}
                                </span>
                            </td>
                            <td class="py-3 px-2 text-right">
                                <p class="text-sm font-semibold text-gray-900">PKR {{ number_format($transaction->total_amount ?? 0, 2) }}</p>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="py-4 text-center text-gray-500">No recent transactions</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Bottom Row - Sales Analytics & Overall Information -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Sales Analytics -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Sales Analytics</h3>
                <div class="flex items-center space-x-2 text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <span class="text-sm font-medium">{{ date('Y') }}</span>
                </div>
            </div>
            <div class="h-64">
                <canvas id="salesChart"></canvas>
            </div>
        </div>

        <!-- Overall Information -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Overall Information</h3>
            
            <!-- Stats Cards -->
            <div class="grid grid-cols-2 gap-6 mb-6">
                <div class="text-center p-6 bg-gray-50 rounded-lg">
                    <div class="flex justify-center mb-2">
                        <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($totalSuppliers) }}</p>
                    <p class="text-xs text-gray-500 mt-1">Suppliers</p>
                </div>
                <div class="text-center p-6 bg-gray-50 rounded-lg">
                    <div class="flex justify-center mb-2">
                        <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($totalCustomers) }}</p>
                    <p class="text-xs text-gray-500 mt-1">Customer</p>
                </div>
              
            </div>
<!--  -->
        </div>
    </div>

    <!-- Chart.js for Sales Analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('salesChart');
            if (ctx) {
                const salesData = @json($salesAnalytics);
                
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: salesData.map(item => item.month),
                        datasets: [{
                            label: 'Sales',
                            data: salesData.map(item => item.amount),
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return (value / 1000).toFixed(0) + 'k';
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Toggle Weekly Earning visibility
            const toggleButton = document.getElementById('toggleWeeklyEarning');
            const amountElement = document.getElementById('weeklyEarningAmount');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (toggleButton && amountElement) {
                let isVisible = false; // Start with hidden
                const originalAmount = amountElement.getAttribute('data-amount');
                
                // Set initial icon state (hidden - slash eye)
                if (eyeIcon) {
                    eyeIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>';
                }
                
                toggleButton.addEventListener('click', function() {
                    isVisible = !isVisible;
                    
                    if (isVisible) {
                        amountElement.textContent = 'PKR ' + originalAmount;
                        eyeIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>';
                    } else {
                        amountElement.textContent = 'PKR ••••••';
                        eyeIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>';
                    }
                });
            }
        });
    </script>
    @endif
</x-app-layout>
