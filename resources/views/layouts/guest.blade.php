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

        <style>
            .ft-login-shell { min-height: 100vh; display: flex; background: #F5F1E8; }
            .ft-login-left {
                display: none;
                width: 50%;
                position: relative;
                overflow: hidden;
                align-items: center;
                justify-content: center;
                background: linear-gradient(145deg, #fff7ed 0%, #ffedd5 40%, #fed7aa 100%);
            }
            @media (min-width: 1024px) {
                .ft-login-left { display: flex; }
            }
            .ft-login-left-inner {
                position: relative;
                z-index: 2;
                padding: 48px;
                max-width: 520px;
                width: 100%;
            }
            .ft-login-logo { height: 80px; width: auto; display: block; }
            .ft-login-title {
                margin: 20px 0 0;
                font-size: 1.875rem;
                font-weight: 700;
                color: #111827;
                letter-spacing: -0.02em;
            }
            .ft-login-sub {
                margin: 8px 0 0;
                font-size: 1rem;
                line-height: 1.55;
                color: #4B5563;
            }
            .ft-avatars {
                display: flex;
                align-items: center;
                margin: 28px 0 24px;
            }
            .ft-avatar {
                width: 56px;
                height: 56px;
                border-radius: 9999px;
                border: 4px solid #fff7ed;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #fff;
                font-size: 13px;
                font-weight: 700;
                box-shadow: 0 4px 12px rgba(0,0,0,.12);
                margin-left: -12px;
                flex-shrink: 0;
            }
            .ft-avatars .ft-avatar:first-child { margin-left: 0; }
            .ft-avatar-more {
                background: #fff;
                color: #ea580c;
                font-size: 11px;
                font-weight: 600;
            }
            .ft-features {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 12px;
            }
            .ft-feature {
                display: flex;
                align-items: center;
                gap: 12px;
                border-radius: 12px;
                background: rgba(255,255,255,.75);
                border: 1px solid #ffedd5;
                padding: 12px 14px;
                box-shadow: 0 1px 3px rgba(0,0,0,.06);
            }
            .ft-feature-icon {
                width: 40px;
                height: 40px;
                border-radius: 9999px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #fff;
                flex-shrink: 0;
            }
            .ft-feature-title { font-size: 14px; font-weight: 600; color: #1f2937; }
            .ft-feature-desc { font-size: 12px; color: #6b7280; margin-top: 2px; }
            .ft-login-right {
                width: 100%;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                padding: 48px 24px;
            }
            @media (min-width: 1024px) {
                .ft-login-right { width: 50%; }
            }
            .ft-login-card-wrap { width: 100%; max-width: 28rem; }
        </style>
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="ft-login-shell">
            <!-- Left side — brand panel with avatars -->
            <div class="ft-login-left" aria-hidden="true">
                <div class="ft-login-left-inner">
                    <img src="{{ asset('logo.png') }}" alt="Farhan Traders" class="ft-login-logo">
                    <h1 class="ft-login-title">Farhan Traders</h1>
                    <p class="ft-login-sub">
                        Wholesale &amp; retail POS for your team — sell faster, stay in sync across branches.
                    </p>

                    <div class="ft-avatars">
                        <div class="ft-avatar" style="background:#ea580c;">FT</div>
                        <div class="ft-avatar" style="background:#c2410c;">PH</div>
                        <div class="ft-avatar" style="background:#9a3412;">LG</div>
                        <div class="ft-avatar" style="background:#f97316;">AD</div>
                        <div class="ft-avatar" style="background:#fb923c;">POS</div>
                        <div class="ft-avatar ft-avatar-more">+team</div>
                    </div>

                    <div class="ft-features">
                        <div class="ft-feature">
                            <div class="ft-feature-icon" style="background:#f97316;">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
                            </div>
                            <div>
                                <div class="ft-feature-title">POS Ready</div>
                                <div class="ft-feature-desc">Fast checkout</div>
                            </div>
                        </div>
                        <div class="ft-feature">
                            <div class="ft-feature-icon" style="background:#d97706;">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            </div>
                            <div>
                                <div class="ft-feature-title">Multi-branch</div>
                                <div class="ft-feature-desc">Stock per branch</div>
                            </div>
                        </div>
                        <div class="ft-feature">
                            <div class="ft-feature-icon" style="background:#c2410c;">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            </div>
                            <div>
                                <div class="ft-feature-title">Offline able</div>
                                <div class="ft-feature-desc">Sell without net</div>
                            </div>
                        </div>
                        <div class="ft-feature">
                            <div class="ft-feature-icon" style="background:#44403c;">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </div>
                            <div>
                                <div class="ft-feature-title">Team access</div>
                                <div class="ft-feature-desc">Roles &amp; branches</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right side with form -->
            <div class="ft-login-right">
                <div class="ft-login-card-wrap">
                    <div class="flex flex-col items-center justify-center mb-8">
                        <div class="mb-3">
                            <img src="{{ asset('logo.png') }}" alt="Farhan Traders Logo" class="h-16 w-auto">
                        </div>
                        <span class="text-2xl font-bold" style="color: #5A189A;">Farhan Traders System</span>
                    </div>

                    <div class="bg-white rounded-lg shadow-xl p-8">
                        {{ $slot }}
                    </div>

                    <div class="mt-6 text-center text-sm text-gray-600 space-y-1">
                        <div>Copyrights © 2025 - FTPOS</div>
                        <div class="text-xs text-gray-500">Version {{ config('app.version', '2.0') }}</div>
                        <div class="text-xs text-gray-500">Design &amp; developed by <a href="https://wa.me/923330915166" target="_blank" rel="noopener noreferrer" class="text-orange-600 hover:text-orange-700 font-medium underline">Bakht Biland</a></div>
                    </div>
                </div>
            </div>
        </div>
        @include('components.session-guard-script')
    </body>
</html>
