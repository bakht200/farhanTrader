<x-app-layout>
    <x-slot name="header">
        Branches
    </x-slot>

    <div class="mb-4">
        <nav class="text-sm text-gray-600">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-900">Dashboard</a>
            <span class="mx-2">></span>
            <span class="text-gray-900 font-medium">Branches</span>
        </nav>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-lg shadow-sm p-4 mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <p class="text-sm text-gray-600">
            Manage branches and their login users. Existing data belongs to <strong>Phandu</strong> (branch 1).
        </p>
        <a href="{{ route('branches.create') }}" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-md font-medium inline-flex items-center whitespace-nowrap">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Create Branch
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Users</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Active</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($branches as $branch)
                        <tr class="{{ (int) $currentBranchId === (int) $branch->id ? 'bg-orange-50' : '' }}">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $branch->id }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $branch->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($branch->is_active)
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                                @else
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Inactive</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                @if($branch->users->isEmpty())
                                    <span class="text-gray-400">No branch users</span>
                                @else
                                    <ul class="space-y-1">
                                        @foreach($branch->users as $user)
                                            <li>
                                                <span class="font-medium">{{ $user->name }}</span>
                                                <span class="text-gray-500">({{ $user->email }})</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if((int) $currentBranchId === (int) $branch->id)
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800">Viewing</span>
                                @elseif($branch->is_active)
                                    <form method="POST" action="{{ route('branches.switch') }}">
                                        @csrf
                                        <input type="hidden" name="branch_id" value="{{ $branch->id }}">
                                        <button type="submit" class="text-blue-600 hover:text-blue-800 font-medium">
                                            Switch
                                        </button>
                                    </form>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                <a href="{{ route('branches.edit', $branch) }}" class="text-orange-600 hover:text-orange-800 font-medium">
                                    Edit
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">No branches found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
