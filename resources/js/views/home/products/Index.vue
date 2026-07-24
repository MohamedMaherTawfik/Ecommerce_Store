<template>
    <main class="store-shell products-page">
        <header class="products-header store-card">
            <div>
                <span class="store-eyebrow">Catalog</span>
                <h1 class="store-title">Products</h1>
                <p class="store-subtitle">Filter the catalog by category, brand, price, and rating.</p>
            </div>
        </header>

        <section class="products-layout">
            <aside class="filters store-card">
                <label>
                    Search
                    <input v-model.trim="filters.search" class="store-input form-control" placeholder="Search products"
                        @keyup.enter="fetchProducts(1, true)" />
                </label>

                <label>
                    Category
                    <select v-model="filters.category_id" class="store-select form-select" @change="changeCategory">
                        <option value="">All categories</option>
                        <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name
                            }}</option>
                    </select>
                </label>

                <label>
                    Brand
                    <select v-model="filters.brand_id" class="store-select form-select"
                        @change="fetchProducts(1, true)">
                        <option value="">All brands</option>
                        <option v-for="brand in brands" :key="brand.id" :value="brand.id">{{ brand.name }}</option>
                    </select>
                </label>

                <div class="range-row">
                    <label>
                        Min
                        <input v-model="filters.min_price" class="store-input form-control" type="number" min="0" />
                    </label>
                    <label>
                        Max
                        <input v-model="filters.max_price" class="store-input form-control" type="number" min="0" />
                    </label>
                </div>

                <label>
                    Sort by
                    <select v-model="filters.sort" class="store-select form-select" @change="fetchProducts(1, true)">
                        <option value="newest">Newest</option>
                        <option value="price_asc">Price: low to high</option>
                        <option value="price_desc">Price: high to low</option>
                        <option value="rating">Top rated</option>
                    </select>
                </label>

                <div class="filters-actions">
                    <button class="store-btn store-btn--primary" type="button"
                        @click="fetchProducts(1, true)">Apply</button>
                    <button class="store-btn store-btn--soft" type="button" @click="clearFilters">Clear</button>
                </div>
            </aside>

            <section class="results">
                <div v-if="loading" class="products-grid">
                    <div v-for="item in 8" :key="item" class="product-skeleton"></div>
                </div>

                <div v-else-if="products.length === 0" class="empty-state store-card">
                    <i class="bi bi-search"></i>
                    <h2>No products found</h2>
                    <p>Try a different category, brand, or price range.</p>
                </div>

                <div v-else class="products-grid">
                    <article v-for="product in products" :key="product.id" class="product-card store-card">
                        <RouterLink :to="`/${lang}/products/${product.slug || product.id}`" class="product-card__image">
                            <img :src="imageUrl(product.image)" :alt="product.name" width="600" height="800"
                                loading="lazy" decoding="async" />
                        </RouterLink>

                        <div class="product-card__body">
                            <div class="product-card__top">
                                <span>{{ product.brand?.name || product.category?.name || 'Fashion' }}</span>
                                <button type="button" @click="toggleWishlist(product)"
                                    :disabled="busyId === product.id">
                                    <i :class="product.is_wishlisted ? 'bi bi-heart-fill' : 'bi bi-heart'"></i>
                                </button>
                            </div>

                            <h2>{{ product.name }}</h2>
                            <p>{{ Number(product.average_rating || 0).toFixed(1) }} / 5 · {{ product.reviews_count || 0
                                }} reviews</p>

                            <div class="product-card__bottom">
                                <strong>{{ money(product.price) }}</strong>
                                <button type="button" :disabled="product.stock <= 0 || busyId === product.id"
                                    @click="addToCart(product)">
                                    {{ product.stock > 0 ? 'Add' : 'Sold out' }}
                                </button>
                            </div>
                        </div>
                    </article>
                </div>

                <nav v-if="pagination.last_page > 1" class="results-pagination store-card"
                    aria-label="Product pagination">
                    <button class="store-btn store-btn--soft" :disabled="pagination.current_page <= 1 || loading"
                        @click="fetchProducts(pagination.current_page - 1, true)">
                        Previous
                    </button>
                    <span>Page {{ pagination.current_page }} of {{ pagination.last_page }}</span>
                    <button class="store-btn store-btn--soft"
                        :disabled="pagination.current_page >= pagination.last_page || loading"
                        @click="fetchProducts(pagination.current_page + 1, true)">
                        Next
                    </button>
                </nav>
            </section>
        </section>
    </main>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { useSeoMeta } from '@/composables/useSeoMeta';
