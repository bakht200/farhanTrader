{{--
  Logout + expired-session fallback that does not wait for the Vite bundle.
  Always ends on /login so a cached app shell cannot keep the user "logged in".
--}}
<script>
(function () {
    var LOGIN_URL = '/login';
    var onLogin = (window.location.pathname || '').indexOf('/login') !== -1;

    function htmlLooksLikeLogin(html) {
        if (!html) {
            return false;
        }
        if (html.indexOf('data-ftpos-page="login"') !== -1) {
            return true;
        }
        if (html.indexOf('<div data-ftpos-page="dashboard"') !== -1) {
            return false;
        }
        return html.indexOf('Sign In') !== -1 && html.indexOf('name="email"') !== -1;
    }

    function paintLogin(html) {
        try {
            document.open();
            document.write(html);
            document.close();
            return true;
        } catch (e) {
            return false;
        }
    }

    function findCachedLogin() {
        if (!('caches' in window)) {
            return Promise.resolve(null);
        }
        return caches.keys().then(function (keys) {
            return keys.reduce(function (chain, name) {
                return chain.then(function (found) {
                    if (found) {
                        return found;
                    }
                    return caches.open(name).then(function (cache) {
                        return cache.match(LOGIN_URL, { ignoreSearch: true }).then(function (hit) {
                            return hit || cache.match(new URL(LOGIN_URL, window.location.origin).href, { ignoreSearch: true });
                        });
                    });
                });
            }, Promise.resolve(null));
        }).then(function (hit) {
            if (!hit) {
                return null;
            }
            return hit.text().then(function (html) {
                return htmlLooksLikeLogin(html) ? html : null;
            });
        }).catch(function () {
            return null;
        });
    }

    function goLogin() {
        function navigate() {
            window.location.replace(LOGIN_URL);
        }
        if (navigator.onLine === false) {
            findCachedLogin().then(function (html) {
                if (html && paintLogin(html) && document.querySelector('form[action*="login"]')) {
                    try {
                        window.history.replaceState(null, '', LOGIN_URL);
                    } catch (e) {}
                    return;
                }
                navigate();
            }).catch(navigate);
            return;
        }
        navigate();
    }

    function wipeClientSessionOnly() {
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
        // Keep the service worker and cached pages so this PC can sign in with Wi‑Fi off.
        return Promise.resolve();
    }

    if (navigator.serviceWorker) {
        navigator.serviceWorker.addEventListener('message', function (event) {
            if (!event.data || event.data.type !== 'SESSION_EXPIRED' || onLogin || window.__ftposLoggingOut) {
                return;
            }
            window.__ftposLoggingOut = true;
            wipeClientSessionOnly().finally(goLogin);
            setTimeout(goLogin, 800);
        });
    }

    if (!onLogin && navigator.onLine !== false) {
        try {
            fetch('/sync/ping', {
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function (res) {
                if ((res.status === 401 || res.status === 419) && !window.__ftposLoggingOut) {
                    window.__ftposLoggingOut = true;
                    wipeClientSessionOnly().finally(goLogin);
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
        if (navigator.onLine !== false) {
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
        }

        Promise.race([
            logoutReq,
            new Promise(function (resolve) { setTimeout(resolve, 2500); })
        ]).then(function () {
            return wipeClientSessionOnly();
        }).finally(goLogin);

        setTimeout(goLogin, 3000);
    };

    var logoutForm = document.getElementById('ftpos-logout-form');
    if (logoutForm) {
        logoutForm.addEventListener('submit', function (event) {
            event.preventDefault();
            window.ftposLogout(event);
        });
    }
})();
</script>
