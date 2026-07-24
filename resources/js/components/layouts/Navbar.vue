<template>
    <header class="store-nav" :dir="lang === 'ar' ? 'rtl' : 'ltr'">
        <div class="store-nav__inner">
            <RouterLink :to="`/${lang}`" class="store-nav__brand">
                <img :src="layoutData.navbar?.value || 'images/ai_logo.webp'"
                    :alt="`${layoutData.navbar?.brand || 'EliteShop'} logo`" width="38" height="38"
                    decoding="async" fetchpriority="high" />
                <span>{{ layoutData.navbar?.brand || 'EliteShop' }}</span>
            </RouterLink>

            <!-- <nav class="store-nav__links d-none d-lg-flex">
                <RouterLink v-for="link in navLinks" :key="link.key" :to="resolveRoute(link.route)"
                    class="store-nav__link" :class="{ 'is-active': isActive(resolveRoute(link.route)) }">
                    {{ t(`nav.${link.key}`) }}
                </RouterLink>
            </nav> -->

            <form class="store-nav__search d-none d-md-flex" @submit.prevent="submitSearch">
                <i class="bi bi-search"></i>
                <input v-model.trim="searchText" type="text" :placeholder="t('nav.search')" />
            </form>

            <div class="store-nav__actions">
                <button class="icon-btn d-md-none" type="button" @click="searchOpen = !searchOpen"
                    :aria-label="t('nav.search')">
                    <i class="bi bi-search"></i>
                </button>

                <button class="lang-btn" type="button" @click="toggleLanguage">
                    {{ lang === 'ar' ? 'EN' : 'AR' }}
                </button>
                <!-- <button class="icon-btn" type="button" @click="toggleTheme" :title="t('nav.theme')">
                    <i class="bi" :class="theme === 'dark' ? 'bi-sun' : 'bi-moon'"></i>
                </button> -->

                <RouterLink v-if="isLoggedIn" class="icon-btn" :to="`/${lang}/wishlist`" :title="t('nav.wishlist')">
                    <i class="bi bi-heart"></i>
                </RouterLink>

                <button v-if="isLoggedIn" class="icon-btn icon-btn--count" type="button" @click="goToCart"
                    :title="t('nav.cart')">
                    <i class="bi bi-bag"></i>
                    <span v-if="cartCount" class="count-badge">{{ cartCount }}</span>
                </button>

                <div class="store-nav__auth">
                    <template v-if="isLoggedIn">
                        <button class="user-chip d-none d-sm-flex" type="button" @click="dropdownOpen = !dropdownOpen">
                            <i class="bi bi-person-circle"></i>
                            <span>{{ userName }}</span>
                            <i class="bi" :class="dropdownOpen ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                        </button>
                        <button class="icon-btn d-sm-none" type="button" @click="dropdownOpen = !dropdownOpen">
                            <i class="bi bi-person"></i>
                        </button>
                        <div v-if="dropdownOpen" class="user-menu">
                            <RouterLink :to="`/${lang}/profile`" class="user-menu__item">{{ t('nav.profile') }}
                            </RouterLink>
                            <RouterLink :to="`/${lang}/orders`" class="user-menu__item">Orders</RouterLink>
                            <RouterLink :to="`/${lang}/support`" class="user-menu__item">Support</RouterLink>
                            <button type="button" class="user-menu__item user-menu__item--danger" @click="logout">{{
                                t('nav.logout') }}</button>
                        </div>
                    </template>
                    <template v-else>
                        <RouterLink :to="`/${lang}/auth`" class="auth-btn d-none d-sm-flex">
                            {{ t('nav.login') }}
                        </RouterLink>
                        <RouterLink :to="`/${lang}/auth`" class="icon-btn d-sm-none" :title="t('nav.login')">
                            <i class="bi bi-box-arrow-in-right"></i>
                        </RouterLink>
                    </template>
                </div>
            </div>
        </div>

        <div v-if="searchOpen" class="store-nav__mobile-search d-md-none">
            <form @submit.prevent="submitSearch" class="store-nav__search">
                <i class="bi bi-search"></i>
                <input v-model.trim="searchText" type="text" :placeholder="t('nav.search')" />
            </form>
        </div>
    </header>

    <!-- Bottom Navigation (Mobile Only) -->
    <nav class="bottom-nav d-md-none">
        <RouterLink v-for="link in navLinks.slice(0, 4)" :key="link.key" :to="resolveRoute(link.route)"
            class="bottom-nav__item" :class="{ 'is-active': isActive(resolveRoute(link.route)) }" @click="mobileOpen = false">
            <i class="bi" :class="getIconForKey(link.key, isActive(resolveRoute(link.route)))"></i>
            <span>{{ t(`nav.${link.key}`) }}</span>
        </RouterLink>

        <button v-if="navLinks.length > 4" class="bottom-nav__item" type="button" @click="mobileOpen = !mobileOpen" :class="{ 'is-active': mobileOpen }">
            <i class="bi bi-three-dots"></i>
            <span>{{ lang === 'ar' ? 'المزيد' : 'More' }}</span>
        </button>

        <!-- More Links Menu -->
        <div v-if="mobileOpen" class="bottom-nav__more-menu">
            <RouterLink v-for="link in navLinks.slice(4)" :key="link.key" :to="resolveRoute(link.route)" class="bottom-nav__more-item"
                :class="{ 'is-active': isActive(resolveRoute(link.route)) }" @click="mobileOpen = false">
                {{ t(`nav.${link.key}`) }}
            </RouterLink>
        </div>
    </nav>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { RouterLink, useRoute, useRouter } from "vue-router";