import toastr from 'toastr';
import { syncCartState } from '@/composables/useCartState';
import ProductService from '@/services/home/ProductService';
import CartService from '@/services/home/CartService';
import WishlistService from '@/services/home/WishlistService';
import CategoryService from '@/services/home/categorey/CategoryService';
import BrandService from '@/services/home/brand/BrandService';

const route = useRoute();
const router = useRouter();
const lang = route.params.lang || localStorage.getItem('language') || 'en';

const products = ref([]);
const categories = ref([]);
const brands = ref([]);
const loading = ref(false);
const busyId = ref(null);
const syncGuard = ref(false);

const filters = reactive({
    search: '',
    category_id: '',
    category_slug: '',
    brand_id: '',
    min_price: '',
    max_price: '',
    sort: 'newest',
});

const pagination = reactive({ current_page: 1, last_page: 1, total: 0 });
const imageUrl = (path) => !path ? '/images/categorey.webp' : path.startsWith('http') || path.startsWith('/') ? path : `/storage/${path}`;

const activeCategory = computed(() =>
    categories.value.find((category) =>
        route.params.category
            ? category.slug === route.params.category
            : String(category.id) === String(filters.category_id),
    ),
);

const hasNonCanonicalFilters = computed(() =>
    Boolean(
        route.query.search ||
        route.query.category_id ||
        route.query.brand_id ||
        route.query.min_price ||
        route.query.max_price ||
        (route.query.sort && route.query.sort !== 'newest'),
    ),
);

const canonical = computed(() => {
    const base = route.params.category
        ? `${window.location.origin}/${lang}/products/category/${route.params.category}`
        : `${window.location.origin}/${lang}/products`;
    return pagination.current_page > 1 ? `${base}?page=${pagination.current_page}` : base;
});

const pageUrl = (page) => {
    if (page < 1 || page > pagination.last_page) return null;
    const url = new URL(canonical.value);
    if (page > 1) url.searchParams.set("page", page);
    else url.searchParams.delete("page");
    return url.toString();
};

useSeoMeta({
    title: () => activeCategory.value?.meta_title || activeCategory.value?.name || "Shop Products",
    description: () =>
        activeCategory.value?.meta_description ||
        activeCategory.value?.description ||
        "Browse products by category, brand, price, and customer rating.",
    keywords: () => activeCategory.value?.meta_keywords,
    image: () => imageUrl(activeCategory.value?.og_image || activeCategory.value?.image),
    ogTitle: () => activeCategory.value?.og_title,
    ogDescription: () => activeCategory.value?.og_description,
    canonical: () => activeCategory.value?.canonical_url || canonical.value,
    robots: () => hasNonCanonicalFilters.value ? "noindex,follow" : "index,follow,max-image-preview:large",
    prev: () => pageUrl(pagination.current_page - 1),
    next: () => pageUrl(pagination.current_page + 1),
    schema: () => ({
        "@context": "https://schema.org",
        "@type": "ItemList",
        name: activeCategory.value?.name || "Products",
        numberOfItems: pagination.total,
        itemListElement: products.value.map((product, index) => ({
            "@type": "ListItem",
            position: (pagination.current_page - 1) * 12 + index + 1,
            url: `${window.location.origin}/${lang}/products/${product.slug || product.id}`,
            name: product.name,
        })),
    }),
});

const syncFiltersFromRoute = () => {
    filters.search = route.query.search || '';
    filters.category_id = route.query.category_id || '';
    filters.category_slug = route.params.category || '';
    filters.brand_id = route.query.brand_id || '';
    filters.min_price = route.query.min_price || '';
    filters.max_price = route.query.max_price || '';
    filters.sort = route.query.sort || 'newest';
};

