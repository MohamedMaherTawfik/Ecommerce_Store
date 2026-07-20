import { createApp } from "vue";
import "../css/app.css";
import App from "./App.vue";
import router from "./router";
import i18n, { resolveLocale } from "./i18n/index";
import { setInstallMode } from "./services/ApiClient";

// ===============================
// 🌙 Theme Init (IMPORTANT)
// ===============================
const theme = localStorage.getItem("theme");

if (!theme) {
    localStorage.setItem("theme", "light");
    document.documentElement.setAttribute("data-theme", "light");
} else {
    document.documentElement.setAttribute("data-theme", theme);
}
document.documentElement.setAttribute(
    "data-bs-theme",
    localStorage.getItem("theme") || "light",
);

// Enable install mode immediately on first load of /install
setInstallMode(["/install", "/installer"].includes(window.location.pathname));

// ===============================
// 🌐 i18n — Sync locale from route
// ===============================
router.afterEach((to) => {
    // Skip locale sync for install route — it has no :lang param
    if (to.path === "/install" || to.path === "/installer") {
        return;
    }

    // Validate the language before setting it
    const rawLang = to.params.lang || localStorage.getItem("language") || "en";
    const lang = resolveLocale(rawLang);

    localStorage.setItem("language", lang);
    i18n.global.locale.value = lang;
    document.documentElement.setAttribute("dir", lang === "ar" ? "rtl" : "ltr");
    document.documentElement.setAttribute("lang", lang);

    if (
        to.path.startsWith("/admin") ||
        to.path.includes("/auth") ||
        ["/cart", "/wishlist", "/profile", "/wallet", "/orders", "/support"].some(
            (segment) => to.path.includes(segment),
        )
    ) {
        let robots = document.head.querySelector('meta[name="robots"]');
        if (!robots) {
            robots = document.createElement("meta");
            robots.setAttribute("name", "robots");
            document.head.appendChild(robots);
        }
        robots.setAttribute("content", "noindex,nofollow");
    }
});

// ===============================

import { createPinia } from 'pinia';

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);
app.use(i18n);
app.mount("#app");
