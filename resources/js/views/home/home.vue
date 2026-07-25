<template>
    <main ref="homeRef" class="premium-home">
        <section v-if="homeStore.error" class="home-alert">
            <div>
                <strong>We could not load the homepage content.</strong>
                <p>{{ homeStore.error }}</p>
            </div>
            <Button variant="soft" type="button" @click="reloadHome">Retry</Button>
        </section>

        <HomeHeroSlider
            :slides="heroSlides"
            :stats="stats"
            :loading="homeStore.loading"
            :lang="lang"
        />

        <div class="premium-home__stack">
            <HomeTrustBar :items="trustItems" />

            <HomeFeaturedCategories
                :categories="categories"
                :lang="lang"
                :loading="homeStore.loading"
            />

            <HomeFeaturedBrands
                :brands="brands"
                :lang="lang"
                :loading="homeStore.loading"
            />

            <HomeFlashSale :sale="flashSale" :loading="homeStore.loading" />

            <HomeFeaturedProducts
                :products="featuredProducts"
                :lang="lang"
                :loading="homeStore.loading"
                :adding-id="cartAddingId"
                @add-to-cart="handleAddToCart"
                @toggle-wishlist="handleToggleWishlist"
                @quick-view="openQuickView"
            />

            <HomePromotionalBanner :banner="promotionalBanner" :lang="lang" />

            <HomeBestSellers
                :products="bestSellers"
                :lang="lang"
                :loading="homeStore.loading"
                :adding-id="cartAddingId"
                @add-to-cart="handleAddToCart"
                @toggle-wishlist="handleToggleWishlist"
                @quick-view="openQuickView"
            />

            <HomeTrendingProducts
                :products="trendingProducts"
                :lang="lang"
                :loading="homeStore.loading"
                :adding-id="cartAddingId"
                @add-to-cart="handleAddToCart"
                @toggle-wishlist="handleToggleWishlist"
                @quick-view="openQuickView"
            />

            <HomeShopByBrand :brands="brands" :lang="lang" />

            <HomePromiseStrip :items="promiseItems" />

            <HomeCustomerTestimonials :testimonials="testimonials" />

            <HomeInstagramGallery :items="instagram" />

            <HomeReelsShowcase
                :videos="reels"
                :loading="loadingReels"
                @open-video="openReel"
                @like="toggleReelLike"
                @follow="followPage"
                @view-all="goToReels"
            />

            <HomeNewsletter :newsletter="newsletter" />
        </div>

        <Modal v-model="quickViewOpen">
            <div v-if="selectedProduct" class="quick-view">
                <div class="quick-view__media">
                    <img :src="imageUrl(selectedProduct.image, 1200)" :alt="selectedProduct.name" />
                </div>
                <div class="quick-view__body">
                    <Badge variant="gold">{{ selectedProduct.badge || selectedProduct.category }}</Badge>
                    <h3>{{ selectedProduct.name }}</h3>
                    <p>{{ selectedProduct.brand }} · {{ selectedProduct.tag }}</p>
                    <Rating :value="selectedProduct.rating" />
                    <Price :value="selectedProduct.price" :compare-at="selectedProduct.compareAtPrice" />
                    <div class="quick-view__actions">
                        <Button variant="primary" type="button" @click="handleAddToCart(selectedProduct)">
                            Add to cart
                        </Button>
                        <Button variant="soft" type="button" @click="handleToggleWishlist(selectedProduct)">
                            {{ wishlistStore.isWishlisted(selectedProduct.id) ? 'Remove wishlist' : 'Save wishlist' }}
                        </Button>
                    </div>
                </div>
            </div>
        </Modal>
    </main>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { useRoute } from "vue-router";
