<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@isset($header){{ $header }} - @endisset{{ config('app.name', 'Laravel') }}</title>

        <!-- Favicon -->
        <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex bg-[#F5F1E8]">
            <!-- Left side — brand panel with avatars -->
            <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden items-center justify-center"
                 style="background: linear-gradient(145deg, #fff7ed 0%, #ffedd5 40%, #fed7aa 100%);">
                {{-- Soft atmosphere shapes --}}
                <div class="absolute inset-0 pointer-events-none overflow-hidden" aria-hidden="true">
                    <div class="absolute -top-16 -left-10 w-72 h-72 rounded-full bg-orange-400/25 blur-2xl"></div>
                    <div class="absolute bottom-10 right-0 w-80 h-80 rounded-full bg-amber-500/20 blur-3xl"></div>
                    <div class="absolute top-1/3 left-1/4 w-40 h-40 rounded-full bg-orange-600/10 blur-xl"></div>
                </div>

                <div class="relative z-10 px-12 max-w-lg w-full">
                    <div class="mb-10">
                        <img src="{{ asset('logo.png') }}" alt="Farhan Traders" class="h-20 w-auto drop-shadow-sm">
                        <h1 class="mt-5 text-3xl font-bold tracking-tight text-gray-900">Farhan Traders</h1>
                        <p class="mt-2 text-base text-gray-600 leading-relaxed">
                            Wholesale &amp; retail POS for your team sell faster, stay in sync across branches.
                        </p>
                    </div>

                    {{-- Avatar cluster --}}
                    <div class="flex items-center -space-x-3 mb-6" aria-hidden="true">
                        @php
                            $avatars = [
                                ['bg' => '#ea580c', 'initials' => 'FT'],
                                ['bg' => '#c2410c', 'initials' => 'PH'],
                                ['bg' => '#9a3412', 'initials' => 'LG'],
                                ['bg' => '#f97316', 'initials' => 'AD'],
                                ['bg' => '#fb923c', 'initials' => 'POS'],
                            ];
                        @endphp
                        @foreach ($avatars as $avatar)
                            <div class="w-14 h-14 rounded-full border-4 border-[#fff7ed] flex items-center justify-center text-white text-sm font-bold shadow-md"
                                 style="background: {{ $avatar['bg'] }};">
                                {{ $avatar['initials'] }}
                            </div>
                        @endforeach
                        <div class="w-14 h-14 rounded-full border-4 border-[#fff7ed] bg-white flex items-center justify-center text-orange-600 text-xs font-semibold shadow-md">
                            +team
                        </div>
                    </div>

                    {{-- Feature chips with icon avatars --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div class="flex items-center gap-3 rounded-xl bg-white/70 backdrop-blur px-4 py-3 shadow-sm border border-orange-100">
                            <div class="w-10 h-10 rounded-full bg-orange-500 flex items-center justify-center text-white shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-gray-800">POS Ready</div>
                                <div class="text-xs text-gray-500">Fast checkout</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 rounded-xl bg-white/70 backdrop-blur px-4 py-3 shadow-sm border border-orange-100">
                            <div class="w-10 h-10 rounded-full bg-amber-600 flex items-center justify-center text-white shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-gray-800">Multi-branch</div>
                                <div class="text-xs text-gray-500">Stock per branch</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 rounded-xl bg-white/70 backdrop-blur px-4 py-3 shadow-sm border border-orange-100">
                            <div class="w-10 h-10 rounded-full bg-orange-700 flex items-center justify-center text-white shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-gray-800">Offline able</div>
                                <div class="text-xs text-gray-500">Sell without net</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 rounded-xl bg-white/70 backdrop-blur px-4 py-3 shadow-sm border border-orange-100">
                            <div class="w-10 h-10 rounded-full bg-stone-700 flex items-center justify-center text-white shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-gray-800">Team access</div>
                                <div class="text-xs text-gray-500">Roles &amp; branches</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right side with form -->
            <div class="w-full lg:w-1/2 flex flex-col justify-center items-center px-6 py-12">
                <div class="w-full max-w-md">
                    <!-- Logo and Branding -->
                    <div class="flex flex-col items-center justify-center mb-8">
                        <!-- Logo Image -->
                        <div class="mb-3">
                            <img src="{{ asset('logo.png') }}" alt="Farhan Traders Logo" class="h-16 w-auto">
                        </div>
                        <!-- Company Name in dark purple -->
                        <span class="text-2xl font-bold" style="color: #5A189A;">Farhan Traders System</span>
                    </div>

                    <!-- Form Card -->
                    <div class="bg-white rounded-lg shadow-xl p-8">
                        {{ $slot }}
                    </div>

                    <!-- Footer -->
                    <div class="mt-6 text-center text-sm text-gray-600 space-y-1">
                        <div>Copyrights © 2025 - FTPOS</div>
                        <div class="text-xs text-gray-500">Version 1.0</div>
                        <div class="text-xs text-gray-500">Design &amp; developed by <a href="https://wa.me/923330915166" target="_blank" rel="noopener noreferrer" class="text-orange-600 hover:text-orange-700 font-medium underline">Bakht Biland</a></div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
