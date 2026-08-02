<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@isset($header){{ $header }} - @endisset{{ config('app.name', 'Laravel') }}</title>

        <!-- Favicon -->
        <link rel="icon" type="image/png" href="{{ asset('build/assets/logo.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex bg-[#F5F1E8]">
            <!-- Left side with geometric shapes -->
            <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden">
                <div class="absolute inset-0">
                    <!-- Orange angular geometric shapes -->
                    <div class="absolute top-20 left-10 w-32 h-32 bg-orange-500 transform rotate-45 opacity-80" style="clip-path: polygon(50% 0%, 0% 100%, 100% 100%);"></div>
                    <div class="absolute top-40 left-32 w-24 h-24 bg-orange-400 transform -rotate-12 opacity-70" style="clip-path: polygon(30% 0%, 70% 0%, 100% 30%, 100% 70%, 70% 100%, 30% 100%, 0% 70%, 0% 30%);"></div>
                    <div class="absolute bottom-32 left-20 w-40 h-40 bg-orange-600 transform rotate-12 opacity-75" style="clip-path: polygon(50% 0%, 100% 38%, 82% 100%, 18% 100%, 0% 38%);"></div>
                    <div class="absolute bottom-20 left-48 w-28 h-28 bg-orange-300 transform -rotate-45 opacity-60" style="clip-path: polygon(25% 0%, 100% 0%, 75% 100%, 0% 100%);"></div>
                    <div class="absolute top-60 left-16 w-20 h-20 bg-orange-500 transform rotate-45 opacity-65" style="clip-path: polygon(50% 0%, 61% 35%, 98% 35%, 68% 57%, 79% 91%, 50% 70%, 21% 91%, 32% 57%, 2% 35%, 39% 35%);"></div>
                </div>
            </div>

            <!-- Right side with form -->
            <div class="w-full lg:w-1/2 flex flex-col justify-center items-center px-6 py-12">
                <div class="w-full max-w-md">
                    <!-- Logo and Branding -->
                    <div class="flex flex-col items-center justify-center mb-8">
                        <!-- Logo Image -->
                        <div class="mb-3">
                            <img src="{{ asset('build/assets/logo.png') }}" alt="Farhan Traders Logo" class="h-16 w-auto">
                        </div>
                        <!-- Company Name in dark purple -->
                        <span class="text-2xl font-bold" style="color: #5A189A;">Farhan Traders System</span>
                    </div>

                    <!-- Form Card -->
                    <div class="bg-white rounded-lg shadow-xl p-8">
                        {{ $slot }}
                    </div>

                    <!-- Footer -->
                    <div class="mt-6 text-center text-sm text-gray-600">
                        Copyrights © 2025 - FTPOS
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
