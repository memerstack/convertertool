// assets/js/pwa.js
// Progressive Web App: install prompt, service worker, online/offline indicator.

(function () {
    'use strict';

    /* ─────────────────────────────────────────────────
       1. Service Worker registration
       ───────────────────────────────────────────────── */
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js', { scope: '/' })
                .then((reg) => {
                    console.log('[PWA] SW registered:', reg.scope);
                })
                .catch((err) => {
                    console.warn('[PWA] SW registration failed:', err);
                });
        });
    }

    /* ─────────────────────────────────────────────────
       2. Install prompt (Chrome / Edge / Android)
       ───────────────────────────────────────────────── */
    let deferredPrompt = null;
    const installBtn = document.getElementById('pwaInstallBtn');

    // Show the button only when the browser says we're installable
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        if (installBtn) installBtn.hidden = false;
    });

    // Hide once installed
    window.addEventListener('appinstalled', () => {
        deferredPrompt = null;
        if (installBtn) installBtn.hidden = true;
        console.log('[PWA] App installed');
    });

    // Handle clicks on the install button
    if (installBtn) {
        installBtn.addEventListener('click', async () => {
            // Case 1: Chrome/Edge/Android — use the native prompt
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                console.log('[PWA] Install outcome:', outcome);
                deferredPrompt = null;
                installBtn.hidden = true;
                return;
            }

            // Case 2: iOS Safari — show manual instructions
            const ua = navigator.userAgent.toLowerCase();
            const isIOS = /iphone|ipad|ipod/.test(ua);
            const isStandalone =
                window.matchMedia('(display-mode: standalone)').matches ||
                navigator.standalone === true;

            if (isIOS && !isStandalone) {
                alert(
                    'To install this app on your iPhone/iPad:\n\n' +
                    '1. Tap the Share button (square with arrow)\n' +
                    '2. Scroll down and tap "Add to Home Screen"\n' +
                    '3. Tap "Add"\n\n' +
                    'The app will appear on your home screen.'
                );
                return;
            }

            // Case 3: Already installed or unsupported
            alert('This app is already installed or your browser does not support installation.');
        });
    }

    // If already running as installed app, hide the button
    if (window.matchMedia('(display-mode: standalone)').matches ||
        window.navigator.standalone === true) {
        if (installBtn) installBtn.hidden = true;
    }

    /* ─────────────────────────────────────────────────
       3. Online / Offline indicator
       ───────────────────────────────────────────────── */
    const statusEl = document.getElementById('pwaOnlineStatus');
    const statusDot = document.getElementById('pwaOnlineDot');
    const statusText = document.getElementById('pwaOnlineText');

    function updateOnlineStatus() {
        if (!statusEl) return;
        const online = navigator.onLine;

        statusEl.classList.toggle('is-online', online);
        statusEl.classList.toggle('is-offline', !online);

        if (statusDot) statusDot.setAttribute('title', online ? 'Online' : 'Offline');
        if (statusText) statusText.textContent = online ? 'Online' : 'Offline';

        // Refresh the page when coming back online, IF we were offline
        // (so cached resource versions sync with the live server)
        if (online && statusEl.dataset.wasOffline === '1') {
            statusEl.dataset.wasOffline = '0';
            // Optional: soft reload — uncomment if you want auto-refresh
            // location.reload();
        }
        if (!online) {
            statusEl.dataset.wasOffline = '1';
        }
    }

    window.addEventListener('online', updateOnlineStatus);
    window.addEventListener('offline', updateOnlineStatus);
    updateOnlineStatus();

    /* ─────────────────────────────────────────────────
       4. Manual check button — re-tests connectivity
       ───────────────────────────────────────────────── */
    const checkBtn = document.getElementById('pwaCheckBtn');
    if (checkBtn) {
        checkBtn.addEventListener('click', async () => {
            if (!statusText) return;
            statusText.textContent = 'Checking…';

            try {
                // Fetch a tiny file with cache-busting — real network test
                const res = await fetch('/robots.txt?_=' + Date.now(), {
                    method: 'HEAD',
                    cache: 'no-store',
                });
                if (res.ok) {
                    statusText.textContent = 'Online';
                    statusEl.classList.add('is-online');
                    statusEl.classList.remove('is-offline');
                } else {
                    throw new Error('Bad response');
                }
            } catch (err) {
                statusText.textContent = 'Offline';
                statusEl.classList.add('is-offline');
                statusEl.classList.remove('is-online');
            }
        });
    }
})();