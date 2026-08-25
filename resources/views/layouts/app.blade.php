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
    <body class="font-sans antialiased" id="ftpos-app-shell" data-ft-branch-id="{{ $currentBranchId ?? '' }}" x-data="{
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
                    <nav class="bg-white border-b border-gray-200 px-3 sm:px-4 md:px-5 py-3 sm:py-4 flex-shrink-0">
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
                                <button type="button"
                                    id="ftpos-connectivity-status"
                                    class="rounded-full px-3 py-1 text-xs font-semibold whitespace-nowrap bg-green-600 text-white"
                                    title="Connection status">
                                    Online
                                </button>
                                <span id="ftpos-pending-sync" class="hidden rounded-full px-3 py-1 text-xs font-semibold"></span>
                                @auth
                                    <x-quantity-alerts-bell />
                                    @if(Auth::user()->isAdmin())
                                        <div class="relative" x-data="{ open: false }">
                                            <button
                                                @click="open = !open"
                                                type="button"
                                                class="inline-flex items-center gap-2 px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-orange-500"
                                            >
                                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                                </svg>
                                                <span>{{ $currentBranch?->name ?? 'Phandu' }}</span>
                                                <svg class="w-4 h-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                            <div
                                                x-show="open"
                                                @click.outside="open = false"
                                                x-cloak
                                                class="absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50"
                                            >
                                                <div class="py-1">
                                                    <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase">Switch Branch</div>
                                                    @foreach($branchesForSwitcher ?? [] as $branch)
                                                        <form method="POST" action="{{ route('branches.switch') }}">
                                                            @csrf
                                                            <input type="hidden" name="branch_id" value="{{ $branch->id }}">
                                                            <button
                                                                type="submit"
                                                                class="w-full text-left px-4 py-2 text-sm {{ (int) ($currentBranchId ?? 0) === (int) $branch->id ? 'bg-orange-50 text-orange-700 font-medium' : 'text-gray-700 hover:bg-gray-100' }}"
                                                            >
                                                                {{ $branch->name }}
                                                                @if((int) ($currentBranchId ?? 0) === (int) $branch->id)
                                                                    <span class="text-xs text-orange-500 ml-1">(current)</span>
                                                                @endif
                                                            </button>
                                                        </form>
                                                    @endforeach
                                                    <div class="border-t border-gray-100 my-1"></div>
                                                    <a href="{{ route('branches.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                        Manage Branches
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    @elseif($currentBranch)
                                        <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-md text-sm text-gray-600 bg-gray-50 border border-gray-200">
                                            {{ $currentBranch->name }}
                                        </span>
                                    @endif
                                @endauth
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
                                        <form method="POST" action="{{ route('logout') }}" id="ftpos-logout-form">
                                            @csrf
                                            <x-dropdown-link :href="route('logout')"
                                                    onclick="window.ftposLogout(event)">
                                                {{ __('Log Out') }}
                                            </x-dropdown-link>
                                        </form>
                                    </x-slot>
                                </x-dropdown>
                            </div>
                        </div>
                    </nav>

                    <!-- Page Content -->
                    <main class="flex-1 overflow-y-auto p-2 sm:p-3 md:p-4">
                        {{ $slot }}
                    </main>

                    <!-- Footer -->
                    <footer class="mt-auto py-3 sm:py-4 px-3 sm:px-4 md:px-5 border-t border-gray-200 bg-white flex-shrink-0">
                        <div class="flex flex-col md:flex-row justify-between items-center text-sm text-gray-600 gap-2">
                            <div>2025 © FTPOS. All Right Reserved · Version {{ config('app.version', '1.1') }}</div>
                            <div>Design &amp; developed by <a href="https://wa.me/923330915166" target="_blank" rel="noopener noreferrer" class="text-orange-600 hover:text-orange-700 font-medium underline">Bakht Biland</a></div>
                        </div>
                    </footer>
                </div>
            </div>
        </div>

        @include('components.receipt-branding')
        @include('components.stock-alert-notify')
        @include('components.branch-switch-script')
        @include('components.session-guard-script')
    </body>
</html>
