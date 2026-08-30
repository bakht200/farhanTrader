@php
    $needsBranchModal = Auth::user()?->isAdmin()
        && ! ($currentBranchId ?? null)
        && ! request()->routeIs('branches.*', 'profile.*');
@endphp
@if($needsBranchModal)
    <div class="fixed inset-0 z-[10050] flex items-center justify-center bg-gray-900/60 px-4"
         role="dialog"
         aria-modal="true"
         aria-labelledby="ft-branch-required-title"
         data-ft-branch-required="1">
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
            <h2 id="ft-branch-required-title" class="text-lg font-semibold text-gray-900">Select a branch</h2>
            <p class="mt-2 text-sm text-gray-600">Choose a shop before viewing numbers or making changes.</p>
            <div class="mt-4 space-y-2 max-h-72 overflow-y-auto">
                @forelse($branchesForSwitcher ?? [] as $branch)
                    <form method="POST" action="{{ route('branches.switch') }}">
                        @csrf
                        <input type="hidden" name="branch_id" value="{{ $branch->id }}">
                        <button type="submit"
                            class="w-full rounded-lg border border-gray-200 px-4 py-3 text-left text-sm font-medium text-gray-800 hover:border-orange-400 hover:bg-orange-50">
                            {{ $branch->name }}
                        </button>
                    </form>
                @empty
                    <p class="text-sm text-gray-500">No active branches yet.</p>
                @endforelse
            </div>
            <a href="{{ route('branches.index') }}" class="mt-4 inline-block text-sm text-orange-600 hover:text-orange-700">
                Manage branches
            </a>
        </div>
    </div>
@endif