import toastr from "toastr";
import "toastr/build/toastr.min.css";
import { imageUrl } from "@/utils/image";
import { useSeoMeta } from "@/composables/useSeoMeta";
import { useHomeStore } from "@/stores/useHomeStore";
import { useProductsStore } from "@/stores/useProductsStore";
import { useCategoriesStore } from "@/stores/useCategoriesStore";
import { useSettingsStore } from "@/stores/useSettingsStore";
import { useWishlistStore } from "@/stores/useWishlistStore";
import { syncCartState } from "@/composables/useCartState";
import HomeService from "@/services/home/HomeService";
import CartService from "@/services/home/CartService";
import WishlistService from "@/services/home/WishlistService";
import Button from "@/components/ui/Button.vue";
import Badge from "@/components/ui/Badge.vue";
import Rating from "@/components/ui/Rating.vue";
import Price from "@/components/ui/Price.vue";
import Modal from "@/components/ui/Modal.vue";
import HomeHeroSlider from "@/components/home/HomeHeroSlider.vue";
import HomeTrustBar from "@/components/home/HomeTrustBar.vue";
import HomeFeaturedCategories from "@/components/home/HomeFeaturedCategories.vue";
import HomeFeaturedBrands from "@/components/home/HomeFeaturedBrands.vue";
import HomeFlashSale from "@/components/home/HomeFlashSale.vue";
import HomeFeaturedProducts from "@/components/home/HomeFeaturedProducts.vue";
import HomePromotionalBanner from "@/components/home/HomePromotionalBanner.vue";
import HomeBestSellers from "@/components/home/HomeBestSellers.vue";
import HomeTrendingProducts from "@/components/home/HomeTrendingProducts.vue";
import HomeShopByBrand from "@/components/home/HomeShopByBrand.vue";
import HomePromiseStrip from "@/components/home/HomePromiseStrip.vue";
import HomeCustomerTestimonials from "@/components/home/HomeCustomerTestimonials.vue";
import HomeInstagramGallery from "@/components/home/HomeInstagramGallery.vue";
import HomeReelsShowcase from "@/components/home/HomeReelsShowcase.vue";
import HomeNewsletter from "@/components/home/HomeNewsletter.vue";

const route = useRoute();
const homeRef = ref(null);
const quickViewOpen = ref(false);
const selectedProduct = ref(null);

const homeStore = useHomeStore();
const productsStore = useProductsStore();
const categoriesStore = useCategoriesStore();
const settingsStore = useSettingsStore();
const wishlistStore = useWishlistStore();
const latestProducts = ref([]);
const apiCategories = ref([]);
const apiBrands = ref([]);
const apiRandomThree = ref([]);
const apiRandomFour = ref([]);
const apiFeaturedProducts = ref([]);

const lang = computed(() => String(route.params.lang || localStorage.getItem("language") || "en"));

const heroSlides = computed(() => homeStore.heroSlides);
const trustItems = computed(() => homeStore.trustItems);
const categories = computed(() => apiCategories.value);
const brands = computed(() => apiBrands.value);
const flashSale = computed(() => {
    const sale = productsStore.flashSale;
    if (!sale) return null;
    return {
        ...sale,
        items: apiRandomThree.value.length > 0 ? apiRandomThree.value : sale.items,
    };
});
const featuredProducts = computed(() => latestProducts.value);
const bestSellers = computed(() => {
    return apiRandomFour.value.length > 0 ? apiRandomFour.value : productsStore.bestSellers;
});
const trendingProducts = computed(() => {
    return apiFeaturedProducts.value.length > 0 ? apiFeaturedProducts.value : productsStore.trending;
});
const promotionalBanner = computed(() => homeStore.promotionalBanner);
const promiseItems = computed(() => homeStore.promiseItems);
const testimonials = computed(() => homeStore.testimonials);
const instagram = computed(() => homeStore.instagram);
const reels = computed(() => homeStore.reels);
const newsletter = computed(() => homeStore.newsletter);
const stats = computed(() => homeStore.stats);
const loadingReels = computed(() => homeStore.loading);

const cartAddingId = ref(null);

const normalizeProduct = (product) => ({
    ...product,
    brand: product?.brand?.name || product?.brand || "",
    category: product?.category?.name || product?.category || "",
    rating: product?.rating ?? product?.average_rating ?? 0,
    compareAtPrice: product?.compareAtPrice ?? product?.compare_at_price ?? null,
});

const normalizeCategory = (category) => ({
    ...category,
    productsCount: category?.productsCount ?? category?.products_count ?? 0,
});

const normalizeBrand = (brand) => ({
    ...brand,
    productsCount: brand?.productsCount ?? brand?.products_count ?? 0,
});

const paginatedItems = (response) => (Array.isArray(response?.data?.data) ? response.data.data : []);
const responseItems = (response) => (Array.isArray(response?.data) ? response.data : []);

