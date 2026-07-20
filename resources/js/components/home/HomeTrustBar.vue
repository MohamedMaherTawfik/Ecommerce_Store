<template>
    <section ref="target" class="trust-strip" :class="{ 'is-visible': inView }">
        <div class="trust-strip__grid">
            <article v-for="item in items" :key="item.id" class="trust-strip__item">
                <i :class="['bi', item.icon]"></i>
                <div>
                    <strong>{{ item.title }}</strong>
                    <p>{{ item.text }}</p>
                </div>
            </article>
        </div>
    </section>
</template>

<script setup>
import { useIntersectionObserver } from "@/composables/useIntersectionObserver";

defineProps({
    items: { type: Array, default: () => [] },
});

const { target, inView } = useIntersectionObserver({ threshold: 0.15 });
</script>

<style scoped>
.trust-strip__grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 1rem;
}

.trust-strip__item {
    display: flex;
    gap: .9rem;
    padding: 1rem 1.1rem;
    border-radius: 1.2rem;
    background: var(--premium-surface);
    border: 1px solid var(--premium-border);
    box-shadow: var(--premium-shadow-sm);
}

.trust-strip__item i {
    display: grid;
    place-items: center;
    width: 42px;
    height: 42px;
    border-radius: 999px;
    background: var(--premium-gold-soft);
    color: var(--premium-gold-ink);
}

.trust-strip__item strong {
    display: block;
    margin-bottom: .2rem;
    color: var(--premium-ink);
}

.trust-strip__item p {
    margin: 0;
    color: var(--premium-muted);
    font-size: .9rem;
    line-height: 1.6;
}

@media (max-width: 1100px) {
    .trust-strip__grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 620px) {
    .trust-strip__grid {
        grid-template-columns: 1fr;
    }
}
</style>
