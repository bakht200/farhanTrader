{{--
  Branch switch sync (inline, does not wait for Vite bundle).
  - Switching tab: flash sets payload, notifies other tabs, clears offline page caches
  - Other tabs: listen and reload into dashboard so stock/sales match the new branch
--}}
<script>
(function () {
    var CHANNEL = 'ftpos-offline';

    function currentBranchId() {
        return Number((document.body && document.body.getAttribute('data-ft-branch-id')) || 0);
    }

    function clearFtposCaches() {
        if (!('caches' in window)) {
            return Promise.resolve();
        }
        return caches.keys().then(function (keys) {
            return Promise.all(
                keys.filter(function (k) { return k.indexOf('ftpos-') === 0; })
                    .map(function (k) { return caches.delete(k); })
            );
        }).catch(function () {});
    }

    function goDashboardFresh() {
        clearFtposCaches().finally(function () {
            var target = '/dashboard?branch_switched=1&_ts=' + Date.now();
            if (window.location.pathname.indexOf('/dashboard') === 0) {
                window.location.replace(target);
            } else {
                window.location.href = target;
            }
        });
    }

    // Always listen for branch changes from other tabs
    try {
        var ch = new BroadcastChannel(CHANNEL);
        ch.addEventListener('message', function (ev) {
            var msg = ev && ev.data;
            if (!msg || msg.type !== 'branch-changed') {
                return;
            }
            var switchedId = Number((msg.payload && msg.payload.id) || 0);
            var here = currentBranchId();
            if (here && switchedId && here === switchedId) {
                return;
            }
            // Mark so we don't loop if this tab also had flash
            window.__ftBranchSwitchHandled = true;
            goDashboardFresh();
        });
    } catch (e) {}

    @if (session('branch_switched'))
    window.__ftBranchSwitched = @json(session('branch_switched'));

    // This tab just switched — tell others and refresh offline caches
    (function () {
        if (window.__ftBranchSwitchHandled) {
            return;
        }
        window.__ftBranchSwitchHandled = true;
        var payload = window.__ftBranchSwitched || {};
        try {
            var ch2 = new BroadcastChannel(CHANNEL);
            ch2.postMessage({
                type: 'branch-changed',
                payload: { id: Number(payload.id || 0), name: payload.name || '' },
                at: Date.now()
            });
        } catch (e) {}

        clearFtposCaches().then(function () {
            // Soft-refresh shell data if offline runtime is already up
            if (window.FTOffline && typeof window.FTOffline.bootstrap === 'function' && window.FTOffline.isOnline && window.FTOffline.isOnline()) {
                return window.FTOffline.bootstrap().catch(function () {});
            }
        }).catch(function () {});
    })();
    @endif
})();
</script>
