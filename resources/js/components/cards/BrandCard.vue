<template>
    <RouterLink :to="brandLink" class="brand-card">
        <div class="brand-card__monogram">{{ monogram }}</div>
        <div class="brand-card__body">
            <span class="brand-card__eyebrow">Brand</span>
            <h3>{{ brand.name }}</h3>
            <p>{{ brand.productsCount }} curated products</p>
        </div>
        <i class="bi bi-arrow-up-right brand-card__icon"></i>
    </RouterLink>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
    brand: { type: Object, required: true },
    lang: { type: String, default: "en" },
});

const monogram = computed(() => (props.brand.name || "B").split(" ").map((part) => part[0]).join("").slice(0, 2).toUpperCase());
const brandLink = computed(() => `/${props.lang}/products?brand=${props.brand.slug || props.brand.id}`);
</script>

<style scoped>
.brand-card {
    display: grid;
    grid-template-columns: auto 1fr auto;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.1rem;
    border-radius: 1.2rem;
    border: 1px solid var(--premium-border);
    background: var(--premium-surface);
    color: inherit;
    text-decoration: none;
    box-shadow: var(--premium-shadow-sm);
}

.brand-card__monogram {
    display: grid;
    place-items: center;
    width: 52px;
    height: 52px;
    border-radius: 1rem;
    background: linear-gradient(135deg, var(--premium-ink), #272f3f);
    color: #fff;
    font-weight: 800;
    letter-spacing: .08em;
}

.brand-card__eyebrow {
    display: inline-flex;
    margin-bottom: .25rem;
    font-size: .68rem;
    font-weight: 800;
    letter-spacing: .14em;
    text-transform: uppercase;
    color: var(--premium-muted);
}

.brand-card h3 {
    margin: 0 0 .2rem;
    font-size: .98rem;
    color: var(--premium-ink);
}

.brand-card p {
    margin: 0;
    color: var(--premium-muted);
    font-size: .9rem;
}

.brand-card__icon {
    color: var(--premium-gold-ink);
}
</style>
