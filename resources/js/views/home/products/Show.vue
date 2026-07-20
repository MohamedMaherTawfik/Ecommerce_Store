<template>
    <main class="store-shell product-details-page">
        <div v-if="loading" class="details-skeleton"></div>

        <template v-else-if="product">
            <nav class="store-card product-breadcrumbs" aria-label="Breadcrumb">
                <RouterLink :to="`/${lang}`">Home</RouterLink>
                <span>/</span>
                <RouterLink :to="`/${lang}/products`">Products</RouterLink>
                <template v-if="product.category?.slug">
                    <span>/</span>
                    <RouterLink :to="`/${lang}/products/category/${product.category.slug}`">
                        {{ product.category.name }}
                    </RouterLink>
                </template>
                <span>/</span>
                <span aria-current="page">{{ product.name }}</span>
            </nav>

            <section class="details-layout">
                <div class="gallery store-card">
                    <img class="gallery-main" :src="imageUrl(activeImage)" :alt="product.name"
                        width="900" height="1200" fetchpriority="high" decoding="async" />
                    <div class="gallery-thumbs">
                        <button
                            v-for="image in gallery"
                            :key="image"
                            type="button"
                            :class="{ active: activeImage === image }"
                            @click="activeImage = image"
                        >
                            <img :src="imageUrl(image)" :alt="product.name" width="88" height="88"
                                loading="lazy" decoding="async" />
                        </button>
                    </div>
                </div>

                <article class="product-info store-card">
                    <span class="store-eyebrow">{{ product.brand?.name || product.category?.name || 'Fashion' }}</span>
                    <h1 class="store-title">{{ product.name }}</h1>
                    <div class="pricing-row">
                        <strong>{{ money(product.price) }}</strong>
                        <span>{{ Number(product.average_rating || 0).toFixed(1) }} / 5 · {{ product.reviews_count || 0 }} reviews</span>
                    </div>

                    <p class="description">{{ product.description }}</p>

                    <div class="option-grid">
                        <label v-if="product.sizes?.length">
                            Size
                            <select v-model="selected.size" class="store-select form-select">
                                <option v-for="size in product.sizes" :key="size.id" :value="size.size">{{ size.size }}</option>
                            </select>
                        </label>

                        <label v-if="product.colors?.length">
                            Color
                            <select v-model="selected.color" class="store-select form-select">
                                <option v-for="color in product.colors" :key="color.id" :value="color.color">{{ color.color }}</option>
                            </select>
                        </label>

                        <label>
                            Quantity
                            <input v-model.number="selected.quantity" class="store-input form-control" type="number" min="1" :max="product.stock || 1" />
                        </label>
                    </div>

                    <div class="detail-actions">
                        <button class="store-btn store-btn--primary" type="button" :disabled="product.stock <= 0 || busy" @click="addToCart">
                            {{ product.stock > 0 ? 'Add to cart' : 'Out of stock' }}
                        </button>
                        <button class="store-btn store-btn--soft" type="button" :disabled="busy" @click="toggleWishlist">
                            <i :class="product.is_wishlisted ? 'bi bi-heart-fill' : 'bi bi-heart'"></i>
                            Wishlist
                        </button>
                    </div>
                </article>
            </section>

            <section class="reviews-section store-card">
                <div class="section-heading">
                    <h2>Reviews</h2>
                    <span>{{ product.reviews_count || 0 }} total</span>
                </div>

                <form class="review-form" @submit.prevent="saveReview">
                    <select v-model="review.rating" class="store-select form-select" required>
                        <option v-for="rating in [5, 4, 3, 2, 1]" :key="rating" :value="rating">{{ rating }} stars</option>
                    </select>
                    <textarea v-model.trim="review.comment" class="store-textarea form-control" rows="3" maxlength="1000" placeholder="Share your experience"></textarea>
                    <button class="store-btn store-btn--primary" type="submit" :disabled="busy">Save review</button>
                </form>

                <div v-if="!product.reviews?.length" class="empty-reviews">No reviews yet.</div>

                <article v-for="item in product.reviews" :key="item.id" class="review-card">
                    <div>
                        <strong>{{ item.user?.name || 'Customer' }}</strong>
                        <span>{{ item.rating }} / 5</span>
                    </div>
                    <p>{{ item.comment || 'No comment provided.' }}</p>
                </article>
            </section>

            <section v-if="related.length" class="related-section store-card">
                <div class="section-heading">
                    <h2>Related products</h2>
                </div>

                <div class="related-grid">
                    <RouterLink v-for="item in related" :key="item.id" :to="`/${lang}/products/${item.slug || item.id}`" class="related-card">
                        <img :src="imageUrl(item.image)" :alt="item.name" width="400" height="533"
                            loading="lazy" decoding="async" />
                        <strong>{{ item.name }}</strong>
                        <span>{{ money(item.price) }}</span>
                    </RouterLink>
                </div>
            </section>
        </template>
    </main>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { useSeoMeta } from '@/composables/useSeoMeta';

