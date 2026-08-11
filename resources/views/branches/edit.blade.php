<x-app-layout>
    <x-slot name="header">
        Edit Branch
    </x-slot>

    <div class="mb-4">
        <nav class="text-sm text-gray-600">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-900">Dashboard</a>
            <span class="mx-2">></span>
            <a href="{{ route('branches.index') }}" class="hover:text-gray-900">Branches</a>
            <span class="mx-2">></span>
            <span class="text-gray-900 font-medium">{{ $branch->name }}</span>
        </nav>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-lg shadow-sm p-6 mb-6 max-w-3xl">
        <h3 class="text-base font-semibold text-gray-800 mb-4">Branch Details</h3>
        <form method="POST" action="{{ route('branches.update', $branch) }}">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Branch Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           id="name"
                           name="name"
                           value="{{ old('name', $branch->name) }}"
                           required
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-end">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox"
                               name="is_active"
                               value="1"
                               {{ old('is_active', $branch->is_active) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-orange-500 focus:ring-orange-500">
                        Active
                    </label>
                </div>
            </div>

            <div class="mt-8 pt-6 border-t border-gray-200">
                <h4 class="text-sm font-semibold text-gray-800 mb-1">Receipt / Print Header</h4>
                <p class="text-xs text-gray-500 mb-4">Shown on this branch’s POS and order receipts. If empty, users will be asked to set it on first print.</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label for="receipt_title" class="block text-sm font-medium text-gray-700 mb-2">Title <span class="text-gray-400 font-normal">(required for print)</span></label>
                        <input type="text" id="receipt_title" name="receipt_title" value="{{ old('receipt_title', $branch->receipt_title) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
                               placeholder="e.g. FARHAN TRADERS">
                        @error('receipt_title')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label for="receipt_subtitle" class="block text-sm font-medium text-gray-700 mb-2">Subtitle</label>
                        <input type="text" id="receipt_subtitle" name="receipt_subtitle" value="{{ old('receipt_subtitle', $branch->receipt_subtitle) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
                               placeholder="e.g. Deals In Food Chemicals / Non Food Chemicals">
                        @error('receipt_subtitle')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="receipt_phone" class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                        <input type="text" id="receipt_phone" name="receipt_phone" value="{{ old('receipt_phone', $branch->receipt_phone) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
                               placeholder="Landline">
                        @error('receipt_phone')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="receipt_email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                        <input type="email" id="receipt_email" name="receipt_email" value="{{ old('receipt_email', $branch->receipt_email) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
                               placeholder="email@example.com">
                        @error('receipt_email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="receipt_mobile_1" class="block text-sm font-medium text-gray-700 mb-2">Mobile 1</label>
                        <input type="text" id="receipt_mobile_1" name="receipt_mobile_1" value="{{ old('receipt_mobile_1', $branch->receipt_mobile_1) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                        @error('receipt_mobile_1')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="receipt_mobile_2" class="block text-sm font-medium text-gray-700 mb-2">Mobile 2</label>
                        <input type="text" id="receipt_mobile_2" name="receipt_mobile_2" value="{{ old('receipt_mobile_2', $branch->receipt_mobile_2) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                        @error('receipt_mobile_2')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label for="receipt_address" class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                        <textarea id="receipt_address" name="receipt_address" rows="2"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
                                  placeholder="Branch address for receipts">{{ old('receipt_address', $branch->receipt_address) }}</textarea>
                        @error('receipt_address')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-md font-medium">
                    Save Branch
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <h3 class="text-base font-semibold text-gray-800 mb-2">Users on this branch</h3>
        <p class="text-sm text-gray-600 mb-4">These users can log in and are locked to this branch.</p>

        <div class="overflow-x-auto border border-gray-200 rounded-md">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($branch->users as $user)
                        <tr>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $user->name }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $user->email }}</td>
                            <td class="px-4 py-3 text-sm text-right">
                                <form method="POST"
                                      action="{{ route('branches.users.remove', [$branch, $user]) }}"
                                      onsubmit="return confirm('Remove {{ $user->name }} from {{ $branch->name }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 font-medium">
                                        Remove
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-6 text-center text-sm text-gray-500">No users assigned to this branch.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-2">Add existing user</h3>
            <p class="text-sm text-gray-600 mb-4">Assign a current branch user to this branch. If they belong to another branch, they will be moved here.</p>

            @if($availableUsers->isEmpty())
                <p class="text-sm text-gray-500">No other branch users available to add.</p>
            @else
                <form method="POST" action="{{ route('branches.users.add', $branch) }}">
                    @csrf
                    <input type="hidden" name="mode" value="existing">

                    <div class="mb-4">
                        <label for="user_id" class="block text-sm font-medium text-gray-700 mb-2">
                            User <span class="text-red-500">*</span>
                        </label>
                        <select id="user_id"
                                name="user_id"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                            <option value="">Select user</option>
                            @foreach($availableUsers as $user)
                                <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>
                                    {{ $user->name }} ({{ $user->email }})
                                    @if($user->branch_id)
                                        — currently: {{ $user->branch?->name ?? 'Branch #'.$user->branch_id }}
                                    @else
                                        — unassigned
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @error('user_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium">
                        Add User
                    </button>
                </form>
            @endif
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-2">Create new user</h3>
            <p class="text-sm text-gray-600 mb-4">Create a new login for this branch.</p>

            <form method="POST" action="{{ route('branches.users.add', $branch) }}">
                @csrf
                <input type="hidden" name="mode" value="new">

                <div class="space-y-4">
                    <div>
                        <label for="user_name" class="block text-sm font-medium text-gray-700 mb-2">
                            User Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               id="user_name"
                               name="user_name"
                               value="{{ old('user_name') }}"
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
                               minlength="8"
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
                               placeholder="Confirm password">
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-md font-medium">
                        Create & Add User
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="mt-6">
        <a href="{{ route('branches.index') }}" class="text-sm text-gray-600 hover:text-gray-900">← Back to Branches</a>
    </div>
</x-app-layout>
