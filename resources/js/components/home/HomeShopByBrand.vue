<template>
    <section ref="target" class="section-shell" :class="{ 'is-visible': inView }">
        <SectionTitle eyebrow="Editorial" title="Shop by brand" description="A more immersive look at the labels shaping the season." />

        <div class="brand-layout">
            <div class="brand-layout__featured">
                <div class="brand-layout__panel">
                    <span>Premium shortlist</span>
                    <h3>Quiet luxury with a modern edge.</h3>
                    <p>Made to help customers navigate the collection through the designers and ateliers they already trust.</p>
                </div>
            </div>

            <div class="brand-layout__grid">
                <BrandCard v-for="brand in brands" :key="brand.id" :brand="brand" :lang="lang" />
            </div>
        </div>
    </section>
</template>

<script setup>
import SectionTitle from "@/components/ui/SectionTitle.vue";
import BrandCard from "@/components/cards/BrandCard.vue";
import { useIntersectionObserver } from "@/composables/useIntersectionObserver";

defineProps({
    brands: { type: Array, default: () => [] },
    lang: { type: String, default: "en" },
});

const { target, inView } = useIntersectionObserver({ threshold: 0.15 });
</script>

<style scoped>
.brand-layout {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.brand-layout__featured {
    min-height: 100%;
    border-radius: 1.5rem;
    background:
        linear-gradient(135deg, rgba(15, 23, 42, .95), rgba(15, 23, 42, .84)),
        radial-gradient(circle at top right, rgba(212, 175, 55, .22), transparent 36%);
    padding: clamp(1.25rem, 3vw, 2rem);
    color: #fff;
    box-shadow: var(--premium-shadow-lg);
}

.brand-layout__panel {
    display: grid;
    align-content: end;
    min-height: 100%;
    gap: 1rem;
}

.brand-layout__panel span {
    color: rgba(255, 255, 255, .72);
    font-size: .72rem;
    font-weight: 800;
    letter-spacing: .14em;
    text-transform: uppercase;
}

.brand-layout__panel h3 {
    margin: 0;
    font-size: clamp(1.8rem, 3vw, 2.8rem);
    line-height: 1;
}

.brand-layout__panel p {
    margin: 0;
    max-width: 50ch;
    color: rgba(255, 255, 255, .72);
    line-height: 1.8;
}

.brand-layout__grid {
    display: grid;
    gap: .9rem;
}

@media (max-width: 900px) {
    .brand-layout {
        grid-template-columns: 1fr;
    }
}
</style>
