<template>
    <section ref="target" class="section-shell section-shell--soft" :class="{ 'is-visible': inView }">
        <SectionTitle eyebrow="Our promise" title="Why choose us" description="Small guarantees that add up to a noticeably better shopping experience." />

        <div class="promise-grid">
            <article v-for="item in items" :key="item.id" class="promise-card">
                <i :class="['bi', item.icon]"></i>
                <h3>{{ item.title }}</h3>
                <p>{{ item.text }}</p>
            </article>
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
.promise-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 1rem;
}

.promise-card {
    padding: 1.1rem;
    border-radius: 1.25rem;
    background: var(--premium-surface);
    border: 1px solid var(--premium-border);
    box-shadow: var(--premium-shadow-sm);
}

.promise-card i {
    display: grid;
    place-items: center;
    width: 48px;
    height: 48px;
    margin-bottom: .95rem;
    border-radius: 1rem;
    background: var(--premium-gold-soft);
    color: var(--premium-gold-ink);
}

.promise-card h3 {
    margin: 0 0 .35rem;
    font-size: 1rem;
}

.promise-card p {
    margin: 0;
    color: var(--premium-muted);
    line-height: 1.7;
    font-size: .92rem;
}

@media (max-width: 1000px) {
    .promise-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 620px) {
    .promise-grid {
        grid-template-columns: 1fr;
    }
}
</style>
