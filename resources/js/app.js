// Installable app (PWA): register the service worker and keep the browser's
// install prompt so the "Instalar la app" button can show it later.
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js'));
}

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    window.deferredInstallPrompt = event;
    window.dispatchEvent(new CustomEvent('app-installable'));
});

window.addEventListener('appinstalled', () => {
    window.deferredInstallPrompt = null;
});
