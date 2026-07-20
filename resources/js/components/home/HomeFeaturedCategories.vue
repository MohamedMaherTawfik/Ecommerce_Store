<template>
    <section ref="target" class="section-shell" :class="{ 'is-visible': inView }">
        <SectionTitle eyebrow="Browse" title="Shop by category" description="Refined collections, thoughtfully organized for faster discovery.">
            <template #actions>
                <Button as="RouterLink" variant="secondary" :to="`/${lang}/products`">View all</Button>
            </template>
        </SectionTitle>

        <div v-if="loading" class="category-grid">
            <SkeletonLoader v-for="n in 6" :key="n" height="220px" radius="1.35rem" />
        </div>

        <div v-else class="category-grid">
            <CategoryCard v-for="category in categories" :key="category.id" :category="category" :lang="lang" />
        </div>
    </section>
</template>

<script setup>
import Button from "@/components/ui/Button.vue";
import SectionTitle from "@/components/ui/SectionTitle.vue";
import SkeletonLoader from "@/components/ui/SkeletonLoader.vue";
import CategoryCard from "@/components/cards/CategoryCard.vue";
import { useIntersectionObserver } from "@/composables/useIntersectionObserver";

defineProps({
    categories: { type: Array, default: () => [] },
    lang: { type: String, default: "en" },
    loading: { type: Boolean, default: false },
});

const { target, inView } = useIntersectionObserver({ threshold: 0.12 });
</script>

<style scoped>
.category-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1rem;
}

@media (max-width: 1100px) {
    .category-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 640px) {
    .category-grid {
        grid-template-columns: 1fr;
    }
}
</style>
