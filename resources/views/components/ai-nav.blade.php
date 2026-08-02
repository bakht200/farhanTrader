@props(['active' => 'index'])

@php
    $items = [
        'index' => ['label' => 'AI Dashboard', 'route' => 'ai-insights.index'],
        'forecast' => ['label' => 'Sales Forecast', 'route' => 'ai-insights.forecast'],
        'inventory' => ['label' => 'ABC Analysis', 'route' => 'ai-insights.inventory'],
        'customers' => ['label' => 'Customer Segments', 'route' => 'ai-insights.customers'],
        'recommendations' => ['label' => 'Recommendations', 'route' => 'ai-insights.recommendations'],
        // 'anomalies' => ['label' => 'Anomaly Detection', 'route' => 'ai-insights.anomalies'],
    ];
@endphp

<div class="mb-6">
    <div class="overflow-x-auto">
        <div class="inline-flex min-w-full rounded-xl bg-white p-1 border border-gray-200/70 shadow-sm">
            @foreach($items as $key => $item)
                <a
                    href="{{ route($item['route']) }}"
                    class="whitespace-nowrap rounded-lg px-3.5 py-2 text-sm font-medium transition-colors {{ $active === $key ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}"
                >
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>
    </div>
</div>
@props(['active' => 'index'])

@php
    $tabs = [
        'index' => ['label' => 'Overview', 'route' => 'ai-insights.index'],
        'forecast' => ['label' => 'Forecast', 'route' => 'ai-insights.forecast'],
        'inventory' => ['label' => 'Inventory', 'route' => 'ai-insights.inventory'],
        'customers' => ['label' => 'Customers', 'route' => 'ai-insights.customers'],
        'recommendations' => ['label' => 'Recommendations', 'route' => 'ai-insights.recommendations'],
        // 'anomalies' => ['label' => 'Anomalies', 'route' => 'ai-insights.anomalies'],
    ];
@endphp

<nav class="mb-8 flex flex-wrap gap-2 rounded-2xl border border-gray-200/60 bg-white p-2 shadow-sm" aria-label="AI Insights sections">
    @foreach($tabs as $key => $tab)
        <a
            href="{{ route($tab['route']) }}"
            class="inline-flex items-center rounded-xl px-4 py-2.5 text-sm font-semibold transition-all duration-200 {{ $active === $key ? 'bg-gradient-to-r from-indigo-600 to-violet-600 text-white shadow-md shadow-indigo-200' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
        >
            {{ $tab['label'] }}
        </a>
    @endforeach
</nav>
