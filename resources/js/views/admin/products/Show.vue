<template>
    <AdminLayout>
        <div class="min-h-screen bg-slate-50">
            <div class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
                <nav class="flex flex-wrap items-center gap-2 text-sm text-slate-500">
                    <RouterLink to="/admin" class="transition hover:text-slate-700">Dashboard</RouterLink>
                    <span>/</span>
                    <RouterLink to="/admin/products" class="transition hover:text-slate-700">Products</RouterLink>
                    <span>/</span>
                    <span class="font-medium text-slate-700">{{ productName }}</span>
                </nav>

                <section
                    class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-[0_24px_60px_-32px_rgba(15,23,42,0.35)]"
                >
                    <div
                        class="flex flex-col gap-5 border-b border-slate-200 bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.12),_transparent_38%),linear-gradient(135deg,_#ffffff,_#f8fafc)] px-6 py-6 lg:flex-row lg:items-start lg:justify-between"
                    >
                        <div class="space-y-4">
                            <div class="flex flex-wrap items-center gap-3">
                                <span
                                    class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-sky-700"
                                >
                                    Product details
                                </span>
                                <span
                                    class="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600"
                                >
                                    ID #{{ product?.id ?? "--" }}
                                </span>
                            </div>

                            <div class="space-y-3">
                                <div class="flex flex-wrap items-center gap-3">
                                    <h1 class="text-3xl font-semibold tracking-tight text-slate-900">
                                        {{ productName }}
                                    </h1>

                                    <span
                                        class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold"
                                        :class="statusBadgeClass"
                                    >
                                        {{ statusLabel }}
                                    </span>

                                    <span
                                        v-if="isFeatured"
                                        class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700"
                                    >
                                        Featured
                                    </span>
                                </div>

                                <div class="flex flex-wrap items-center gap-3 text-sm text-slate-600">
                                    <span
                                        class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 font-medium"
                                    >
                                        <i class="bi bi-upc-scan text-slate-400"></i>
                                        <span>{{ product?.sku || "No SKU assigned" }}</span>
                                    </span>

                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 font-medium text-slate-600 transition hover:border-slate-300 hover:text-slate-900"
                                        @click="copySku"
                                    >
                                        <i class="bi bi-copy text-slate-400"></i>
                                        <span>{{ copiedSku ? "Copied" : "Copy SKU" }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <RouterLink
                                to="/admin/products"
                                class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-950"
                            >
                                <i class="bi bi-arrow-left"></i>
                                <span>Back to products</span>
                            </RouterLink>

                            <RouterLink
                                :to="`/admin/products/${route.params.id}/edit`"
                                class="inline-flex items-center gap-2 rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-slate-900/10 transition hover:bg-slate-800"
                            >
                                <i class="bi bi-pencil-square"></i>
                                <span>Edit Product</span>
                            </RouterLink>
                        </div>
                    </div>

                    <div v-if="loading" class="space-y-6 px-6 py-6">
                        <div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
                            <div class="space-y-4">
                                <div class="h-[380px] animate-pulse rounded-3xl bg-slate-200"></div>
                                <div class="grid grid-cols-4 gap-3">
                                    <div
                                        v-for="thumb in 4"
                                        :key="thumb"
                                        class="h-20 animate-pulse rounded-2xl bg-slate-200"
                                    ></div>
                                </div>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div
                                    v-for="item in 6"
                                    :key="item"
                                    class="h-28 animate-pulse rounded-3xl bg-slate-100"
                                ></div>
                            </div>
                        </div>

                        <div class="grid gap-6 lg:grid-cols-2">
                            <div
                                v-for="section in 4"
                                :key="section"
                                class="space-y-4 rounded-3xl border border-slate-200 bg-slate-50 p-6"
                            >
                                <div class="h-5 w-32 animate-pulse rounded-full bg-slate-200"></div>
                                <div class="h-4 w-full animate-pulse rounded-full bg-slate-200"></div>
                                <div class="h-4 w-4/5 animate-pulse rounded-full bg-slate-200"></div>
                            </div>
                        </div>
                    </div>

                    <div v-else-if="notFound" class="px-6 py-16">
                        <div
                            class="mx-auto flex max-w-xl flex-col items-center rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-6 py-12 text-center"
                        >
                            <div
                                class="mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-white text-2xl text-slate-400 shadow-sm"
                            >
                                <i class="bi bi-box-seam"></i>
                            </div>
                            <h2 class="text-2xl font-semibold text-slate-900">Product not found</h2>
                            <p class="mt-3 max-w-md text-sm leading-6 text-slate-500">
                                The requested product could not be loaded. It may have been removed, or the link may be
                                invalid.
                            </p>
                            <RouterLink
                                to="/admin/products"
                                class="mt-6 inline-flex items-center gap-2 rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800"
                            >
                                <i class="bi bi-grid"></i>
                                <span>Browse Products</span>
                            </RouterLink>
                        </div>
                    </div>

                    <div v-else class="space-y-6 px-6 py-6">
                        <div class="grid gap-6 xl:grid-cols-[1.18fr_0.82fr]">
                            <section class="rounded-3xl border border-slate-200 bg-slate-50/80 p-4 sm:p-5">
                                <div
                                    class="relative flex h-[300px] items-center justify-center overflow-hidden rounded-[28px] bg-white sm:h-[420px]"
                                >
                                    <img
                                        v-if="mainImage"
                                        :src="mainImage"
                                        :alt="productName"
                                        class="h-full w-full object-cover"
                                    />
                                    <div
                                        v-else
                                        class="flex h-full w-full flex-col items-center justify-center gap-3 bg-[linear-gradient(135deg,_#f8fafc,_#e2e8f0)] text-slate-400"
                                    >
                                        <i class="bi bi-image text-5xl"></i>
                                        <span class="text-sm font-medium">No product images available</span>
                                    </div>
                                </div>

                                <div
                                    class="mt-4 grid gap-3"
                                    :class="imageGallery.length > 0 ? 'grid-cols-2 sm:grid-cols-4' : 'grid-cols-1'"
                                >
                                    <button
                                        v-for="(image, index) in imageGallery"
                                        :key="`${image}-${index}`"
                                        type="button"
                                        class="group relative overflow-hidden rounded-2xl border bg-white transition"
                                        :class="
                                            image === selectedImage
                                                ? 'border-slate-900 ring-2 ring-slate-900/10'
                                                : 'border-slate-200 hover:border-slate-300'
                                        "
                                        @click="selectedImage = image"
                                    >
                                        <img
                                            :src="image"
                                            :alt="`${productName} ${index + 1}`"
                                            class="h-20 w-full object-cover transition duration-300 group-hover:scale-[1.03]"
                                        />
                                    </button>

                                    <div
                                        v-if="imageGallery.length === 0"
                                        class="rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-6 text-center text-sm text-slate-400"
                                    >
                                        Upload gallery images to preview them here.
                                    </div>
                                </div>
                            </section>

                            <section class="grid gap-4 sm:grid-cols-2">
                                <article
                                    v-for="item in infoCards"
                                    :key="item.label"
                                    class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/50"
                                >
                                    <div class="mb-4 flex items-center justify-between">
                                        <span class="text-sm font-medium text-slate-500">{{ item.label }}</span>
                                        <span
                                            class="flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-100 text-slate-500"
                                        >
                                            <i :class="item.icon"></i>
                                        </span>
                                    </div>
                                    <div class="text-lg font-semibold tracking-tight text-slate-900">
                                        {{ item.value }}
                                    </div>
                                </article>
                            </section>
                        </div>

                        <div class="grid gap-6 xl:grid-cols-[1fr_0.88fr]">
                            <section class="space-y-6">
                                <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                                    <div class="mb-4 flex items-center gap-3">
                                        <span
                                            class="flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-100 text-slate-600"
                                        >
                                            <i class="bi bi-card-text"></i>
                                        </span>
                                        <div>
                                            <h2 class="text-lg font-semibold text-slate-900">Description</h2>
                                            <p class="text-sm text-slate-500">Product summary and buying notes.</p>
                                        </div>
                                    </div>

                                    <div class="grid gap-5 lg:grid-cols-2">
                                        <div class="rounded-2xl bg-slate-50 p-5">
                                            <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">
                                                Product Description
                                            </h3>
                                            <p class="mt-3 whitespace-pre-line text-sm leading-7 text-slate-700">
                                                {{ product?.description || "No description available." }}
                                            </p>
                                        </div>

                                        <div class="rounded-2xl bg-slate-50 p-5">
                                            <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">
                                                Return Policy
                                            </h3>
                                            <p class="mt-3 whitespace-pre-line text-sm leading-7 text-slate-700">
                                                {{ product?.return_policy || "No return policy provided." }}
                                            </p>
                                        </div>
                                    </div>
                                </article>

                                <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                                    <div class="mb-5 flex items-center gap-3">
                                        <span
                                            class="flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-100 text-slate-600"
                                        >
                                            <i class="bi bi-layers"></i>
                                        </span>
                                        <div>
                                            <h2 class="text-lg font-semibold text-slate-900">Variants</h2>
                                            <p class="text-sm text-slate-500">Available sizes and colors for this product.</p>
                                        </div>
                                    </div>

                                    <div class="grid gap-6 lg:grid-cols-2">
                                        <div>
                                            <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">
                                                Sizes
                                            </h3>
                                            <div class="mt-4 flex flex-wrap gap-2">
                                                <span
                                                    v-for="size in sizeBadges"
                                                    :key="size.key"
                                                    class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-sm font-medium text-slate-700"
                                                >
                                                    {{ size.label }}
                                                </span>
                                                <span
                                                    v-if="sizeBadges.length === 0"
                                                    class="text-sm text-slate-400"
                                                >
                                                    No sizes assigned.
                                                </span>
                                            </div>
                                        </div>

                                        <div>
                                            <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">
                                                Colors
                                            </h3>
                                            <div class="mt-4 flex flex-wrap gap-2">
                                                <span
                                                    v-for="color in colorBadges"
                                                    :key="color.key"
                                                    class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700"
                                                >
                                                    <span
                                                        class="h-3 w-3 rounded-full border border-slate-300"
                                                        :style="{ backgroundColor: color.swatch }"
                                                    ></span>
                                                    <span>{{ color.label }}</span>
                                                </span>
                                                <span
                                                    v-if="colorBadges.length === 0"
                                                    class="text-sm text-slate-400"
                                                >
                                                    No colors assigned.
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            </section>

                            <section class="space-y-6">
                                <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                                    <div class="mb-5 flex items-center gap-3">
                                        <span
                                            class="flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-100 text-slate-600"
                                        >
                                            <i class="bi bi-search"></i>
                                        </span>
                                        <div>
                                            <h2 class="text-lg font-semibold text-slate-900">SEO</h2>
                                            <p class="text-sm text-slate-500">Search metadata currently attached to this product.</p>
                                        </div>
                                    </div>

                                    <div class="space-y-4">
                                        <div class="rounded-2xl bg-slate-50 p-5">
                                            <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">
                                                Meta Title
                                            </h3>
                                            <p class="mt-3 text-sm leading-7 text-slate-700">
                                                {{ product?.meta_title || "No meta title available." }}
                                            </p>
                                        </div>

                                        <div class="rounded-2xl bg-slate-50 p-5">
                                            <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">
                                                Meta Description
                                            </h3>
                                            <p class="mt-3 text-sm leading-7 text-slate-700">
                                                {{ product?.meta_description || "No meta description available." }}
                                            </p>
                                        </div>
                                    </div>
                                </article>

                                <article
                                    class="rounded-3xl border border-slate-200 bg-[linear-gradient(145deg,_#0f172a,_#1e293b)] p-6 text-white shadow-lg shadow-slate-900/20"
                                >
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <p class="text-sm uppercase tracking-[0.2em] text-slate-300">Inventory Snapshot</p>
                                            <h2 class="mt-2 text-2xl font-semibold">{{ quantityLabel }}</h2>
                                            <p class="mt-2 max-w-sm text-sm leading-6 text-slate-300">
                                                Keep an eye on stock availability, pricing, and merchandising visibility from one place.
                                            </p>
                                        </div>
                                        <span
                                            class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/10 text-xl text-white"
                                        >
                                            <i class="bi bi-box2-heart"></i>
                                        </span>
                                    </div>

                                    <div class="mt-6 grid grid-cols-2 gap-3">
                                        <div class="rounded-2xl bg-white/5 p-4">
                                            <p class="text-xs uppercase tracking-[0.18em] text-slate-300">Category</p>
                                            <p class="mt-2 text-sm font-semibold text-white">{{ categoryName }}</p>
                                        </div>
                                        <div class="rounded-2xl bg-white/5 p-4">
                                            <p class="text-xs uppercase tracking-[0.18em] text-slate-300">Brand</p>
                                            <p class="mt-2 text-sm font-semibold text-white">{{ brandName }}</p>
                                        </div>
                                        <div class="rounded-2xl bg-white/5 p-4">
                                            <p class="text-xs uppercase tracking-[0.18em] text-slate-300">Price</p>
                                            <p class="mt-2 text-sm font-semibold text-white">{{ priceLabel }}</p>
                                        </div>
                                        <div class="rounded-2xl bg-white/5 p-4">
                                            <p class="text-xs uppercase tracking-[0.18em] text-slate-300">Tax</p>
                                            <p class="mt-2 text-sm font-semibold text-white">{{ taxLabel }}</p>
                                        </div>
                                    </div>
                                </article>
                            </section>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { RouterLink, useRoute } from "vue-router";
import api from "@/services/AdminApiClient";
import AdminLayout from "@/views/admin/layout/AdminLayout.vue";

const route = useRoute();

const loading = ref(false);
const product = ref(null);
const notFound = ref(false);
const selectedImage = ref("");
const copiedSku = ref(false);

const placeholderColor = "#cbd5e1";

const normalizeResponseProduct = (payload) => payload?.data?.product ?? payload?.data ?? payload?.product ?? null;

const normalizeImagePath = (value) => {
    if (!value) {
        return null;
    }

    if (typeof value !== "string") {
        return null;
    }

    if (value.startsWith("http://") || value.startsWith("https://")) {
        return value;
    }

    return `http://localhost:8000/storage/${value.replace(/^\/+/, "")}`;
};

const formatMoney = (value) => {
    if (value === null || value === undefined || value === "") {
        return "--";
    }

    const number = Number(value);

    if (Number.isNaN(number)) {
        return String(value);
    }

    return new Intl.NumberFormat("en-US", {
        style: "currency",
        currency: "USD",
        maximumFractionDigits: 2,
    }).format(number);
};

const imageGallery = computed(() => {
    const gallery = product.value?.images
        ?.map((item) => normalizeImagePath(item?.image ?? item?.url ?? item?.path))
        ?.filter(Boolean) ?? [];

    const coverImage = normalizeImagePath(product.value?.image);

    if (coverImage && !gallery.includes(coverImage)) {
        gallery.unshift(coverImage);
    }

    return gallery;
});

const mainImage = computed(() => selectedImage.value || imageGallery.value?.[0] || null);

const productName = computed(() => product.value?.name || "Product Overview");
const isFeatured = computed(() => Boolean(product.value?.is_featured));
const statusLabel = computed(() => (product.value?.is_active ? "Active" : "Inactive"));
const statusBadgeClass = computed(() =>
    product.value?.is_active
        ? "border-emerald-200 bg-emerald-50 text-emerald-700"
        : "border-rose-200 bg-rose-50 text-rose-700"
);

const priceLabel = computed(() => formatMoney(product.value?.price));
const taxLabel = computed(() => {
    const tax = product.value?.tax;

    if (tax === null || tax === undefined || tax === "") {
        return "--";
    }

    return `${tax}${String(tax).includes("%") ? "" : "%"}`;
});

const quantityLabel = computed(() => {
    const quantity = product.value?.stocks?.quantity ?? product.value?.stock?.quantity;
    return quantity ?? "--";
});

const categoryName = computed(() => product.value?.category?.name || "Unassigned");
const brandName = computed(() => product.value?.brand?.name || "Unassigned");
const slugLabel = computed(() => product.value?.slug || "--");

const infoCards = computed(() => [
    { label: "Price", value: priceLabel.value, icon: "bi bi-cash-stack" },
    { label: "Tax", value: taxLabel.value, icon: "bi bi-percent" },
    { label: "Quantity", value: quantityLabel.value, icon: "bi bi-boxes" },
    { label: "Category", value: categoryName.value, icon: "bi bi-diagram-3" },
    { label: "Brand", value: brandName.value, icon: "bi bi-award" },
    { label: "Slug", value: slugLabel.value, icon: "bi bi-link-45deg" },
]);

const sizeBadges = computed(() =>
    product.value?.sizes
        ?.map((item, index) => ({
            key: item?.id ?? `${item?.size ?? item}-${index}`,
            label: item?.size ?? item ?? "--",
        }))
        ?.filter((item) => item.label && item.label !== "--") ?? []
);

const colorBadges = computed(() =>
    product.value?.colors
        ?.map((item, index) => {
            const label = item?.color ?? item?.name ?? item ?? "--";
            return {
                key: item?.id ?? `${label}-${index}`,
                label,
                swatch: /^#|^rgb|^hsl/i.test(String(label)) ? String(label) : placeholderColor,
            };
        })
        ?.filter((item) => item.label && item.label !== "--") ?? []
);

const requestProduct = async (id) => {
    try {
        return await api.get("/admin/products/show", {
            params: { id },
        });
    } catch (error) {
        if (error?.response?.status !== 404) {
            throw error;
        }

        return api.get(`/admin/products/${id}`, {
            params: { id },
        });
    }
};

const fetchProduct = async () => {
    loading.value = true;
    notFound.value = false;

    try {
        const response = await requestProduct(route.params.id);

        const record = normalizeResponseProduct(response?.data);
        product.value = record;
        notFound.value = !record;
    } catch (error) {
        product.value = null;
        notFound.value = error?.response?.status === 404;
        console.error(`Failed to fetch product ${route.params.id}:`, error);
    } finally {
        loading.value = false;
    }
};

const copySku = async () => {
    const sku = product.value?.sku;

    if (!sku || !navigator?.clipboard) {
        return;
    }

    try {
        await navigator.clipboard.writeText(sku);
        copiedSku.value = true;
        window.setTimeout(() => {
            copiedSku.value = false;
        }, 1600);
    } catch (error) {
        console.error("Failed to copy SKU:", error);
    }
};

watch(
    imageGallery,
    (gallery) => {
        selectedImage.value = gallery?.includes(selectedImage.value) ? selectedImage.value : gallery?.[0] || "";
    },
    { immediate: true }
);

watch(
    () => route.params.id,
    () => {
        fetchProduct();
    }
);

onMounted(() => {
    fetchProduct();
});
</script>