const activeFilters = () => {
    const map = {
        search: filters.search,
        category_id: filters.category_id,
        category_slug: filters.category_slug,
        brand_id: filters.brand_id,
        min_price: filters.min_price,
        max_price: filters.max_price,
        sort: filters.sort,
    };
    return Object.fromEntries(Object.entries(map).filter(([, value]) => value !== ''));
};

const pushQuery = async (page) => {
    const query = { ...activeFilters() };
    if (page > 1) query.page = page;
    syncGuard.value = true;
    await router.replace({
        path: route.params.category
            ? `/${lang}/products/category/${route.params.category}`
            : `/${lang}/products`,
        query,
    });
    syncGuard.value = false;
};

const fetchProducts = async (page = 1, syncQuery = false) => {
    if (syncQuery) await pushQuery(page);
    loading.value = true;
    try {
        const response = await ProductService.getProducts({ page, per_page: 12, ...activeFilters() });
        const paginated = response.data || {};
        products.value = Array.isArray(paginated) ? paginated : (paginated.data || []);
        Object.assign(pagination, {
            current_page: paginated.current_page || 1,
            last_page: paginated.last_page || 1,
            total: paginated.total || 0,
        });
    } catch (err) {
        console.error('[Products] fetch failed:', err);
        toastr.error('Unable to load products.');
    } finally {
        loading.value = false;
    }
};

const fetchProductsFromRoute = async () => {
    syncFiltersFromRoute();
    const page = Number(route.query.page || 1);
    await fetchProducts(page, false);
};

const fetchFacets = async () => {
    try {
        const [categoryResponse, brandResponse] = await Promise.all([
            CategoryService.getCategories(),
            BrandService.getBrands(),
        ]);
        const catData = categoryResponse.data || {};
        const brdData = brandResponse.data || {};
        categories.value = Array.isArray(catData) ? catData : (catData.data || []);
        brands.value = Array.isArray(brdData) ? brdData : (brdData.data || []);
    } catch (err) {
        console.error('[Products] fetchFacets failed:', err);
    }
};

const changeCategory = async () => {
    const category = categories.value.find(
        (item) => String(item.id) === String(filters.category_id),
    );
    filters.category_slug = category?.slug || '';
    await router.push(
        category?.slug
            ? `/${lang}/products/category/${category.slug}`
            : `/${lang}/products`,
    );
};

const clearFilters = async () => {
    Object.assign(filters, { search: '', category_id: '', category_slug: '', brand_id: '', min_price: '', max_price: '', sort: 'newest' });
    if (route.params.category) await router.replace(`/${lang}/products`);
    await fetchProducts(1, true);
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

const toggleWishlist = async (product) => {
    busyId.value = product.id;
    try {
        const response = await WishlistService.toggle(product.id);
        product.is_wishlisted = response.data?.wishlisted;
    } finally {
        busyId.value = null;
    }
};

const money = (value) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(Number(value || 0));

watch(
    () => route.fullPath,
    async () => {
        if (syncGuard.value) return;
        await fetchProductsFromRoute();
    },
);

onMounted(async () => {
    await fetchFacets();
    await fetchProductsFromRoute();
});
</script>

<style scoped>
/* ==============================
   LAYOUT
============================== */
.products-page {
    display: grid;
    gap: 1.2rem;
}

.products-header {
    padding: clamp(1.2rem, 3vw, 1.8rem);
}

.products-layout {
    display: grid;
    grid-template-columns: 240px minmax(0, 1fr);
    gap: 1rem;
    align-items: start;
}

/* ==============================
   FILTERS SIDEBAR
============================== */
.filters {
    position: sticky;
    top: 88px;
    display: grid;
    gap: 0.7rem;
    padding: 1rem;
    height: fit-content;
    overflow: hidden;
}

.filters label {
    display: grid;
    gap: 0.25rem;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.03em;
    color: var(--sf-muted);
    text-transform: uppercase;
}

/* inputs و selects داخل الفلتر */
.filters .store-input,
.filters .store-select,
.filters .form-control,
.filters .form-select {
    height: 32px;
    padding: 0 0.6rem;
    font-size: 0.8rem;
    border-radius: 0.5rem;
    border: 1px solid var(--sf-border);
    background: var(--sf-surface-soft, var(--background));
    color: var(--sf-text);
    width: 100%;
    transition: border-color 0.15s;
    appearance: none;
    -webkit-appearance: none;
}

.filters .store-select,
.filters .form-select {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.55rem center;
    padding-right: 1.8rem;
    cursor: pointer;
}

.filters .store-input:focus,
.filters .store-select:focus,
.filters .form-control:focus,
.filters .form-select:focus {
    outline: none;
    border-color: var(--sf-primary);
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--sf-primary) 15%, transparent);
}

