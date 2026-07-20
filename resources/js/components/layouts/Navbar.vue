<template>
    <header class="store-nav" :dir="lang === 'ar' ? 'rtl' : 'ltr'">
        <div class="store-nav__inner">
            <RouterLink :to="`/${lang}`" class="store-nav__brand">
                <img :src="layoutData.navbar?.logo || 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAARMAAAC3CAMAAAAGjUrGAAAAMFBMVEXx8/XCy9K/yND09vfw8vTP1tzp7O/i5ure4+fO1dvJ0dfT2d/EzNPt7/Lb4OXo6+4FeM7UAAAFL0lEQVR4nO2c24KrIAxFLdha7///t0dxOlWDSiAKztnrbR4G6SoJBKHZA6zJYncgQeCEAicUOKHACQVOKHBCgRMKnFDghAInFDihwAkFTihwQoETCpxQ4IQCJxQ4ocAJBU4ocEKBEwqcUOCEAicUOKHACQVOKHBCgRMKnFDghAInFDihwAkFTihwQoETCpxQ4IQCJxQ4ocAJBU4ot3Oi1KMq64FnWTVq+EueWzlRquqKVn/J+/ezEfdyHydKPYtc62yF1m1Xymq5ixPVdDnx8eslf1eCVu7hRFXFppAfLW39kNJyByeqOTJirGTvRsbKDZyozsHIpKUQsZK8E1Vu55GTrKTuRL0ZRoyVLviZaTtRVctUMuaVOnCoJO1E1WwjxsorbGZO2Qk7br5WuhApKTvpfZWMy5WAoZKuk6b1NhI4VJJ10uRBSsas0ng+OlUnVaARw9NvqCTqRERJpt9eUtJ0IqPEN36SdNIIKRnIPeafFJ0Ep9c5mr+qTdFJ2CRMpLAn5fScqJeokrFWZkoRdaImwtpw2T9iSnnxuiDoRFXda6hK28JzWTA14ryBxKFlTT9iTlT1W57o3Lta96yED8krRieknCw/DDuEP1TnKBlgzMlCTtZDXr+8pIjOwitK5x7JOKFD3mukiE85ix45S5FxYll46prdiv8ekpsU19wv4kS9LV1ouQPlrPzKliIzTuw9YDYiVfgFSxFx8rR+wcyMomSX9HYpTjlFwonqrB3gBc/JyYQjRcRJYe8Ay4l9rMlLcVi8iTjp7Y/nOBHcMjngWEoi4+TUlcmKw9rnxHzCWMqeU/ltkB9JEZl3SusnYmwQn1fm2GgPeiOzZrM9WZfu/3/BNDznYATLOLENffep+JppeMZBMSZUF9N6ljFM7KF3qpTduBZyQj4W53XTiRsEm1L2dr2k9k9W9Rtjq2BrJj9Zyk7pI7bP9lw8kfH+4KIFLGF77Sa3R90Un0POvHNCcYzsLVMk9+2buni1bd9xjMSJHMPmjCz7zov/fidW5GQ7OS/2e8BoRrLtrBfXScTIMVLsk09cJxEjZ8I6+cR1EmG1tsRaDsZ0EjlyDL0leuxOpulD4JTALtfXORRbnqVO1LDOePdtpoclWPsqulL+wt0P0SNnxFKrrp2opmuXl+5OuHA3PSmByDGQ9ezSydYdM+ELd4YUIsdANnoWTva2RSUv3JlnJRE5I2RbY+6kee1+dTrrhC7cPTZeMUdivZnydaIc3tdqqWuI6USOYZlSfp0oxzVlJxNByUSOYZlSPk6cDzqEXy17JDTn/LBMKRlTSRZ4X2giep2zZnEwZHLiGjifFt6BTtKKHMMspUxO2BkvDzoDm1jkGGa7bsaJx0t9XfgrOfuMlhezwsc48RrKufvhyiXXHatg8T2Zkm0eHzluxO8W4pXHKljkXycBt3h9blFdeqyCx2fPOguLbn6qTWsBu+Czxs/CopsdP4kmkx+mcZ8FRrfuWUqSTSYT005keDucW4iXnzRhMg17iYacC6A0VyZzzIQs0pBrUrn22JoXY4Us0pDjaZMzb+dIMX6/Qi0dHSU0XHySz48heqSaOs60vsvlq2mtpzj9OCh/Trgjew7afgLar63d6ec2SmTZm37+UyV7048K+Gmkm7O10A/8aaSbY7sEr8rYvYoNnX4Sr3EuYJVpVc35Ccu/innZbryMJ1n4v9f4N9FZ39XPZ931GYzMGH9VPHYfAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAADp8Q9+nG9anuOrfAAAAABJRU5ErkJggg=='"
                    :alt="`${layoutData.navbar?.brand || 'EliteShop'} logo`" width="38" height="38"
                    decoding="async" fetchpriority="high" />
                <span>{{ layoutData.navbar?.brand || 'EliteShop' }}</span>
            </RouterLink>

            <nav class="store-nav__links d-none d-lg-flex">
                <RouterLink v-for="link in navLinks" :key="link.key" :to="resolveRoute(link.route)"
                    class="store-nav__link" :class="{ 'is-active': isActive(resolveRoute(link.route)) }">
                    {{ t(`nav.${link.key}`) }}
                </RouterLink>
            </nav>

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
                <button class="icon-btn" type="button" @click="toggleTheme" :title="t('nav.theme')">
                    <i class="bi" :class="theme === 'dark' ? 'bi-sun' : 'bi-moon'"></i>
                </button>

                <RouterLink v-if="isLoggedIn" class="icon-btn" :to="`/${lang}/wishlist`" :title="t('nav.wishlist')">
                    <i class="bi bi-heart"></i>
                </RouterLink>

                <button v-if="isLoggedIn" class="icon-btn icon-btn--count" type="button" @click="goToCart"
                    :title="t('nav.cart')">
                    <i class="bi bi-bag"></i>
                    <span v-if="cartCount" class="count-badge">{{ cartCount }}</span>
                </button>

                <button class="icon-btn d-lg-none" type="button" @click="mobileOpen = !mobileOpen" aria-label="menu">
                    <i class="bi" :class="mobileOpen ? 'bi-x-lg' : 'bi-list'"></i>
                </button>

                <div class="store-nav__auth d-none d-sm-flex">
                    <template v-if="isLoggedIn">
                        <button class="user-chip" type="button" @click="dropdownOpen = !dropdownOpen">
                            <i class="bi bi-person-circle"></i>
                            <span>{{ userName }}</span>
                            <i class="bi" :class="dropdownOpen ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
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
                    <RouterLink v-else :to="`/${lang}/auth`" class="auth-btn">
                        {{ t('nav.login') }}
                    </RouterLink>
                </div>
            </div>
        </div>

        <div v-if="searchOpen" class="store-nav__mobile-search d-md-none">
            <form @submit.prevent="submitSearch" class="store-nav__search">
                <i class="bi bi-search"></i>
                <input v-model.trim="searchText" type="text" :placeholder="t('nav.search')" />
            </form>
        </div>

        <div v-if="mobileOpen" class="store-nav__mobile-links d-lg-none">
            <RouterLink v-for="link in navLinks" :key="link.key" :to="resolveRoute(link.route)" class="store-nav__link"
                :class="{ 'is-active': isActive(resolveRoute(link.route)) }" @click="mobileOpen = false">
                {{ t(`nav.${link.key}`) }}
            </RouterLink>
            <RouterLink v-if="!isLoggedIn" :to="`/${lang}/auth`" class="auth-btn" @click="mobileOpen = false">
                {{ t('nav.login') }}
            </RouterLink>
        </div>
    </header>
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
    border-bottom: 1px solid var(--sf-border);
    background: var(--nav-bg);
    backdrop-filter: blur(14px);
}

