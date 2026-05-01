import "./bootstrap";
import "@fortawesome/fontawesome-free/css/all.min.css";
import Alpine from "alpinejs";
import Chart from "chart.js/auto";

window.Alpine = Alpine;
window.Chart = Chart;

Alpine.start();

// PWA: Service Worker registration
if ("serviceWorker" in navigator) {
    window.addEventListener("load", () => {
        navigator.serviceWorker.register("/sw.js").catch(() => {});
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