.filters .store-input::placeholder {
    color: var(--sf-muted);
    font-size: 0.78rem;
}

/* range row */
.range-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.5rem;
}

/* actions row */
.filters-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.5rem;
    padding-top: 0.2rem;
}

.filters-actions .store-btn {
    height: 32px;
    padding: 0 0.75rem;
    font-size: 0.78rem;
    font-weight: 700;
    border-radius: 0.5rem;
    border: none;
    cursor: pointer;
    transition: opacity 0.15s, background 0.15s;
    white-space: nowrap;
}

.filters-actions .store-btn--primary {
    background: var(--sf-primary);
    color: #fff;
}

.filters-actions .store-btn--primary:hover {
    opacity: 0.88;
}

.filters-actions .store-btn--soft {
    background: var(--sf-surface-soft, var(--surface));
    color: var(--sf-text);
    border: 1px solid var(--sf-border);
}

.filters-actions .store-btn--soft:hover {
    background: var(--sf-border);
}

/* ==============================
   PRODUCTS GRID
============================== */
.products-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1rem;
}

.product-card {
    overflow: hidden;
}

.product-card__image {
    display: block;
    aspect-ratio: 1 / 1.05;
    background: var(--sf-surface-soft);
}

.product-card__image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-card__body {
    display: grid;
    gap: 0.55rem;
    padding: 0.95rem;
}

.product-card__top,
.product-card__bottom {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.6rem;
}

.product-card__top span,
.product-card p {
    color: var(--sf-muted);
    font-size: 0.82rem;
}

.product-card__top button {
    border: 0;
    background: transparent;
    color: var(--sf-text);
    cursor: pointer;
}

.product-card h2 {
    margin: 0;
    min-height: 2.4rem;
    font-size: 1rem;
    font-weight: 800;
    color: var(--sf-text);
}

.product-card__bottom strong {
    color: var(--sf-text);
}

.product-card__bottom button {
    min-height: 34px;
    border: 0;
    border-radius: 0.55rem;
    background: var(--sf-primary);
    color: #fff;
    font-size: 0.8rem;
    font-weight: 700;
    padding: 0.3rem 0.75rem;
    cursor: pointer;
    transition: opacity 0.15s;
}

.product-card__bottom button:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

/* ==============================
   PAGINATION
============================== */
.results-pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.6rem;
    margin-top: 1rem;
    padding: 0.7rem 1rem;
}

.results-pagination span {
    font-size: 0.82rem;
    color: var(--sf-muted);
}

/* ==============================
   EMPTY STATE
============================== */
.empty-state {
    min-height: 280px;
    display: grid;
    place-items: center;
    text-align: center;
    gap: 0.45rem;
    padding: 1.2rem;
}

.empty-state i {
    font-size: 1.8rem;
    color: var(--sf-muted);
}

.empty-state h2 {
    margin: 0;
    color: var(--sf-text);
    font-size: 1.25rem;
}

.empty-state p {
    margin: 0;
    color: var(--sf-muted);
}

/* ==============================
   SKELETON
============================== */
.product-skeleton {
    min-height: 340px;
    border-radius: 1rem;
    background: linear-gradient(90deg,
            var(--sf-surface-soft),
            color-mix(in srgb, var(--sf-surface-soft) 75%, var(--sf-border)),
            var(--sf-surface-soft));
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

/* ==============================
   RESPONSIVE
============================== */
@media (max-width: 991.98px) {
    .products-layout {
        grid-template-columns: 1fr;
    }

    .filters {
        position: static;
    }

    .products-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 575.98px) {

    .products-grid,
    .range-row,
    .filters-actions {
        grid-template-columns: 1fr;
    }
}
</style>
