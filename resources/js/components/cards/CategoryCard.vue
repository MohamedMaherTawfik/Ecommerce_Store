<template>
    <RouterLink :to="categoryLink" class="category-card">
        <div class="category-card__media">
            <img :src="imageUrl(category.image, 900)" :alt="category.name" loading="lazy" decoding="async" />
        </div>
        <div class="category-card__body">
            <span class="category-card__eyebrow">Collection</span>
            <h3>{{ category.name }}</h3>
            <p>{{ category.productsCount }} products</p>
        </div>
        <i class="bi bi-arrow-up-right category-card__icon"></i>
    </RouterLink>
</template>

<script setup>
import { computed } from "vue";
import { imageUrl } from "@/utils/image";

const props = defineProps({
    category: { type: Object, required: true },
    lang: { type: String, default: "en" },
});

const categoryLink = computed(() => `/${props.lang}/products/category/${props.category.slug}`);
</script>

<style scoped>
.category-card {
    position: relative;
    overflow: hidden;
    display: grid;
    min-height: 220px;
    border-radius: 1.35rem;
    border: 1px solid var(--premium-border);
    background: var(--premium-surface);
    color: inherit;
    text-decoration: none;
    box-shadow: var(--premium-shadow-sm);
}

.category-card__media {
    position: absolute;
    inset: 0;
}

.category-card__media img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    filter: saturate(.88) contrast(1.02);
    transform: scale(1.02);
}

.category-card::after {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, color-mix(in srgb, var(--hero-to) 8%, transparent) 0%, color-mix(in srgb, var(--hero-to) 76%, transparent) 100%);
}

.category-card__body,
.category-card__icon {
    position: relative;
    z-index: 1;
}

.category-card__body {
    align-self: end;
    padding: 1rem;
    color: #fff;
}

.category-card__eyebrow {
    display: inline-flex;
    margin-bottom: .45rem;
    font-size: .68rem;
    font-weight: 800;
    letter-spacing: .14em;
    text-transform: uppercase;
    color: rgba(255, 255, 255, .78);
}

.category-card h3 {
    margin: 0 0 .25rem;
    font-size: 1.15rem;
    letter-spacing: -.02em;
}

.category-card p {
    margin: 0;
    color: rgba(255, 255, 255, .78);
    font-size: .9rem;
}

.category-card__icon {
    position: absolute;
    top: 1rem;
    right: 1rem;
    display: grid;
    place-items: center;
    width: 42px;
    height: 42px;
    border-radius: 999px;
    background: rgba(255, 255, 255, .12);
    color: #fff;
    backdrop-filter: blur(8px);
}
</style>