import { useI18n } from "vue-i18n";
import api, { isInstallMode } from "@/services/ApiClient";
import authService from "@/services/auth/Authservice";
import { resetCartState, syncCartState, useCartState } from "@/composables/useCartState";
import LayoutService from "@/services/home/LayoutService";
import CartService from "@/services/home/CartService";

const route = useRoute();
const router = useRouter();
const { t } = useI18n();
const { cartCount } = useCartState();

const theme = ref("light");
const isLoggedIn = ref(false);
const userName = ref("User");
const dropdownOpen = ref(false);
const mobileOpen = ref(false);
const searchOpen = ref(false);
const searchText = ref("");
const initialLayout = LayoutService.getInitialLayoutData();
const layoutData = ref(initialLayout || { navbar: {}, footer: {} });

const lang = computed(() => route.params.lang || localStorage.getItem("language") || "en");

const navLinks = computed(() => {
    const links = (layoutData.value.navbar?.links || []).map((link) => ({
        ...link,
        route: normalizeRoute(link.route),
    }));
    if (links.length) {
        return links;
    }
    return [
        { key: "home", route: "/{lang}" },
        { key: "products", route: "/{lang}/products" },
        { key: "about", route: "/{lang}/about" },
        { key: "contact", route: "/{lang}/contact" },
        { key: "blog", route: "/{lang}/blog" },
    ];
});

const getIconForKey = (key, active = false) => {
    const icons = {
        home: active ? 'bi-house-fill' : 'bi-house',
        categories: active ? 'bi-grid-fill' : 'bi-grid',
        products: active ? 'bi-bag-fill' : 'bi-bag',
        offers: active ? 'bi-tag-fill' : 'bi-tag',
        about: active ? 'bi-info-circle-fill' : 'bi-info-circle',
        contact: active ? 'bi-envelope-fill' : 'bi-envelope',
        blog: 'bi-journal-text',
        wishlist: active ? 'bi-heart-fill' : 'bi-heart',
    };
    return icons[key?.toLowerCase()] || 'bi-three-dots';
};

const normalizeRoute = (routeTemplate = "") =>
    routeTemplate.replace("/{lang}/who", "/{lang}/about").replace("/{lang}/about-us", "/{lang}/about");

const resolveRoute = (routeTemplate = "") => normalizeRoute(routeTemplate).replace("{lang}", lang.value);

const isActive = (path) => {
    if (path === `/${lang.value}`) {
        return route.path === path;
    }
    return route.path === path || route.path.startsWith(`${path}/`);
};
const applyTheme = () => {
    document.documentElement.setAttribute("data-theme", theme.value);
    document.documentElement.setAttribute("data-bs-theme", theme.value);
};

const toggleTheme = () => {
    theme.value = theme.value === "dark" ? "light" : "dark";
    localStorage.setItem("theme", theme.value);
    applyTheme();
};

const submitSearch = async () => {
    searchOpen.value = false;
    mobileOpen.value = false;
    await router.push({
        path: `/${lang.value}/products`,
        query: searchText.value ? { search: searchText.value } : {},
    });
};

const goToCart = () => {
    router.push(`/${lang.value}/cart`);
};