const product = ref(null);
const route = useRoute();
const router = useRouter();
const lang = computed(() => route.params.lang || localStorage.getItem('language') || 'en');
const activeImage = ref(null);
const gallery = computed(() => {
    const extra = product.value?.images?.map((item) => item.image).filter(Boolean) || [];
    return [product.value?.image, ...extra].filter(Boolean);
});
const imageUrl = (path) => !path ? '/images/categorey.webp' : path.startsWith('http') || path.startsWith('/') ? path : `/storage/${path}`;

useSeoMeta({
    title: () => product.value?.meta_title || product.value?.name || 'Product Details',
    description: () => product.value?.meta_description || product.value?.description,
    keywords: () => product.value?.meta_keywords,
    image: () => imageUrl(product.value?.og_image || product.value?.image),
    ogTitle: () => product.value?.og_title,
    ogDescription: () => product.value?.og_description,
    canonical: () => product.value?.canonical_url || (
        product.value?.slug
            ? `${window.location.origin}/${lang.value}/products/${product.value.slug}`
            : window.location.href
    ),
    type: "product",
    schema: () => product.value ? [
        {
            "@context": "https://schema.org",
            "@type": "Product",
            name: product.value.name,
            description: product.value.meta_description || product.value.description,
            sku: product.value.sku,
            image: gallery.value.map(imageUrl),
            brand: product.value.brand?.name
                ? { "@type": "Brand", name: product.value.brand.name }
                : undefined,
            category: product.value.category?.name,
            aggregateRating: product.value.reviews_count > 0
                ? {
                    "@type": "AggregateRating",
                    ratingValue: product.value.average_rating,
                    reviewCount: product.value.reviews_count,
                }
                : undefined,
            offers: {
                "@type": "Offer",
                priceCurrency: "USD",
                price: product.value.price,
                availability: product.value.stock > 0
                    ? "https://schema.org/InStock"
                    : "https://schema.org/OutOfStock",
                url: `${window.location.origin}/${lang.value}/products/${product.value.slug || product.value.id}`,
            },
        },
        {
            "@context": "https://schema.org",
            "@type": "BreadcrumbList",
            itemListElement: [
                { "@type": "ListItem", position: 1, name: "Home", item: `${window.location.origin}/${lang.value}` },
                { "@type": "ListItem", position: 2, name: "Products", item: `${window.location.origin}/${lang.value}/products` },
                ...(product.value.category?.slug ? [{
                    "@type": "ListItem",
                    position: 3,
                    name: product.value.category.name,
                    item: `${window.location.origin}/${lang.value}/products/category/${product.value.category.slug}`,
                }] : []),
                {
                    "@type": "ListItem",
                    position: product.value.category?.slug ? 4 : 3,
                    name: product.value.name,
                    item: `${window.location.origin}/${lang.value}/products/${product.value.slug || product.value.id}`,
                },
            ],
        },
    ] : null,
});
import toastr from 'toastr';
import { syncCartState } from '@/composables/useCartState';
import ProductService from '@/services/home/ProductService';
import CartService from '@/services/home/CartService';
import WishlistService from '@/services/home/WishlistService';

const related = ref([]);
const loading = ref(false);
const busy = ref(false);

const selected = reactive({ quantity: 1, size: '', color: '' });
const review = reactive({ rating: 5, comment: '' });

const fetchProduct = async () => {
    loading.value = true;
    try {
        const [productResponse, relatedResponse] = await Promise.all([
            ProductService.getProduct(route.params.product),
            ProductService.getRelated(route.params.product),
        ]);

        product.value = productResponse.data;
        const relData = relatedResponse.data || {};
        related.value = Array.isArray(relData) ? relData : (relData.data || []);

        activeImage.value = product.value?.image || gallery.value[0];
        selected.size = product.value?.sizes?.[0]?.size || '';
        selected.color = product.value?.colors?.[0]?.color || '';

        if (product.value?.slug && route.params.product !== product.value.slug) {
            await router.replace(`/${lang.value}/products/${product.value.slug}`);
        }
    } catch (err) {
        console.error('[ProductDetails] fetch failed:', err);
        toastr.error('Unable to load product details.');
    } finally {
        loading.value = false;
    }
};

