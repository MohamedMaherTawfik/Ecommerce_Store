<template>
    <section ref="target" class="hero-section" :class="{ 'is-visible': inView }">
        <div v-if="loading" class="hero-section__skeleton">
            <SkeletonLoader height="720px" radius="2rem" />
        </div>

        <div v-else-if="slides.length" class="hero-carousel">
            <article
                v-for="(slide, slideIndex) in slides"
                :key="slide.id"
                class="hero-slide"
                :class="{ 'is-active': slideIndex === activeIndex }"
            >
                <img
                    :src="imageUrl(slide.image, 1800)"
                    :alt="slide.title"
                    class="hero-slide__image"
                    :loading="slideIndex === 0 ? 'eager' : 'lazy'"
                    decoding="async"
                />
                <div class="hero-slide__overlay"></div>
                <div class="hero-slide__content">
                    <Badge variant="gold">{{ slide.tag }}</Badge>
                    <h1>{{ slide.title }}</h1>
                    <p>{{ slide.description }}</p>
                    <div class="hero-slide__actions">
                        <Button as="RouterLink" variant="gold" :to="localizedLink(slide.ctaLink)">{{ slide.ctaLabel }}</Button>
                        <Button as="RouterLink" variant="ghost" :to="`/${lang}/products`">View collection</Button>
                    </div>
                    <div class="hero-slide__meta">
                        <div>
                            <span>Starting from</span>
                            <strong>{{ formatMoney(slide.price) }}</strong>
                        </div>
                        <div>
                            <span>Season note</span>
                            <strong>{{ slide.accent }}</strong>
                        </div>
                    </div>
                </div>
            </article>

            <div class="hero-section__controls">
                <button class="hero-section__nav" type="button" @click="previous" aria-label="Previous slide">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <div class="hero-section__dots">
                    <button
                        v-for="(_, index) in slides"
                        :key="index"
                        type="button"
                        class="hero-section__dot"
                        :class="{ 'is-active': index === activeIndex }"
                        @click="goTo(index)"
                        :aria-label="`Slide ${index + 1}`"
                    />
                </div>
                <button class="hero-section__nav" type="button" @click="next" aria-label="Next slide">
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>

            <div class="hero-section__stats">
                <div v-for="stat in stats" :key="stat.id" class="hero-section__stat">
                    <strong>{{ stat.value }}</strong>
                    <span>{{ stat.label }}</span>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { computed } from "vue";
import { imageUrl } from "@/utils/image";
import Badge from "@/components/ui/Badge.vue";
import Button from "@/components/ui/Button.vue";
import SkeletonLoader from "@/components/ui/SkeletonLoader.vue";
import { useCarousel } from "@/composables/useCarousel";
import { useIntersectionObserver } from "@/composables/useIntersectionObserver";

const props = defineProps({
    slides: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
    lang: { type: String, default: "en" },
    stats: { type: Array, default: () => [] },
});

const { target, inView } = useIntersectionObserver({ threshold: 0.1 });
const carousel = useCarousel(computed(() => props.slides), { interval: 5400 });
const { index: activeIndex, next, prev: previous, goTo } = carousel;

const localizedLink = (path) => String(path || "").replace("{lang}", props.lang);

const formatMoney = (value) =>
    new Intl.NumberFormat("en-US", { style: "currency", currency: "USD" }).format(Number(value || 0));
</script>

<style scoped>
.hero-section {
    position: relative;
}

.hero-carousel {
    position: relative;
    overflow: hidden;
    min-height: 720px;
    border-radius: 2rem;
    border: 1px solid var(--premium-border);
    background: var(--premium-surface);
    box-shadow: var(--premium-shadow-lg);
}

.hero-slide {
    position: absolute;
    inset: 0;
    opacity: 0;
    transform: scale(1.015);
    transition: opacity .55s ease, transform .8s ease;
}

.hero-slide.is-active {
    opacity: 1;
    transform: scale(1);
    z-index: 1;
}