const closeMenus = () => {
    dropdownOpen.value = false;
    mobileOpen.value = false;
};

const logout = () => {
    authService.logout({ redirect: false }).finally(() => {
        isLoggedIn.value = false;
        userName.value = "User";
        resetCartState();
        closeMenus();
        router.push(`/${lang.value}`);
    });
};

const fetchProfile = async () => {
    if (!authService.isLoggedIn()) {
        isLoggedIn.value = false;
        userName.value = "User";
        return;
    }

    try {
        const res = await api.get("/users/profile");
        if (res.data.success === true) {
            const profileUser = res.data.data.user || {};
            userName.value = profileUser.name || "User";
            isLoggedIn.value = true;
        }
    } catch {
        isLoggedIn.value = false;
        userName.value = "User";
    }
};

const toggleLanguage = async () => {
    const newLang = lang.value === "ar" ? "en" : "ar";

    localStorage.setItem("language", newLang);

    const currentPath = route.fullPath;

    const segments = currentPath.split("/");

    if (segments[1] === "ar" || segments[1] === "en") {
        segments[1] = newLang;
    } else {
        segments.splice(1, 0, newLang);
    }

    const newPath = segments.join("/");

    await router.push(newPath);

    window.location.reload();
};

const fetchCartCount = async () => {
    if (!authService.isLoggedIn()) {
        resetCartState();
        return;
    }

    try {
        const res = await CartService.getCart();
        syncCartState(res);
    } catch {
        resetCartState();
    }
};

const fetchLayout = async () => {
    try {
        const res = await LayoutService.getLayout();
        if (res.success === true) {
            layoutData.value = res.data;
        }
    } catch {
        // Keep fallback links and branding.
    }
};

watch(
    () => route.fullPath,
    () => {
        closeMenus();
    },
);

onMounted(async () => {
    theme.value = localStorage.getItem("theme") || "light";
    applyTheme();

    // Skip all API calls during install mode
    if (isInstallMode()) return;

    if (!initialLayout) {
        await fetchLayout();
    }

    if (authService.isLoggedIn()) {
        await Promise.all([fetchProfile(), fetchCartCount()]);
    }
});
</script>

<style scoped>
.store-nav {
    position: sticky;
    top: 0;
    z-index: 1040;
    border-bottom: 1px solid var(--border);
    background: color-mix(in srgb, var(--surface) 80%, transparent);
    backdrop-filter: blur(10px);
}

.store-nav__inner {
    width: min(1280px, 100% - clamp(1.25rem, 4vw, 4rem));
    margin-inline: auto;
    min-height: 72px;
    display: grid;
    grid-template-columns: auto 1fr auto auto;
    align-items: center;
    gap: 0.3rem;
}

.store-nav__brand {
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    color: var(--text);
    text-decoration: none;
    font-weight: 800;
    letter-spacing: -0.02em;
}

.store-nav__brand img {
    width: 38px;
    height: 38px;
    border-radius: 0.7rem;
    object-fit: cover;
    border: 1px solid var(--border);
}

.store-nav__links {
    align-items: center;
    gap: 0.2rem;
}

.store-nav__link {
    padding: 0.45rem 0.6rem;
    border-radius: 0.7rem;
    color: var(--text-secondary);
    font-size: 0.87rem;
    font-weight: 600;
    text-decoration: none;
    transition: 0.2s ease;
}

.store-nav__link:hover,
.store-nav__link.is-active {
    color: var(--text);
    background: var(--background);
}

.store-nav__search {
    max-width: 420px;
    display: grid;
    grid-template-columns: auto 1fr;
    align-items: center;
    gap: 0.5rem;
    min-height: 40px;
    border-radius: 0.8rem;
    border: 1px solid var(--border);
    background: var(--surface);
    padding-inline: 0.75rem;
    box-shadow: 0 4px 12px color-mix(in srgb, var(--primary) 8%, transparent);
}

.store-nav__search i {
    color: var(--text-secondary);
    font-size: 0.92rem;
}

.store-nav__search input {
    border: 0;
    background: transparent;
    color: var(--text);
    font-size: 0.9rem;
}

.store-nav__search input:focus {
    outline: none;
}

.store-nav__actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.15rem;
}

.icon-btn {
    position: relative;
    width: 40px;
    height: 40px;
    border: 1px solid var(--border);
    border-radius: 0.75rem;
    background: var(--surface);
    color: var(--text);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: 0.2s ease;
}

