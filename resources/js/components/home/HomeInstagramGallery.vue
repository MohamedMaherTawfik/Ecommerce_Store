<template>
    <section ref="target" class="section-shell section-shell--soft" :class="{ 'is-visible': inView }">
        <SectionTitle eyebrow="Community" title="Instagram gallery" description="A more editorial view of how the brand feels in the wild." />

        <div class="instagram-grid">
            <a v-for="item in items" :key="item.id" href="#" class="instagram-card" @click.prevent>
                <img :src="item.image" :alt="item.label" loading="lazy" decoding="async" />
                <span>{{ item.label }}</span>
            </a>
        </div>
    </section>
</template>

<script setup>
import SectionTitle from "@/components/ui/SectionTitle.vue";
import { useIntersectionObserver } from "@/composables/useIntersectionObserver";

defineProps({
    items: { type: Array, default: () => [] },
});

const { target, inView } = useIntersectionObserver({ threshold: 0.12 });
</script>

<style scoped>
.instagram-grid {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: .85rem;
}

.instagram-card {
    position: relative;
    display: block;
    overflow: hidden;
    border-radius: 1.1rem;
    aspect-ratio: 1 / 1;
    color: #fff;
    text-decoration: none;
}

.instagram-card img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform .45s ease;
}

.instagram-card:hover img {
    transform: scale(1.04);
}

.instagram-card::after {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, transparent 55%, rgba(2, 6, 23, .75) 100%);
}

.instagram-card span {
    position: absolute;
    left: .8rem;
    bottom: .8rem;
    z-index: 1;
    font-size: .82rem;
    font-weight: 800;
    letter-spacing: .08em;
}

@media (max-width: 1100px) {
    .instagram-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

@media (max-width: 640px) {
    .instagram-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
</style>
