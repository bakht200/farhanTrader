{{-- After admin switches branch: refresh offline cache and notify other open tabs --}}
@if (session('branch_switched'))
    <script>
        window.__ftBranchSwitched = @json(session('branch_switched'));
    </script>
@endif
