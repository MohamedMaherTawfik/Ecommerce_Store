<template>
    <section ref="target" class="section-shell" :class="{ 'is-visible': inView }">
        <SectionTitle eyebrow="Deals" :title="sale?.title || 'Private flash sale'" :description="sale?.description || ''">
            <template #actions>
                <div class="countdown-pill">
                    <i class="bi bi-clock"></i>
                    <span>Ends in</span>
                    <strong>{{ countdown.days }}d {{ countdown.hours }}h {{ countdown.minutes }}m</strong>
                </div>
            </template>
        </SectionTitle>

        <div v-if="loading" class="flash-grid">
            <SkeletonLoader v-for="n in 3" :key="n" height="260px" radius="1.35rem" />
        </div>

        <div v-else class="flash-grid">
            <article v-for="item in saleItems" :key="item.id" class="flash-card">
                <img
                    :src="resolveImageSrc(item.image)"
                    :alt="item.name"
                    loading="lazy"
                    decoding="async"
                    @error="handleImageError"
                />
                <div class="flash-card__body">
                    <h3>{{ item.name }}</h3>
                    <div class="flash-card__price">
                        <strong>{{ money(item.price) }}</strong>
                        <s>{{ money(item.originalPrice) }}</s>
                    </div>
                    <div class="flash-card__progress">
                        <span :style="{ width: `${item.sold}%` }"></span>
                    </div>
                    <small>{{ item.sold }}% reserved</small>
                </div>
            </article>
        </div>
    </section>
</template>

<script setup>
import { computed } from "vue";
import SectionTitle from "@/components/ui/SectionTitle.vue";
import SkeletonLoader from "@/components/ui/SkeletonLoader.vue";
import { useCountdown } from "@/composables/useCountdown";
import { useIntersectionObserver } from "@/composables/useIntersectionObserver";
import { imageUrl } from "@/utils/image";

const props = defineProps({
    sale: { type: Object, default: null },
    loading: { type: Boolean, default: false },
});

const saleItems = computed(() => props.sale?.items || []);
const { remaining } = useCountdown(computed(() => props.sale?.endsAt || Date.now()));
const countdown = remaining;
const { target, inView } = useIntersectionObserver({ threshold: 0.15 });

const money = (value) =>
    new Intl.NumberFormat("en-US", { style: "currency", currency: "USD" }).format(Number(value || 0));

const resolveImageSrc = (image) => imageUrl(image);

const handleImageError = (event) => {
    const img = event?.currentTarget;
    if (!img || img.dataset.fallbackApplied === "true") {
        return;
    }

    img.dataset.fallbackApplied = "true";
    img.src = imageUrl(null);
};
</script>

<style scoped>
.countdown-pill {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    padding: .6rem .95rem;
    border-radius: 999px;
    border: 1px solid var(--premium-border);
    background: var(--premium-surface);
    color: var(--premium-ink);
    box-shadow: var(--premium-shadow-sm);
}

.countdown-pill span {
    color: var(--premium-muted);
}

.flash-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1rem;
}

.flash-card {
    overflow: hidden;
    border-radius: 1.35rem;
    background: var(--premium-surface);
    border: 1px solid var(--premium-border);
    box-shadow: var(--premium-shadow-sm);
}

.flash-card img {
    width: 100%;
    aspect-ratio: 4 / 3;
    object-fit: cover;
}

.flash-card__body {
    display: grid;
    gap: .65rem;
    padding: 1rem;
}

.flash-card__body h3 {
    margin: 0;
    font-size: 1.02rem;
}

.flash-card__price {
    display: flex;
    align-items: baseline;
    gap: .7rem;
}

.flash-card__price strong {
    font-size: 1.1rem;
}

.flash-card__price s,
.flash-card small {
    color: var(--premium-muted);
}

.flash-card__progress {
    height: 6px;
    border-radius: 999px;
    background: var(--premium-surface-soft);
    overflow: hidden;
}

.flash-card__progress span {
    display: block;
    height: 100%;
    border-radius: 999px;
    background: linear-gradient(90deg, #8f6a13 0%, #d4af37 100%);
}

@media (max-width: 1000px) {
    .flash-grid {
        grid-template-columns: 1fr;
    }
}
</style>
