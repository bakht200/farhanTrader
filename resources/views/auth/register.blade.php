<x-guest-layout>
    <x-slot name="header">Register</x-slot>
    
    <div class="mb-6">
        <h2 class="text-3xl font-bold text-gray-800 mb-2">Create an Account</h2>
        <p class="text-gray-600">Access the Farhan Traders panel using your email and passcode.</p>
    </div>

    <form method="POST" action="{{ route('register') }}">
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
                    autocomplete="new-password"
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

        <!-- Confirm Password -->
        <div class="mb-4">
            <div class="flex items-center justify-between mb-2">
                <x-input-label for="password_confirmation" :value="__('Confirm Password *')" class="text-gray-700 font-medium" />
            </div>
            <div class="relative">
                <x-text-input id="password_confirmation" 
                    class="block w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-orange-500 focus:border-orange-500 pr-10"
                    type="password"
                    name="password_confirmation"
                    required 
                    autocomplete="new-password"
                    placeholder="Confirm your password" />
                <button type="button" 
                    onclick="togglePassword('password_confirmation')"
                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700 focus:outline-none">
                    <svg id="eye-password_confirmation" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                    <svg id="eye-off-password_confirmation" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <!-- Submit Button -->
        <div class="mb-4">
            <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold py-3 px-4 rounded-md transition duration-200 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                Create account
            </button>
        </div>

        <!-- Sign In Link -->
        <div class="text-center">
            <span class="text-sm text-gray-600">Already have an account? </span>
            <a class="text-sm text-orange-600 hover:text-orange-700 underline font-medium" href="{{ route('login') }}">
                Sign In
            </a>
        </div>
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
