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
    <body class="font-sans antialiased" x-data="{
        sidebarOpen: (() => {
            let savedState = localStorage.getItem('sidebarOpen');
            if (savedState !== null) {
                return savedState === 'true';
            } else {
                return window.innerWidth >= 768;
            }
        })(),
        toggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
        }
    }" x-init="
        $watch('sidebarOpen', value => {
            localStorage.setItem('sidebarOpen', value);
        });
    ">
        <div class="min-h-screen bg-gray-100">
            <!-- Overlay for mobile -->
            <div 
                x-show="sidebarOpen"
                x-transition:enter="transition-opacity ease-linear duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-linear duration-300"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="sidebarOpen = false"
                class="fixed inset-0 bg-gray-600 bg-opacity-75 z-20 md:hidden"
                style="display: none;"
                x-cloak
            ></div>

            <div class="flex h-screen overflow-hidden">
                <!-- Sidebar -->
                <x-sidebar />

                <!-- Main Content -->
                <div 
                    class="flex-1 w-full transition-all duration-300 ease-in-out flex flex-col h-screen overflow-hidden"
                >
                    <!-- Top Navigation -->
                    <nav class="bg-white border-b border-gray-200 px-6 py-4 flex-shrink-0">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <!-- Sidebar Toggle Button - Mobile -->
                                <button 
                                    @click="toggleSidebar()"
                                    class="md:hidden p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500"
                                    aria-label="Toggle sidebar"
                                >
                                    <svg x-show="!sidebarOpen" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                                    </svg>
                                    <svg x-show="sidebarOpen" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                                <!-- Sidebar Toggle Button - Desktop -->
                                <button 
                                    @click="toggleSidebar()"
                                    class="hidden md:flex items-center justify-center p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500 transition-colors"
                                    aria-label="Toggle sidebar"
                                    title="Toggle sidebar"
                                >
                                    <svg x-show="sidebarOpen" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    <svg x-show="!sidebarOpen" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                                    </svg>
                                </button>
                                <h2 class="text-xl font-semibold text-gray-800">
                                    @isset($header)
                                        {{ $header }}
                                    @else
                                        Dashboard
                                    @endisset
                                </h2>
                            </div>
                            <div class="flex items-center space-x-4">
                                <!-- User Menu -->
                                <x-dropdown align="right" width="48">
                                    <x-slot name="trigger">
                                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                                            <div>{{ Auth::user()->name }}</div>
                                            <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </x-slot>

                                    <x-slot name="content">
                                        <x-dropdown-link :href="route('profile.edit')">
                                            {{ __('Profile') }}
                                        </x-dropdown-link>

                                        <!-- Authentication -->
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <x-dropdown-link :href="route('logout')"
                                                    onclick="event.preventDefault();
                                                            this.closest('form').submit();">
                                                {{ __('Log Out') }}
                                            </x-dropdown-link>
                                        </form>
                                    </x-slot>
                                </x-dropdown>
                            </div>
                        </div>
                    </nav>

                    <!-- Page Content -->
                    <main class="flex-1 overflow-y-auto p-6">
                        {{ $slot }}
                    </main>

                    <!-- Footer -->
                    <footer class="mt-auto py-4 px-6 border-t border-gray-200 bg-white flex-shrink-0">
                        <div class="flex flex-col md:flex-row justify-between items-center text-sm text-gray-600 gap-2">
                            <div>2025 © FTPOS. All Right Reserved</div>
                            <div>Designed & Developed By Shahzeb</div>
                        </div>
                    </footer>
                </div>
            </div>
        </div>
    </body>
</html>
