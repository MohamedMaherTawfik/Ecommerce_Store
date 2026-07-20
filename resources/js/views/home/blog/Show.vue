<template>
    <main v-if="post" class="store-shell py-5">
        <nav class="store-card p-3 mb-3" aria-label="Breadcrumb">
            <RouterLink :to="`/${lang}`">Home</RouterLink>
            <span class="mx-2">/</span>
            <RouterLink :to="`/${lang}/blog`">Blog</RouterLink>
            <span class="mx-2">/</span>
            <span aria-current="page">{{ post.title }}</span>
        </nav>

        <article class="store-card p-4 p-lg-5">
            <img
                v-if="post.featured_image"
                :src="image(post.featured_image)"
                :alt="post.title"
                class="w-100 rounded mb-4 hero"
                width="1200"
                height="675"
                fetchpriority="high"
                decoding="async"
            />
            <small>{{ post.category?.name }} - {{ date(post.published_at) }}</small>
            <h1 class="store-title mt-2">{{ post.title }}</h1>
            <p class="lead">{{ post.excerpt }}</p>
            <div class="blog-content" v-html="post.content"></div>
            <div class="mt-4">
                <RouterLink v-for="tag in post.tags || []" :key="tag.id" :to="`/${lang}/blog/tag/${tag.slug}`" class="badge text-bg-light me-2">
                    #{{ tag.name }}
                </RouterLink>
            </div>
        </article>
    </main>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { RouterLink, useRoute } from "vue-router";
import { useSeoMeta } from "@/composables/useSeoMeta";
import service from "@/services/home/BlogService";

const route = useRoute();
const lang = computed(() => route.params.lang || "en");
const post = ref(null);
const image = (value) => !value ? null : value.startsWith("http") || value.startsWith("/") ? value : `/storage/${value}`;
const date = (value) => value ? new Date(value).toLocaleDateString() : "";

useSeoMeta({
    title: () => post.value?.meta_title || post.value?.title || "Blog",
    description: () => post.value?.meta_description || post.value?.excerpt,
    keywords: () => post.value?.meta_keywords,
    image: () => image(post.value?.og_image || post.value?.featured_image),
    ogTitle: () => post.value?.og_title,
    ogDescription: () => post.value?.og_description,
    twitterTitle: () => post.value?.twitter_title,
    twitterDescription: () => post.value?.twitter_description,
    twitterImage: () => image(post.value?.twitter_image),
    canonical: () => post.value?.canonical_url || `${window.location.origin}/${lang.value}/blog/${route.params.slug}`,
    type: "article",
    schema: () => post.value ? [
        {
            "@context": "https://schema.org",
            "@type": "Article",
            headline: post.value.title,
            description: post.value.meta_description || post.value.excerpt,
            image: [image(post.value.og_image || post.value.featured_image)].filter(Boolean),
            datePublished: post.value.published_at,
            dateModified: post.value.updated_at,
            author: {
                "@type": "Person",
                name: post.value.author?.name || "EliteShop",
            },
            mainEntityOfPage: `${window.location.origin}/${lang.value}/blog/${post.value.slug}`,
        },
        {
            "@context": "https://schema.org",
            "@type": "BreadcrumbList",
            itemListElement: [
                { "@type": "ListItem", position: 1, name: "Home", item: `${window.location.origin}/${lang.value}` },
                { "@type": "ListItem", position: 2, name: "Blog", item: `${window.location.origin}/${lang.value}/blog` },
                { "@type": "ListItem", position: 3, name: post.value.title, item: `${window.location.origin}/${lang.value}/blog/${post.value.slug}` },
            ],
        },
    ] : null,
});

const load = async () => {
    post.value = (await service.show(route.params.slug)).data;
};

onMounted(load);
watch(() => route.params.slug, load);
</script>

<style scoped>
.hero {
    max-height: 520px;
    object-fit: cover;
}

.blog-content {
    font-size: 1.08rem;
    line-height: 1.8;
}

.blog-content :deep(img) {
    max-width: 100%;
    height: auto;
}
</style>
