<template>
    <section ref="target" class="section-shell section-shell--soft" :class="{ 'is-visible': inView }">
        <SectionTitle eyebrow="Partners" title="Brands we carry" description="A tightly edited mix of labels with strong material stories and clean design codes." />

        <div v-if="loading" class="brand-strip">
            <SkeletonLoader v-for="n in 6" :key="n" height="76px" radius="1.2rem" />
        </div>

        <div v-else class="brand-strip">
            <BrandCard v-for="brand in brands" :key="brand.id" :brand="brand" :lang="lang" />
        </div>
    </section>
</template>

<script setup>
import SectionTitle from "@/components/ui/SectionTitle.vue";
import SkeletonLoader from "@/components/ui/SkeletonLoader.vue";
import BrandCard from "@/components/cards/BrandCard.vue";
import { useIntersectionObserver } from "@/composables/useIntersectionObserver";

defineProps({
    brands: { type: Array, default: () => [] },
    lang: { type: String, default: "en" },
    loading: { type: Boolean, default: false },
});

const { target, inView } = useIntersectionObserver({ threshold: 0.12 });
</script>

<style scoped>
.brand-strip {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: .9rem;
}

@media (max-width: 800px) {
    .brand-strip {
        grid-template-columns: 1fr;
    }
}
</style>
