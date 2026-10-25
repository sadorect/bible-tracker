import "./bootstrap";
import "@fortawesome/fontawesome-free/css/all.min.css";
import Alpine from "alpinejs";
import Chart from "chart.js/auto";

window.Alpine = Alpine;
window.Chart = Chart;

Alpine.start();

// PWA: service worker registration + update handling
if ("serviceWorker" in navigator) {
    window.addEventListener("load", async () => {
        try {
            const registration = await navigator.serviceWorker.register("/sw.js");

            // When a new worker is waiting, activate it and reload once so the
            // user is not left on a stale shell after a deploy.
            registration.addEventListener("updatefound", () => {
                const installing = registration.installing;
                if (!installing) return;

                installing.addEventListener("statechange", () => {
                    if (
                        installing.state === "installed" &&
                        navigator.serviceWorker.controller
                    ) {
                        installing.postMessage("SKIP_WAITING");
                    }
                });
            });

            let reloading = false;
            navigator.serviceWorker.addEventListener("controllerchange", () => {
                if (reloading) return;
                reloading = true;
                window.location.reload();
            });
        } catch (e) {
            // A missing/blocked worker must never break the page.
        }
    });
}

// PWA: Install prompt
let deferredPrompt = null;

window.addEventListener("beforeinstallprompt", (e) => {
    e.preventDefault();
    deferredPrompt = e;
    const banner = document.getElementById("pwa-install-banner");
    if (banner) banner.classList.remove("hidden");
});

window.addEventListener("appinstalled", () => {
    deferredPrompt = null;
    const banner = document.getElementById("pwa-install-banner");
    if (banner) banner.classList.add("hidden");
});

window.pwaInstall = () => {
    if (!deferredPrompt) return;
    deferredPrompt.prompt();
    deferredPrompt.userChoice.then(() => {
        deferredPrompt = null;
    });
    const banner = document.getElementById("pwa-install-banner");
    if (banner) banner.classList.add("hidden");
};