.store-nav__inner {
    width: min(1280px, 100% - clamp(1.25rem, 4vw, 4rem));
    margin-inline: auto;
    min-height: 72px;
    display: grid;
    grid-template-columns: auto 1fr auto auto;
    align-items: center;
    gap: 1rem;
}

.store-nav__brand {
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    color: var(--sf-text);
    text-decoration: none;
    font-weight: 800;
    letter-spacing: -0.02em;
}

.store-nav__brand img {
    width: 38px;
    height: 38px;
    border-radius: 0.7rem;
    object-fit: cover;
    border: 1px solid var(--sf-border);
}

.store-nav__links {
    align-items: center;
    gap: 0.35rem;
}

.store-nav__link {
    padding: 0.52rem 0.78rem;
    border-radius: 0.7rem;
    color: var(--sf-muted);
    font-size: 0.87rem;
    font-weight: 600;
    text-decoration: none;
    transition: 0.2s ease;
}

.store-nav__link:hover,
.store-nav__link.is-active {
    color: var(--sf-text);
    background: var(--sf-surface-soft);
}

.store-nav__search {
    max-width: 420px;
    display: grid;
    grid-template-columns: auto 1fr;
    align-items: center;
    gap: 0.6rem;
    min-height: 44px;
    border-radius: 0.8rem;
    border: 1px solid var(--sf-border);
    background: var(--sf-surface);
    padding-inline: 0.75rem;
    box-shadow: var(--sf-shadow-sm);
}