const addToCart = async () => {
    busy.value = true;
    try {
        const response = await CartService.add(product.value.id, {
            quantity: selected.quantity,
            size: selected.size || undefined,
            color: selected.color || undefined,
        });
        syncCartState(response);
        toastr.success(response?.message || 'Added to cart.');
    } catch (err) {
        toastr.error(err.response?.data?.message || 'Unable to add item to cart.');
    } finally {
        busy.value = false;
    }
};

const toggleWishlist = async () => {
    busy.value = true;
    try {
        const response = await WishlistService.toggle(product.value.id);
        product.value.is_wishlisted = response.data?.wishlisted;
    } finally {
        busy.value = false;
    }
};

const saveReview = async () => {
    busy.value = true;
    try {
        await ProductService.saveReview(product.value.id, review);
        toastr.success('Review saved.');
        review.comment = '';
        await fetchProduct();
    } catch (err) {
        toastr.error(err.response?.data?.message || 'Unable to save review.');
    } finally {
        busy.value = false;
    }
};

const money = (value) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(Number(value || 0));
watch(() => route.params.product, fetchProduct);
onMounted(fetchProduct);
</script>

<style scoped>
.product-details-page {
    display: grid;
    gap: 1rem;
}

.product-breadcrumbs {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    padding: 0.8rem 1rem;
    color: var(--sf-muted);
    font-size: 0.85rem;
}

.product-breadcrumbs a {
    color: var(--sf-text);
    text-decoration: none;
}

.details-layout {
    display: grid;
    grid-template-columns: 1fr 0.85fr;
    gap: 1rem;
    align-items: start;
}

.gallery {
    padding: 1rem;
}

.gallery-main {
    width: 100%;
    aspect-ratio: 1 / 1;
    object-fit: cover;
    border-radius: 0.85rem;
    background: var(--sf-surface-soft);
}

.gallery-thumbs {
    display: flex;
    gap: 0.65rem;
    margin-top: 0.8rem;
    overflow-x: auto;
}

.gallery-thumbs button {
    width: 84px;
    height: 84px;
    padding: 0;
    border: 2px solid transparent;
    border-radius: 0.75rem;
    overflow: hidden;
    background: var(--sf-surface);
}

.gallery-thumbs button.active {
    border-color: var(--sf-primary);
}

.gallery-thumbs img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-info {
    display: grid;
    gap: 0.95rem;
    padding: 1.2rem;
}

.pricing-row,
.detail-actions,
.section-heading,
.review-card div {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
}

.pricing-row strong {
    font-size: 1.4rem;
    color: var(--sf-text);
}

.pricing-row span,
.description {
    color: var(--sf-muted);
}

.option-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.65rem;
}

.option-grid label,
.review-form {
    display: grid;
    gap: 0.35rem;
    font-size: 0.82rem;
    font-weight: 700;
    color: var(--sf-muted);
}

.reviews-section,
.related-section {
    padding: 1rem;
}

.section-heading h2 {
    margin: 0;
    font-size: 1.2rem;
    color: var(--sf-text);
}

.section-heading span {
    color: var(--sf-muted);
    font-size: 0.85rem;
}

.review-form {
    grid-template-columns: 180px 1fr auto;
    margin: 0.9rem 0;
}

.review-card {
    padding: 0.95rem 0;
    border-top: 1px solid var(--sf-border);
}

.review-card p {
    margin: 0.45rem 0 0;
    color: var(--sf-muted);
}

.empty-reviews {
    padding: 1.3rem;
    text-align: center;
    color: var(--sf-muted);
}

.related-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.8rem;
    margin-top: 0.8rem;
}

.related-card {
    display: grid;
    gap: 0.45rem;
    color: var(--sf-text);
    text-decoration: none;
}

.related-card img {
    width: 100%;
    aspect-ratio: 1 / 1;
    object-fit: cover;
    border-radius: 0.7rem;
}

.related-card span {
    color: var(--sf-muted);
}

.details-skeleton {
    min-height: 500px;
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
    .details-layout,
    .related-grid {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 767.98px) {
    .details-layout,
    .option-grid,
    .review-form,
    .related-grid {
        grid-template-columns: 1fr;
    }

    .detail-actions {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>