.hero-slide__image {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.hero-slide__overlay {
    position: absolute;
    inset: 0;
    background:
        linear-gradient(90deg, rgba(2, 6, 23, .82) 0%, rgba(2, 6, 23, .44) 45%, rgba(2, 6, 23, .12) 100%),
        linear-gradient(180deg, rgba(2, 6, 23, .2) 0%, rgba(2, 6, 23, .78) 100%);
}

.hero-slide__content {
    position: relative;
    z-index: 1;
    display: grid;
    align-content: end;
    gap: 1rem;
    min-height: 720px;
    width: min(720px, 100%);
    padding: clamp(1.5rem, 4vw, 4rem);
    color: #fff;
}

.hero-slide__content h1 {
    margin: 0;
    font-size: clamp(2.6rem, 6vw, 5.8rem);
    line-height: .94;
    letter-spacing: -.06em;
}

.hero-slide__content p {
    margin: 0;
    max-width: 60ch;
    color: rgba(255, 255, 255, .78);
    font-size: 1.04rem;
    line-height: 1.75;
}

.hero-slide__actions {
    display: flex;
    gap: .85rem;
    flex-wrap: wrap;
    margin-top: .35rem;
}

.hero-slide__meta {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    margin-top: 1rem;
}

.hero-slide__meta > div {
    display: grid;
    gap: .2rem;
    min-width: 150px;
    padding: .85rem 1rem;
    border-radius: 1rem;
    background: rgba(255, 255, 255, .08);
    border: 1px solid rgba(255, 255, 255, .12);
    backdrop-filter: blur(10px);
}

.hero-slide__meta span {
    color: rgba(255, 255, 255, .68);
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .12em;
    text-transform: uppercase;
}

.hero-slide__meta strong {
    font-size: .95rem;
}

.hero-section__controls {
    position: absolute;
    right: 1.3rem;
    bottom: 6.6rem;
    z-index: 2;
    display: flex;
    align-items: center;
    gap: .7rem;
    padding: .6rem;
    border-radius: 999px;
    background: rgba(255, 255, 255, .08);
    border: 1px solid rgba(255, 255, 255, .12);
    backdrop-filter: blur(10px);
}

.hero-section__nav {
    width: 42px;
    height: 42px;
    border: 0;
    border-radius: 999px;
    background: rgba(255, 255, 255, .9);
    color: var(--premium-ink);
}

.hero-section__dots {
    display: flex;
    gap: .4rem;
}

.hero-section__dot {
    width: 9px;
    height: 9px;
    border: 0;
    border-radius: 999px;
    background: rgba(255, 255, 255, .35);
}

.hero-section__dot.is-active {
    width: 28px;
    background: var(--premium-gold);
}

.hero-section__stats {
    position: relative;
    z-index: 2;
    margin-top: -2rem;
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1rem;
}

.hero-section__stat {
    padding: 1rem 1.1rem;
    border-radius: 1.1rem;
    background: var(--premium-surface);
    border: 1px solid var(--premium-border);
    box-shadow: var(--premium-shadow-sm);
}

.hero-section__stat strong {
    display: block;
    color: var(--premium-ink);
    font-size: 1.2rem;
}

.hero-section__stat span {
    color: var(--premium-muted);
    font-size: .86rem;
}

.hero-section__skeleton {
    border-radius: 2rem;
    overflow: hidden;
}

@media (max-width: 900px) {
    .hero-carousel,
    .hero-slide__content {
        min-height: 640px;
    }

    .hero-section__stats {
        grid-template-columns: 1fr;
        margin-top: 1rem;
    }

    .hero-section__controls {
        left: 50%;
        right: auto;
        bottom: 1rem;
        transform: translateX(-50%);
    }
}

@media (max-width: 640px) {
    .hero-carousel,
    .hero-slide__content {
        min-height: 580px;
    }

    .hero-slide__content h1 {
        font-size: clamp(2.2rem, 13vw, 4rem);
    }
}
</style>
