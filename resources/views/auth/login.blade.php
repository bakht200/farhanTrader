<x-guest-layout>
    <x-slot name="header">Login</x-slot>
    
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="mb-6">
        <h2 class="text-3xl font-bold text-gray-800 mb-2">Sign In</h2>
        <p class="text-gray-600">Access the Farhan Traders panel using your email and passcode.</p>
    </div>

    <div id="ftpos-offline-gate" class="hidden mb-4 rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        <p class="font-semibold mb-1">Offline access not set up</p>
        <p data-gate-detail>This device has never signed in while online. Connect to the internet and log in once. After that, you can use the full system offline.</p>
        <button type="button" class="mt-2 text-orange-700 underline" onclick="window.FTOffline?.checkNow?.().then((ok)=> ok && window.location.reload())">Retry connection</button>
    </div>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div class="mb-4">
            <div class="flex items-center justify-between mb-2">
                <x-input-label for="email" :value="__('Email *')" class="text-gray-700 font-medium" />
            </div>
            <div class="relative">
                <x-text-input id="email" 
                    class="block w-full px-4 py-2 pr-10 border border-gray-300 rounded-md focus:ring-2 focus:ring-orange-500 focus:border-orange-500" 
                    type="email" 
                    name="email" 
                    :value="old('email')" 
                    required 
                    autofocus 
                    autocomplete="username" 
                    placeholder="Enter your email" />
                <!-- Calendar Icon -->
                <div class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 pointer-events-none">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mb-4">
            <div class="flex items-center justify-between mb-2">
                <x-input-label for="password" :value="__('Password *')" class="text-gray-700 font-medium" />
            </div>
            <div class="relative">
                <x-text-input id="password" 
                    class="block w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-orange-500 focus:border-orange-500 pr-10"
                    type="password"
                    name="password"
                    required 
                    autocomplete="current-password"
                    placeholder="Enter your password" />
                <button type="button" 
                    onclick="togglePassword('password')"
                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700 focus:outline-none">
                    <svg id="eye-password" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                    <svg id="eye-off-password" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center mb-6">
            <input id="remember_me" 
                type="checkbox" 
                class="w-4 h-4 text-orange-600 border-gray-300 rounded focus:ring-orange-500" 
                name="remember">
            <label for="remember_me" class="ml-2 text-sm text-gray-700">
                {{ __('Remember Me') }}
            </label>
        </div>

        <!-- Submit Button -->
        <div class="mb-4">
            <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold py-3 px-4 rounded-md transition duration-200 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                Sign In
            </button>
        </div>

        @if (Route::has('register'))
            <!-- Create Account Link -->
            <div class="text-center">
                <span class="text-sm text-gray-600">New on our platform? </span>
                <a class="text-sm text-orange-600 hover:text-orange-700 underline font-medium" href="{{ route('register') }}">
                    Create an account
                </a>
            </div>
        @endif
    </form>

    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const eye = document.getElementById('eye-' + inputId);
            const eyeOff = document.getElementById('eye-off-' + inputId);
            
            if (input && eye && eyeOff) {
                if (input.type === 'password') {
                    input.type = 'text';
                    eye.classList.add('hidden');
                    eyeOff.classList.remove('hidden');
                } else {
                    input.type = 'password';
                    eye.classList.remove('hidden');
                    eyeOff.classList.add('hidden');
                }
            }
        }
    </script>
</x-guest-layout>