.store-nav__search i {
    color: var(--sf-muted);
    font-size: 0.92rem;
}

.store-nav__search input {
    border: 0;
    background: transparent;
    color: var(--sf-text);
    font-size: 0.9rem;
}

.store-nav__search input:focus {
    outline: none;
}

.store-nav__actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.4rem;
}

.icon-btn {
    position: relative;
    width: 40px;
    height: 40px;
    border: 1px solid var(--sf-border);
    border-radius: 0.75rem;
    background: var(--sf-surface);
    color: var(--sf-text);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: 0.2s ease;
}

.icon-btn:hover {
    background: var(--sf-surface-soft);
}

.count-badge {
    position: absolute;
    top: -6px;
    right: -6px;
    min-width: 18px;
    height: 18px;
    border-radius: 999px;
    background: #dc2626;
    color: #fff;
    font-size: 0.68rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 4px;
}

.store-nav__auth {
    position: relative;
    margin-inline-start: 0.35rem;
}

.user-chip,
.auth-btn {
    min-height: 40px;
    padding: 0.45rem 0.8rem;
    border-radius: 0.75rem;
    border: 1px solid var(--sf-border);
    background: var(--sf-surface);
    color: var(--sf-text);
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
    font-size: 0.86rem;
    font-weight: 600;
}

.auth-btn {
    background: var(--sf-primary);
    border-color: var(--sf-primary);
    color: #fff;
}

.user-menu {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    min-width: 180px;
    border: 1px solid var(--sf-border);
    border-radius: 0.85rem;
    background: var(--sf-surface);
    box-shadow: var(--sf-shadow-lg);
    overflow: hidden;
}

.user-menu__item {
    width: 100%;
    border: 0;
    background: transparent;
    color: var(--sf-text);
    text-align: start;
    padding: 0.7rem 0.85rem;
    text-decoration: none;
    display: block;
    font-size: 0.84rem;
}

.user-menu__item:hover {
    background: var(--sf-surface-soft);
}

.user-menu__item--danger {
    color: var(--sf-danger);
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
    border: 1px solid #9ca3af;
    background: rgb(255, 255, 255);
    color: var(--text-color);
    padding: 8px 14px;
    border-radius: 12px;
    font-weight: 700;
    transition: all 0.2s ease;
}

.lang-btn:hover {
    transform: translateY(-1px);
    background: rgba(255, 255, 255, 0.15);
    border-color: #d1d5db;
}
</style>
