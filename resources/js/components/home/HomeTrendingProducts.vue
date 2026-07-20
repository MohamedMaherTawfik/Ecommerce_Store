<template>
    <section ref="target" class="section-shell section-shell--soft" :class="{ 'is-visible': inView }">
        <SectionTitle eyebrow="Now" title="Trending products" description="Pieces moving quickly because they feel current without feeling disposable." />

        <div v-if="loading" class="product-grid">
            <SkeletonLoader v-for="n in 4" :key="n" height="420px" radius="1.35rem" />
        </div>

        <div v-else class="product-grid">
            <ProductCard
                v-for="product in products"
                :key="product.id"
                :product="product"
                :lang="lang"
                :adding="addingId === product.id"
                @add-to-cart="$emit('add-to-cart', $event)"
                @toggle-wishlist="$emit('toggle-wishlist', $event)"
                @quick-view="$emit('quick-view', $event)"
            />
        </div>
    </section>
</template>

<script setup>
import SectionTitle from "@/components/ui/SectionTitle.vue";
import SkeletonLoader from "@/components/ui/SkeletonLoader.vue";
import ProductCard from "@/components/cards/ProductCard.vue";
import { useIntersectionObserver } from "@/composables/useIntersectionObserver";

defineProps({
    products: { type: Array, default: () => [] },
    lang: { type: String, default: "en" },
    loading: { type: Boolean, default: false },
    addingId: { type: [Number, String, null], default: null },
});

defineEmits(["add-to-cart", "toggle-wishlist", "quick-view"]);

const { target, inView } = useIntersectionObserver({ threshold: 0.12 });
</script>

<style scoped>
.product-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 1rem;
}

@media (max-width: 1100px) {
    .product-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 620px) {
    .product-grid {
        grid-template-columns: 1fr;
    }
}
</style>
