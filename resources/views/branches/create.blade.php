<x-app-layout>
    <x-slot name="header">
        Create Branch
    </x-slot>

    <div class="mb-4">
        <nav class="text-sm text-gray-600">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-900">Dashboard</a>
            <span class="mx-2">></span>
            <a href="{{ route('branches.index') }}" class="hover:text-gray-900">Branches</a>
            <span class="mx-2">></span>
            <span class="text-gray-900 font-medium">Create Branch</span>
        </nav>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6 max-w-3xl">
        <p class="text-sm text-gray-600 mb-6">
            Create a new branch and its login user. That email and password will be used to access this branch.
        </p>

        <form method="POST" action="{{ route('branches.store') }}">
            @csrf

            <div class="mb-8">
                <h3 class="text-base font-semibold text-gray-800 mb-4">Branch</h3>
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Branch Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           id="name"
                           name="name"
                           value="{{ old('name') }}"
                           required
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
                           placeholder="Enter branch name">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mb-8">
                <h3 class="text-base font-semibold text-gray-800 mb-4">Branch Login User</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="user_name" class="block text-sm font-medium text-gray-700 mb-2">
                            User Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               id="user_name"
                               name="user_name"
                               value="{{ old('user_name') }}"
                               required
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
                               placeholder="Enter user name">
                        @error('user_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            Email <span class="text-red-500">*</span>
                        </label>
                        <input type="email"
                               id="email"
                               name="email"
                               value="{{ old('email') }}"
                               required
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
                               placeholder="user@example.com">
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            Password <span class="text-red-500">*</span>
                        </label>
                        <input type="password"
                               id="password"
                               name="password"
                               required
                               minlength="8"
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
                               placeholder="Min 8 characters">
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                            Confirm Password <span class="text-red-500">*</span>
                        </label>
                        <input type="password"
                               id="password_confirmation"
                               name="password_confirmation"
                               required
                               minlength="8"
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
                               placeholder="Confirm password">
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-md font-medium">
                    Create Branch
                </button>
                <a href="{{ route('branches.index') }}" class="px-4 py-2 text-gray-600 hover:text-gray-900">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</x-app-layout>
