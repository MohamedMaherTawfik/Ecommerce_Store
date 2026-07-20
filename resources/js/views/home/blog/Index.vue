<template>
    <main class="store-shell py-5">
        <header class="store-card p-4 mb-4">
            <span class="store-eyebrow">Journal</span>
            <h1 class="store-title">{{ activeCategory?.name || activeTag?.name || "Blog" }}</h1>
            <p v-if="activeCategory?.description">{{ activeCategory.description }}</p>
            <form class="d-flex gap-2 mt-3" @submit.prevent="search">
                <input v-model="filters.search" class="store-input form-control" placeholder="Search articles" />
                <select v-model="filters.category" class="store-input form-select">
                    <option value="">All categories</option>
                    <option v-for="category in categories" :key="category.id" :value="category.slug">
                        {{ category.name }}
                    </option>
                </select>
                <button class="store-btn store-btn--primary">Search</button>
            </form>
            <div class="d-flex flex-wrap gap-2 mt-3">
                <RouterLink v-for="tag in tags" :key="tag.id" :to="`/${lang}/blog/tag/${tag.slug}`" class="badge text-bg-light text-decoration-none">
                    #{{ tag.name }}
                </RouterLink>
            </div>
        </header>

        <section class="row g-4">
            <article v-for="post in posts" :key="post.id" class="col-md-6 col-lg-4">
                <div class="store-card h-100 overflow-hidden">
                    <img
                        v-if="post.featured_image"
                        :src="image(post.featured_image)"
                        :alt="post.title"
                        class="w-100 blog-image"
                        width="800"
                        height="450"
                        loading="lazy"
                        decoding="async"
                    />
                    <div class="p-4">
                        <small>{{ post.category?.name }} - {{ date(post.published_at) }}</small>
                        <h2 class="h4 mt-2">{{ post.title }}</h2>
                        <p>{{ post.excerpt }}</p>
                        <RouterLink :to="`/${lang}/blog/${post.slug}`">Read article</RouterLink>
                    </div>
                </div>
            </article>
        </section>

        <nav v-if="pagination.last_page > 1" class="store-card d-flex justify-content-between align-items-center p-3 mt-4" aria-label="Blog pagination">
            <button class="store-btn store-btn--soft" :disabled="pagination.current_page <= 1" @click="goToPage(pagination.current_page - 1)">Previous</button>
            <span>Page {{ pagination.current_page }} of {{ pagination.last_page }}</span>
            <button class="store-btn store-btn--soft" :disabled="pagination.current_page >= pagination.last_page" @click="goToPage(pagination.current_page + 1)">Next</button>
        </nav>
    </main>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from "vue";
import { RouterLink, useRoute, useRouter } from "vue-router";
import { useSeoMeta } from "@/composables/useSeoMeta";
import service from "@/services/home/BlogService";

const route = useRoute();
const router = useRouter();
const lang = computed(() => route.params.lang || "en");
const posts = ref([]);
const categories = ref([]);
const tags = ref([]);
const pagination = reactive({ current_page: 1, last_page: 1, total: 0 });
const filters = reactive({ search: "", category: "", tag: "" });
const activeCategory = computed(() =>
    categories.value.find((category) => category.slug === filters.category),
);
const activeTag = computed(() => tags.value.find((tag) => tag.slug === filters.tag));
const canonical = computed(() => {
    const suffix = route.params.category
        ? `/category/${route.params.category}`
        : route.params.tag
            ? `/tag/${route.params.tag}`
            : "";
    const page = pagination.current_page > 1 ? `?page=${pagination.current_page}` : "";
    return `${window.location.origin}/${lang.value}/blog${suffix}${page}`;
});
const pageUrl = (page) => {
    if (page < 1 || page > pagination.last_page) return null;
    const url = new URL(canonical.value);
    if (page > 1) url.searchParams.set("page", page);
    else url.searchParams.delete("page");
    return url.toString();
};

useSeoMeta({
    title: () => activeCategory.value?.meta_title || activeCategory.value?.name || activeTag.value?.name || "Blog",
    description: () =>
        activeCategory.value?.meta_description ||
        activeCategory.value?.description ||
        "News, product guides, and stories from our store.",
    canonical: () => canonical.value,
    robots: () => filters.search ? "noindex,follow" : "index,follow,max-image-preview:large",
    prev: () => pageUrl(pagination.current_page - 1),
    next: () => pageUrl(pagination.current_page + 1),
    schema: () => ({
        "@context": "https://schema.org",
        "@type": "CollectionPage",
        name: activeCategory.value?.name || activeTag.value?.name || "Blog",
        url: canonical.value,
        mainEntity: {
            "@type": "ItemList",
            itemListElement: posts.value.map((post, index) => ({
                "@type": "ListItem",
                position: (pagination.current_page - 1) * 12 + index + 1,
                name: post.title,
                url: `${window.location.origin}/${lang.value}/blog/${post.slug}`,
            })),
        },
    }),
});

const syncRoute = () => {
    filters.search = route.query.search || "";
    filters.category = route.params.category || route.query.category || "";
    filters.tag = route.params.tag || route.query.tag || "";
};

const load = async () => {
    const response = await service.list({
        ...filters,
        page: Number(route.query.page || 1),
        per_page: 12,
    });
    const data = response.data || {};
    posts.value = data.data || [];
    Object.assign(pagination, {
        current_page: data.current_page || 1,
        last_page: data.last_page || 1,
        total: data.total || 0,
    });
};

const search = () => router.push({
    path: filters.category
        ? `/${lang.value}/blog/category/${filters.category}`
        : `/${lang.value}/blog`,
    query: filters.search ? { search: filters.search } : {},
});

const goToPage = (page) => router.push({ path: route.path, query: { ...route.query, page } });
const image = (value) => value?.startsWith("http") ? value : `/storage/${value}`;
const date = (value) => value ? new Date(value).toLocaleDateString() : "";

onMounted(async () => {
    syncRoute();
    [categories.value, tags.value] = await Promise.all([
        service.categories().then((response) => response.data || []),
        service.tags().then((response) => response.data || []),
    ]);
    await load();
});

watch(() => route.fullPath, async () => {
    syncRoute();
    await load();
});
</script>

<style scoped>
.blog-image {
    height: 220px;
    object-fit: cover;
}
</style>
