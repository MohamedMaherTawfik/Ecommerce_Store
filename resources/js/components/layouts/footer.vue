<template>
    <footer class="store-footer" :dir="lang === 'ar' ? 'rtl' : 'ltr'">
        <div class="store-footer__inner">
            <div class="store-footer__top">
                <section class="store-footer__brand">
                    <div class="brand-line">
                        <img :src="footerData.logo || '/images/ai_logo.webp'"
                            :alt="`${footerData.brand || 'EliteShop'} logo`" loading="lazy" decoding="async"
                            width="40" height="40" />
                        <strong>{{ footerData.brand || 'EliteShop' }}</strong>
                    </div>
                    <p>{{ footerData.description || 'Your destination for premium products and exceptional shopping experience.' }}</p>
                    <div class="socials">
                        <a
                            v-for="social in socials"
                            :key="social.name"
                            :href="social.href"
                            target="_blank"
                            rel="noopener"
                            :aria-label="social.name"
                        >
                            <i :class="`bi ${social.icon}`"></i>
                        </a>
                    </div>
                </section>

                <section>
                    <h3>{{ $t('footer.quick_links') }}</h3>
                    <ul>
                        <li v-for="link in quickLinks" :key="link.key">
                            <RouterLink :to="resolveRoute(link.route)">{{ linkLabel(link.key) }}</RouterLink>
                        </li>
                    </ul>
                </section>

                <section>
                    <h3>{{ $t('footer.support') }}</h3>
                    <ul>
                        <li v-for="link in supportLinks" :key="link.key">
                            <RouterLink :to="resolveRoute(link.route)">{{ linkLabel(link.key) }}</RouterLink>
                        </li>
                    </ul>
                </section>

                <section>
                    <h3>Newsletter</h3>
                    <p>Subscribe to get special offers and updates.</p>
                    <form class="newsletter" @submit.prevent="subscribed = true">
                        <input v-model.trim="email" type="email" required placeholder="name@email.com" />
                        <button type="submit" aria-label="subscribe">
                            <i class="bi bi-send"></i>
                        </button>
                    </form>
                    <small v-if="subscribed" class="newsletter-success">Subscribed successfully.</small>

                    <div class="stores" v-if="stores.length">
                        <a v-for="store in stores" :key="store.name" :href="store.href" target="_blank" rel="noopener">
                            <img :src="store.image" :alt="store.alt || `${store.name} app download`"
                                width="135" height="40" loading="lazy" decoding="async" />
                        </a>
                    </div>
                </section>
            </div>

            <div class="store-footer__bottom">
                <p>{{ footerData.copyright || `© ${new Date().getFullYear()} EliteShop` }}</p>
                <div>
                    <a href="#">{{ $t('footer.privacy') }}</a>
                    <a href="#">{{ $t('footer.terms') }}</a>
                    <a href="#">Cookie Policy</a>
                </div>
            </div>
        </div>
    </footer>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { isInstallMode } from '@/services/ApiClient';
import LayoutService from '@/services/home/LayoutService';

const route = useRoute();
const { t } = useI18n();

const initialLayout = LayoutService.getInitialLayoutData();
const footerData = ref(initialLayout?.footer || {});
const subscribed = ref(false);
const email = ref('');

const lang = computed(() => route.params.lang || localStorage.getItem('language') || 'en');

const normalizeRoute = (routeTemplate = '') =>
    routeTemplate.replace('/{lang}/who', '/{lang}/about').replace('/{lang}/about-us', '/{lang}/about');

const resolveRoute = (routeTemplate = '') => normalizeRoute(routeTemplate).replace('{lang}', lang.value);

const linkLabel = (key) => {
    const map = {
        home: 'footer.home',
        products: 'footer.products',
        wishlist: 'nav.wishlist',
        about: 'footer.about',
        contact: 'footer.contact',
        profile: 'nav.profile',
        terms: 'footer.terms',
        privacy: 'footer.privacy',
        help: 'footer.help',
    };

    const translationKey = map[key];
    if (translationKey) {
        return t(translationKey);
    }

    return key;
};

const quickLinks = computed(() => {
    const links = footerData.value.quickLinks || [];
    if (links.length) {
        return links;
    }
    return [
        { key: 'home', route: '/{lang}' },
        { key: 'products', route: '/{lang}/products' },
        { key: 'about', route: '/{lang}/about' },
        { key: 'wishlist', route: '/{lang}/wishlist' },
    ];
});

