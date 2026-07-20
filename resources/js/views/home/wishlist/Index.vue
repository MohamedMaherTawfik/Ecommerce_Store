<template>
    <main class="store-shell wishlist-page">
        <header class="wishlist-header store-card">
            <div>
                <span class="store-eyebrow">Saved</span>
                <h1 class="store-title">Wishlist</h1>
                <p class="store-subtitle">Items you saved for later shopping.</p>
            </div>
        </header>

        <div v-if="loading" class="wishlist-grid">
            <div v-for="n in 4" :key="n" class="wishlist-skeleton"></div>
        </div>

        <section v-else-if="products.length" class="wishlist-grid">
            <article v-for="product in products" :key="product.id" class="wishlist-card store-card">
                <RouterLink :to="`/${lang}/products/${product.slug || product.id}`" class="wishlist-card__image">
                    <img :src="imageUrl(product.image)" :alt="product.name" width="600" height="800"
                        loading="lazy" decoding="async" />
                </RouterLink>

                <div class="wishlist-card__body">
                    <h2>{{ product.name }}</h2>
                    <p>{{ product.brand?.name || product.category?.name || 'Fashion' }}</p>
                    <strong>{{ money(product.price) }}</strong>

                    <div class="wishlist-card__actions">
                        <button class="store-btn store-btn--primary" :disabled="busyId === product.id" @click="addToCart(product)">
                            Add to cart
                        </button>
                        <button class="store-btn store-btn--soft" :disabled="busyId === product.id" @click="remove(product.id)">
                            Remove
                        </button>
                    </div>
                </div>
            </article>
        </section>

        <section v-else class="empty-state store-card">
            <i class="bi bi-heart"></i>
            <h2>Your wishlist is empty</h2>
            <RouterLink :to="`/${lang}/products`" class="store-btn store-btn--primary">Browse products</RouterLink>
        </section>
    </main>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { useSeoMeta } from '@/composables/useSeoMeta';

useSeoMeta({
    title: 'Wishlist',
    description: 'Your saved items on EliteShop.',
    robots: 'noindex,nofollow'
});
import toastr from 'toastr';
import { syncCartState } from '@/composables/useCartState';
import WishlistService from '@/services/home/WishlistService';
import CartService from '@/services/home/CartService';

const route = useRoute();
const lang = route.params.lang || localStorage.getItem('language') || 'en';

const products = ref([]);
const loading = ref(false);
const busyId = ref(null);

const fetchWishlist = async () => {
    loading.value = true;
    try {
        const response = await WishlistService.getWishlist();
        const rawData = response.data || {};
        products.value = Array.isArray(rawData) ? rawData : (rawData.data || []);
    } catch (err) {
        console.error('[Wishlist] fetch failed:', err);
    } finally {
        loading.value = false;
    }
};

const remove = async (id) => {
    busyId.value = id;
    try {
        await WishlistService.remove(id);
        products.value = products.value.filter((product) => product.id !== id);
        toastr.success('Removed from wishlist.');
    } finally {
        busyId.value = null;
    }
};

const addToCart = async (product) => {
    busyId.value = product.id;
    try {
        const response = await CartService.add(product.id, { quantity: 1 });
        syncCartState(response);
        toastr.success(response?.message || 'Added to cart.');
    } catch (err) {
        toastr.error(err.response?.data?.message || 'Unable to add item to cart.');
    } finally {
        busyId.value = null;
    }
};

const money = (value) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(Number(value || 0));
const imageUrl = (path) => !path ? '/images/categorey.webp' : path.startsWith('http') ? path : `/storage/${path}`;

onMounted(fetchWishlist);
</script>

<style scoped>
.wishlist-page {
    display: grid;
    gap: 1rem;
}

.wishlist-header {
    padding: 1.2rem;
}

.wishlist-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 1rem;
}

.wishlist-card {
    overflow: hidden;
}

.wishlist-card__image {
    display: block;
    aspect-ratio: 1 / 1.05;
    background: var(--sf-surface-soft);
}

.wishlist-card__image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.wishlist-card__body {
    display: grid;
    gap: 0.55rem;
    padding: 0.9rem;
}

.wishlist-card__body h2 {
    margin: 0;
    font-size: 0.98rem;
    font-weight: 800;
    color: var(--sf-text);
}

.wishlist-card__body p {
    margin: 0;
    color: var(--sf-muted);
    font-size: 0.82rem;
}

.wishlist-card__body strong {
    color: var(--sf-text);
}

.wishlist-card__actions {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.5rem;
}

.empty-state {
    min-height: 300px;
    display: grid;
    place-items: center;
    text-align: center;
    gap: 0.65rem;
    padding: 1rem;
}

.empty-state i {
    font-size: 1.7rem;
    color: var(--sf-muted);
}

.empty-state h2 {
    margin: 0;
    color: var(--sf-text);
}

.wishlist-skeleton {
    min-height: 320px;
    border-radius: 1rem;
    background: linear-gradient(90deg, var(--sf-surface-soft), color-mix(in srgb, var(--sf-surface-soft) 75%, var(--sf-border)), var(--sf-surface-soft));
    background-size: 300% 100%;
    animation: pulse 1.2s linear infinite;
}

@keyframes pulse {
    from {
        background-position: 100% 0;
    }

    to {
        background-position: -100% 0;
    }
}

@media (max-width: 991.98px) {
    .wishlist-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 575.98px) {
    .wishlist-grid {
        grid-template-columns: 1fr;
    }
}
</style>