.icon-btn:hover {
    background: var(--background);
}

.count-badge {
    position: absolute;
    top: -6px;
    right: -6px;
    min-width: 18px;
    height: 18px;
    border-radius: 999px;
    background: var(--danger);
    color: var(--surface);
    font-size: 0.68rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 4px;
}

.store-nav__auth {
    position: relative;
    margin-inline-start: 0.05rem;
}

.user-chip,
.auth-btn {
    min-height: 40px;
    padding: 0.45rem 0.6rem;
    border-radius: 0.75rem;
    border: 1px solid var(--border);
    background: var(--surface);
    color: var(--text);
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    text-decoration: none;
    font-size: 0.86rem;
    font-weight: 600;
}

.auth-btn {
    background: var(--primary);
    border-color: var(--primary);
    color: var(--surface);
}

.user-menu {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    min-width: 180px;
    border: 1px solid var(--border);
    border-radius: 0.85rem;
    background: var(--surface);
    box-shadow: 0 4px 12px color-mix(in srgb, var(--primary) 8%, transparent);
    overflow: hidden;
}

.user-menu__item {
    width: 100%;
    border: 0;
    background: transparent;
    color: var(--text);
    text-align: start;
    padding: 0.7rem 0.85rem;
    text-decoration: none;
    display: block;
    font-size: 0.84rem;
}

.user-menu__item:hover {
    background: var(--background);
}

.user-menu__item--danger {
    color: var(--danger);
}

.store-nav__mobile-search,
.store-nav__mobile-links {
    width: min(1280px, 100% - clamp(1.25rem, 4vw, 4rem));
    margin-inline: auto;
    padding-bottom: 0.9rem;
}

.store-nav__mobile-links {
    display: grid;
    gap: 0.35rem;
}

@media (max-width: 991.98px) {
    .store-nav__inner {
        grid-template-columns: auto 1fr auto;
    }

    .store-nav__search {
        display: none;
    }

    .store-nav__links {
        display: none !important;
    }
}

@media (max-width: 767.98px) {
    .store-nav__inner {
        min-height: 64px;
        gap: 0.6rem;
    }

    .store-nav__brand span {
        display: none;
    }

    .store-nav__auth {
        display: none;
    }
}

.lang-btn {
    border: 1px solid var(--border);
    background: var(--surface);
    color: var(--text);
    padding: 0 10px;
    border-radius: 12px;
    font-weight: 700;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 40px;
}

.lang-btn:hover {
    transform: translateY(-1px);
    background: var(--background);
    border-color: var(--primary);
}

/* Bottom Navigation */
.bottom-nav {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: var(--surface);
    border-top: 1px solid var(--border);
    box-shadow: 0 -2px 10px color-mix(in srgb, var(--primary) 5%, transparent);
    display: flex;
    justify-content: center;
    gap: clamp(1.2rem, 5vw, 2rem);
    align-items: center;
    padding: 0.6rem 0;
    padding-bottom: calc(0.6rem + env(safe-area-inset-bottom, 0px));
    z-index: 1040;
}

.bottom-nav__item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.25rem;
    color: var(--text-secondary);
    text-decoration: none;
    font-size: 0.75rem;
    font-weight: 600;
    transition: 0.2s ease;
    border: none;
    background: transparent;
    padding: 0;
}

.bottom-nav__item i {
    font-size: 1.35rem;
    margin-bottom: 2px;
}

.bottom-nav__item.is-active,
.bottom-nav__item:hover {
    color: var(--primary);
}

.bottom-nav__more-menu {
    position: absolute;
    bottom: calc(100% + 0.5rem);
    right: 1rem;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 0.85rem;
    box-shadow: 0 -4px 12px color-mix(in srgb, var(--primary) 10%, transparent);
    padding: 0.5rem;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    min-width: 150px;
}

.bottom-nav__more-item {
    padding: 0.7rem 1rem;
    color: var(--text);
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 600;
    border-radius: 0.5rem;
    transition: 0.2s;
    text-align: center;
}

.bottom-nav__more-item.is-active,
.bottom-nav__more-item:hover {
    background: var(--background);
    color: var(--primary);
}

@media (max-width: 767.98px) {
    :global(body) {
        padding-bottom: calc(70px + env(safe-area-inset-bottom, 0px));
    }
}
</style>