const supportLinks = computed(() => {
    const links = footerData.value.supportLinks || [];
    if (links.length) {
        return links;
    }
    return [
        { key: 'contact', route: '/{lang}/contact' },
        { key: 'profile', route: '/{lang}/profile' },
        { key: 'orders', route: '/{lang}/orders' },
    ];
});

const socials = computed(() => {
    const links = footerData.value.socials || [];
    if (links.length) {
        return links;
    }
    return [
        { name: 'facebook', href: '#', icon: 'bi-facebook' },
        { name: 'twitter', href: '#', icon: 'bi-twitter' },
        { name: 'instagram', href: '#', icon: 'bi-instagram' },
        { name: 'youtube', href: '#', icon: 'bi-youtube' },
    ];
});

const stores = computed(() => footerData.value.stores || []);

const fetchLayout = async () => {
    try {
        const res = await LayoutService.getLayout();
        if (res.success === true) {
            footerData.value = res.data.footer || {};
        }
    } catch {
        // keep fallback content
    }
};

onMounted(() => {
    // Skip API calls during install mode
    if (!isInstallMode() && !initialLayout) {
        fetchLayout();
    }
});
</script>

<style scoped>
.store-footer {
    margin-top: auto;
    border-top: 1px solid var(--sf-border);
    background: color-mix(in srgb, var(--sf-surface) 95%, transparent);
}

.store-footer__inner {
    width: min(1280px, 100% - clamp(1.25rem, 4vw, 4rem));
    margin-inline: auto;
    padding-block: clamp(2rem, 4vw, 3rem) 1.2rem;
}

.store-footer__top {
    display: grid;
    grid-template-columns: 1.2fr repeat(3, 1fr);
    gap: 1.4rem;
}

.store-footer h3 {
    margin: 0 0 0.85rem;
    font-size: 0.95rem;
    font-weight: 800;
    color: var(--sf-text);
}

.store-footer p,
.store-footer li,
.store-footer small,
.store-footer a {
    color: var(--sf-muted);
    font-size: 0.86rem;
}

.store-footer ul {
    margin: 0;
    padding: 0;
    list-style: none;
    display: grid;
    gap: 0.55rem;
}

.store-footer a {
    text-decoration: none;
    transition: 0.2s ease;
}

.store-footer a:hover {
    color: var(--sf-text);
}

.brand-line {
    display: inline-flex;
    align-items: center;
    gap: 0.58rem;
    margin-bottom: 0.75rem;
}

.brand-line img {
    width: 40px;
    height: 40px;
    border-radius: 0.75rem;
    border: 1px solid var(--sf-border);
    object-fit: cover;
}

.brand-line strong {
    font-size: 1.1rem;
    color: var(--sf-text);
}

.socials {
    display: flex;
    gap: 0.5rem;
    margin-top: 0.9rem;
}

.socials a {
    width: 36px;
    height: 36px;
    border-radius: 0.7rem;
    border: 1px solid var(--sf-border);
    background: var(--sf-surface-soft);
    color: var(--sf-text);
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.newsletter {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 0.5rem;
    margin-top: 0.8rem;
}

.newsletter input {
    min-height: 42px;
    border: 1px solid var(--sf-border);
    border-radius: 0.7rem;
    background: var(--sf-surface-soft);
    color: var(--sf-text);
    padding-inline: 0.7rem;
}

.newsletter input:focus {
    outline: none;
    border-color: var(--sf-primary);
}

.newsletter button {
    width: 42px;
    border: 0;
    border-radius: 0.7rem;
    background: var(--sf-primary);
    color: #fff;
}

.newsletter-success {
    display: inline-block;
    margin-top: 0.6rem;
    color: var(--sf-success);
}

.stores {
    margin-top: 0.75rem;
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.stores img {
    width: auto;
    height: 36px;
    border-radius: 0.5rem;
    border: 1px solid var(--sf-border);
    background: #fff;
}

.store-footer__bottom {
    margin-top: 1.5rem;
    padding-top: 1rem;
    border-top: 1px solid var(--sf-border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
}

.store-footer__bottom p {
    margin: 0;
}

.store-footer__bottom div {
    display: flex;
    gap: 1rem;
}

@media (max-width: 991.98px) {
    .store-footer__top {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 575.98px) {
    .store-footer__top {
        grid-template-columns: 1fr;
    }

    .store-footer__bottom {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>
