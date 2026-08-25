{{--
  Logout + expired-session fallback that does not wait for the Vite bundle.
  Always ends on /login so a cached app shell cannot keep the user "logged in".
--}}
<script>
(function () {
    var LOGIN_URL = '/login';
    var onLogin = (window.location.pathname || '').indexOf('/login') !== -1;

    function goLogin() {
        window.location.replace(LOGIN_URL);
    }

    function wipeCachesAndWorker() {
        try {
            if (navigator.serviceWorker && navigator.serviceWorker.controller) {
                navigator.serviceWorker.controller.postMessage({ type: 'LOGOUT' });
            }
        } catch (e) {}
        try {
            if (window.FTOffline && typeof window.FTOffline.clearLocalSession === 'function') {
                window.FTOffline.clearLocalSession();
            }
        } catch (e) {}
        var jobs = [];
        if ('caches' in window) {
            jobs.push(caches.keys().then(function (keys) {
                return Promise.all(keys.filter(function (k) {
                    return k.indexOf('ftpos-') === 0;
                }).map(function (k) { return caches.delete(k); }));
            }).catch(function () {}));
        }
        if (navigator.serviceWorker && navigator.serviceWorker.getRegistrations) {
            jobs.push(navigator.serviceWorker.getRegistrations().then(function (regs) {
                return Promise.all(regs.map(function (reg) { return reg.unregister(); }));
            }).catch(function () {}));
        }
        return Promise.all(jobs).catch(function () {});
    }

    if (navigator.serviceWorker) {
        navigator.serviceWorker.addEventListener('message', function (event) {
            if (!event.data || event.data.type !== 'SESSION_EXPIRED' || onLogin || window.__ftposLoggingOut) {
                return;
            }
            window.__ftposLoggingOut = true;
            wipeCachesAndWorker().finally(goLogin);
            setTimeout(goLogin, 800);
        });
    }

    if (!onLogin) {
        try {
            fetch('/sync/ping', {
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function (res) {
                if ((res.status === 401 || res.status === 419) && !window.__ftposLoggingOut) {
                    window.__ftposLoggingOut = true;
                    wipeCachesAndWorker().finally(goLogin);
                    setTimeout(goLogin, 800);
                }
            }).catch(function () {});
        } catch (e) {}
    }

    window.ftposLogout = function (event) {
        if (event) {
            event.preventDefault();
        }
        if (window.__ftposLoggingOut) {
            goLogin();
            return;
        }
        window.__ftposLoggingOut = true;

        var form = document.getElementById('ftpos-logout-form');
        if (!form && event && event.target && event.target.closest) {
            form = event.target.closest('form');
        }

        var logoutReq = Promise.resolve();
        try {
            var body = form ? new FormData(form) : new FormData();
            var csrf = document.querySelector('meta[name="csrf-token"]');
            if (!form && csrf) {
                body.append('_token', csrf.getAttribute('content') || '');
            }
            var controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
            var timer = setTimeout(function () {
                if (controller) {
                    controller.abort();
                }
            }, 2500);
            logoutReq = fetch('/logout', {
                method: 'POST',
                credentials: 'same-origin',
                body: body,
                redirect: 'manual',
                keepalive: true,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                signal: controller ? controller.signal : undefined
            }).catch(function () {}).finally(function () {
                clearTimeout(timer);
            });
        } catch (e) {}

        Promise.race([
            logoutReq,
            new Promise(function (resolve) { setTimeout(resolve, 2500); })
        ]).then(function () {
            return wipeCachesAndWorker();
        }).finally(goLogin);

        setTimeout(goLogin, 3000);
    };
})();
</script>