const loadApiContent = async () => {
    const [latestResponse, categoriesResponse, brandsResponse, randomThreeResponse, randomFourResponse, featuredResponse] = await Promise.all([
        HomeService.getLatestProducts(),
        HomeService.getCategories(),
        HomeService.getBrands(),
        HomeService.getRandomThree(),
        HomeService.getRandomFour(),
        HomeService.getFeaturedProducts(),
    ]);

    latestProducts.value = responseItems(latestResponse).map(normalizeProduct);
    apiCategories.value = paginatedItems(categoriesResponse).slice(0, 6).map(normalizeCategory);
    apiBrands.value = paginatedItems(brandsResponse).slice(0, 6).map(normalizeBrand);

    apiRandomThree.value = responseItems(randomThreeResponse).map(normalizeProduct);
    apiRandomFour.value = responseItems(randomFourResponse).map(normalizeProduct);
    apiFeaturedProducts.value = responseItems(featuredResponse).map(normalizeProduct);
};

useSeoMeta({
    title: () => `${settingsStore.siteLabel} | Premium Home`,
    description:
        "A premium ecommerce homepage with curated collections, flash sales, brand edits, and luxury-inspired presentation.",
    canonical: () => window.location.href,
});

const loadHome = async () => {
    try {
        await Promise.all([homeStore.loadHome(), loadApiContent()]);
    } catch {
        // Surface is already handled through the error banner.
    }
};

const reloadHome = () => {
    loadHome();
};

const handleAddToCart = async (product) => {
    if (!product) return;
    cartAddingId.value = product.id;
    try {
        const response = await CartService.add(product.id, { quantity: 1 });
        syncCartState(response);
        toastr.success(response?.message || `${product.name} added to cart.`);
    } catch (error) {
        toastr.error(error.response?.data?.message || "Unable to add item to cart.");
    } finally {
        cartAddingId.value = null;
    }
};

const handleToggleWishlist = async (product) => {
    if (!product) return;
    try {
        const response = await WishlistService.toggle(product.id);
        const isWishlisted = Boolean(response.data?.wishlisted);

        if (isWishlisted && !wishlistStore.isWishlisted(product.id)) {
            wishlistStore.toggleWishlist(product);
        }

        if (!isWishlisted && wishlistStore.isWishlisted(product.id)) {
            wishlistStore.toggleWishlist(product);
        }

        toastr.success(isWishlisted ? "Saved to wishlist." : "Removed from wishlist.");
    } catch (error) {
        toastr.error(error.response?.data?.message || "Unable to update wishlist.");
    }
};

const openQuickView = (product) => {
    selectedProduct.value = product;
    quickViewOpen.value = true;
};

const openReel = (video) => {
    if (!video) return;
};

const toggleReelLike = (video) => {
    if (!video) return;
    video.liked = !video.liked;
    video.likes = Math.max(0, Number(video.likes || 0) + (video.liked ? 1 : -1));
};

const followPage = () => {
    //
};

const goToReels = () => {
    //
};

watch(
    lang,
    () => {
        loadHome();
    },
    { immediate: false },
);

onMounted(() => {
    loadHome();
});
</script>

<style scoped>
.premium-home {
    display: grid;
    gap: 1.25rem;
    padding-bottom: 3rem;
}

.premium-home__stack {
    display: grid;
    gap: 1.25rem;
}

.home-alert {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.1rem;
    border-radius: 1.2rem;
    background: var(--premium-surface);
    border: 1px solid color-mix(in srgb, var(--danger) 18%, transparent);
    box-shadow: var(--premium-shadow-sm);
}

.home-alert p {
    margin: .35rem 0 0;
    color: var(--premium-muted);
}

.quick-view {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.quick-view__media {
    overflow: hidden;
    border-radius: 1.2rem;
    background: var(--premium-surface-soft);
}

.quick-view__media img {
    display: block;
    width: 100%;
    aspect-ratio: 4 / 5;
    object-fit: cover;
}

.quick-view__body {
    display: grid;
    align-content: start;
    gap: .9rem;
    padding: .25rem 0;
}

.quick-view__body h3 {
    margin: 0;
    font-size: clamp(1.4rem, 2vw, 2rem);
}

.quick-view__body p {
    margin: 0;
    color: var(--premium-muted);
}

.quick-view__actions {
    display: flex;
    gap: .75rem;
    flex-wrap: wrap;
    margin-top: .35rem;
}

@media (max-width: 820px) {
    .quick-view {
        grid-template-columns: 1fr;
    }
}
</style>
