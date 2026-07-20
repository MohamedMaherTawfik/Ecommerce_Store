<template>
    <article class="product-card" :class="{ 'is-compact': compact }">
        <div class="product-card__media">
            <RouterLink :to="productLink" class="product-card__image-link" :aria-label="product.name">
                <img
                    :src="imageUrl(product.image, 900)"
                    :srcset="imageSrcset(product.image, [480, 768, 900])"
                    :alt="product.name"
                    class="product-card__image product-card__image--primary"
                    loading="lazy"
                    decoding="async"
                />
                <img
                    v-if="product.hoverImage"
                    :src="imageUrl(product.hoverImage, 900)"
                    :alt="`${product.name} alternate view`"
                    class="product-card__image product-card__image--hover"
                    loading="lazy"
                    decoding="async"
                />
            </RouterLink>
            <div class="product-card__overlay">
                <span v-if="product.badge" class="product-card__badge">{{ product.badge }}</span>
                <div class="product-card__actions">
                    <button type="button" class="product-card__icon" @click="$emit('toggle-wishlist', product)" :aria-pressed="wishlisted">
                        <i class="bi" :class="wishlisted ? 'bi-heart-fill' : 'bi-heart'"></i>
                    </button>
                    <button type="button" class="product-card__icon" @click="$emit('quick-view', product)">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="product-card__body">
            <p class="product-card__meta">{{ product.brand || product.category }}</p>
            <RouterLink :to="productLink" class="product-card__title">{{ product.name }}</RouterLink>
            <Rating :value="product.rating" />
            <Price :value="product.price" :compare-at="product.compareAtPrice" />
            <button class="product-card__cart" type="button" :disabled="adding" @click="$emit('add-to-cart', product)">
                <i class="bi bi-bag-plus"></i>
                {{ adding ? 'Adding...' : 'Add to cart' }}
            </button>
        </div>
    </article>
</template>

<script setup>
import { computed } from "vue";
import { imageSrcset, imageUrl } from "@/utils/image";
import Price from "@/components/ui/Price.vue";
import Rating from "@/components/ui/Rating.vue";
import { useWishlistStore } from "@/stores/useWishlistStore";

const props = defineProps({
    product: { type: Object, required: true },
    lang: { type: String, default: "en" },
    compact: { type: Boolean, default: false },
    adding: { type: Boolean, default: false },
});

defineEmits(["add-to-cart", "toggle-wishlist", "quick-view"]);

const wishlistStore = useWishlistStore();

const productLink = computed(() => `/${props.lang}/products/${props.product.slug || props.product.id}`);
const wishlisted = computed(() => wishlistStore.isWishlisted(props.product.id));
</script>

<style scoped>
.product-card {
    overflow: hidden;
    border-radius: 1.35rem;
    background: var(--premium-surface);
    border: 1px solid var(--premium-border);
    box-shadow: var(--premium-shadow-sm);
    transition: transform .25s ease, box-shadow .25s ease;
}

.product-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--premium-shadow-lg);
}

.product-card__media {
    position: relative;
    aspect-ratio: 4 / 5;
    overflow: hidden;
    background: var(--premium-surface-soft);
}

.product-card__image-link {
    position: absolute;
    inset: 0;
    display: block;
}

.product-card__image {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: opacity .35s ease, transform .6s ease;
}

.product-card__image--hover {
    opacity: 0;
}

.product-card:hover .product-card__image--hover {
    opacity: 1;
}

.product-card:hover .product-card__image--primary {
    opacity: 0;
}

.product-card__overlay {
    position: absolute;
    inset: 0;
    display: flex;
    justify-content: space-between;
    align-items: start;
    padding: .9rem;
    pointer-events: none;
}

.product-card__badge {
    pointer-events: auto;
    display: inline-flex;
    align-items: center;
    padding: .35rem .65rem;
    border-radius: 999px;
    background: rgba(15, 23, 42, .82);
    color: #fff;
    font-size: .68rem;
    font-weight: 800;
    letter-spacing: .12em;
    text-transform: uppercase;
}

.product-card__actions {
    display: grid;
    gap: .45rem;
    pointer-events: auto;
}

.product-card__icon {
    width: 40px;
    height: 40px;
    border: 1px solid rgba(255, 255, 255, .18);
    border-radius: 999px;
    background: rgba(15, 23, 42, .55);
    color: #fff;
    backdrop-filter: blur(8px);
}

.product-card__body {
    padding: 1rem 1rem 1.1rem;
    display: grid;
    gap: .6rem;
}

.product-card__meta {
    margin: 0;
    color: var(--premium-muted);
    font-size: .74rem;
    font-weight: 700;
    letter-spacing: .1em;
    text-transform: uppercase;
}

.product-card__title {
    color: var(--premium-ink);
    text-decoration: none;
    font-size: 1rem;
    font-weight: 750;
    line-height: 1.3;
}

.product-card__cart {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .5rem;
    margin-top: .2rem;
    min-height: 44px;
    border: 1px solid var(--premium-border);
    border-radius: 999px;
    background: var(--premium-surface-soft);
    color: var(--premium-ink);
    font-weight: 700;
}

.product-card.is-compact .product-card__body {
    padding: .9rem;
}
</style>
