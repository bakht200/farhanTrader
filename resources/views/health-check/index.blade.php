<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Health Check') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">System Status</h3>
                        <p class="text-sm text-gray-500">Checked at {{ $checkedAt->format('d M Y h:i A') }}</p>
                    </div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $allHealthy ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $allHealthy ? 'All Systems Healthy' : 'Attention Required' }}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach ($checks as $check)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-100">
                        <div class="p-5">
                            <div class="flex items-center justify-between">
                                <h4 class="text-base font-semibold text-gray-800">{{ $check['name'] }}</h4>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $check['ok'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $check['ok'] ? 'OK' : 'Failed' }}
                                </span>
                            </div>
                            <p class="mt-3 text-sm text-gray-600 break-words">{{ $check['detail'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
