<x-app-layout>
    <x-slot name="header">Product Recommendations</x-slot>

    <!-- Explanation -->
    <div class="bg-gradient-to-r from-violet-50 to-purple-50 border border-violet-200 rounded-2xl p-5 mb-6">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-white border border-violet-200 flex items-center justify-center shadow-sm">
                <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-sm font-bold text-gray-900">Market Basket Analysis — "Frequently Bought Together"</p>
                <p class="text-sm text-gray-600 mt-1.5 leading-relaxed">This AI analyzes all your sales to discover which products are commonly purchased together. Use this to create bundle deals, improve POS suggestions, and increase average order value.</p>
            </div>
        </div>
    </div>

    @if($recommendations->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-8">
            @foreach($recommendations as $rec)
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200/60 hover:shadow-md transition-all duration-200 p-5">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="flex-1 bg-indigo-50 rounded-xl p-4 text-center min-w-0">
                            <div class="w-11 h-11 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-2.5">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                            </div>
                            <p class="text-sm font-semibold text-gray-900 truncate" title="{{ $rec['product_1']->name }}">{{ $rec['product_1']->name }}</p>
                        </div>

                        <div class="flex flex-col items-center gap-1.5 flex-shrink-0">
                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center shadow-lg shadow-violet-200">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                </svg>
                            </div>
                            <span class="text-xs font-bold text-violet-700 tabular-nums">{{ $rec['times_bought_together'] }}x</span>
                        </div>

                        <div class="flex-1 bg-emerald-50 rounded-xl p-4 text-center min-w-0">
                            <div class="w-11 h-11 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-2.5">
                                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                            </div>
                            <p class="text-sm font-semibold text-gray-900 truncate" title="{{ $rec['product_2']->name }}">{{ $rec['product_2']->name }}</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 text-xs text-gray-500 border-t border-gray-100 pt-3 mt-4">
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                            <span>Support: <strong class="text-gray-800 tabular-nums">{{ $rec['support'] }}%</strong></span>
                            <span>Confidence: <strong class="text-gray-800 tabular-nums">{{ $rec['confidence'] }}%</strong></span>
                        </div>
                        @php
                            $strengthClass = match($rec['strength']) {
                                'very_strong' => 'bg-emerald-100 text-emerald-700',
                                'strong' => 'bg-blue-100 text-blue-700',
                                'moderate' => 'bg-amber-100 text-amber-700',
                                default => 'bg-gray-100 text-gray-600',
                            };
                            $strengthLabel = match($rec['strength']) {
                                'very_strong' => 'Very Strong',
                                'strong' => 'Strong',
                                'moderate' => 'Moderate',
                                default => 'Weak',
                            };
                        @endphp
                        <span class="px-3 py-1 rounded-full text-xs font-bold {{ $strengthClass }}">{{ $strengthLabel }}</span>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- How to Use -->
        <div class="bg-gradient-to-br from-gray-50 to-white rounded-2xl border border-gray-200/60 p-6 shadow-sm">
            <h3 class="text-base font-bold text-gray-900 mb-5">How to Use These Insights</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="flex gap-4">
                    <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                        <span class="text-sm font-black text-indigo-700">1</span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-gray-900">Bundle Deals</p>
                        <p class="text-xs text-gray-500 mt-1 leading-relaxed">Create combo offers for frequently paired products to boost sales.</p>
                    </div>
                </div>
                <div class="flex gap-4">
                    <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0">
                        <span class="text-sm font-black text-emerald-700">2</span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-gray-900">POS Suggestions</p>
                        <p class="text-xs text-gray-500 mt-1 leading-relaxed">When a customer buys product A, suggest product B at the counter.</p>
                    </div>
                </div>
                <div class="flex gap-4">
                    <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                        <span class="text-sm font-black text-amber-700">3</span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-gray-900">Store Layout</p>
                        <p class="text-xs text-gray-500 mt-1 leading-relaxed">Place frequently paired products near each other in your store.</p>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200/60 p-14 text-center">
            <div class="mx-auto mb-6 w-24 h-24 rounded-2xl bg-gradient-to-br from-violet-100 to-purple-100 flex items-center justify-center">
                <svg class="w-12 h-12 text-violet-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-2">Not Enough Data Yet</h3>
            <p class="text-sm text-gray-500 max-w-md mx-auto leading-relaxed">Product recommendations will appear once you have enough sales with multiple items per order. Keep selling!</p>
        </div>
    @endif
</x-app-layout>
