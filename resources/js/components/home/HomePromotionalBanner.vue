<template>
    <section ref="target" class="promo-banner" :class="{ 'is-visible': inView }">
        <div class="promo-banner__copy">
            <Badge variant="gold">{{ banner?.eyebrow || 'Signature selection' }}</Badge>
            <h2>{{ banner?.title }}</h2>
            <p>{{ banner?.description }}</p>
            <div class="promo-banner__actions">
                <Button as="RouterLink" variant="gold" :to="localizedLink(banner?.ctaLink)"> {{ banner?.ctaLabel || 'Shop now' }} </Button>
                <span>{{ banner?.accentNote }}</span>
            </div>
        </div>
        <div class="promo-banner__visual">
            <div></div>
            <div></div>
            <div></div>
        </div>
    </section>
</template>

<script setup>
import Badge from "@/components/ui/Badge.vue";
import Button from "@/components/ui/Button.vue";
import { useIntersectionObserver } from "@/composables/useIntersectionObserver";

const props = defineProps({
    banner: { type: Object, default: null },
    lang: { type: String, default: "en" },
});

const { target, inView } = useIntersectionObserver({ threshold: 0.15 });

const localizedLink = (path) => String(path || "").replace("{lang}", props.lang);
</script>

<style scoped>
.promo-banner {
    display: grid;
    grid-template-columns: 1.2fr .8fr;
    gap: 1rem;
    padding: clamp(1.5rem, 3vw, 3rem);
    border-radius: 2rem;
    background:
        radial-gradient(circle at top right, rgba(212, 175, 55, .16), transparent 38%),
        linear-gradient(135deg, #0f172a 0%, #111827 55%, #0b0f19 100%);
    border: 1px solid rgba(255, 255, 255, .08);
    box-shadow: 0 28px 54px rgba(2, 6, 23, .28);
    color: #fff;
}

.promo-banner__copy {
    display: grid;
    align-content: center;
    gap: 1rem;
}

.promo-banner__copy h2 {
    margin: 0;
    font-size: clamp(2rem, 4vw, 3.8rem);
    line-height: .98;
    letter-spacing: -.05em;
}

.promo-banner__copy p {
    margin: 0;
    max-width: 60ch;
    color: rgba(255, 255, 255, .72);
    line-height: 1.8;
}

.promo-banner__actions {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.promo-banner__actions span {
    color: rgba(255, 255, 255, .62);
    font-size: .92rem;
}

.promo-banner__visual {
    position: relative;
    min-height: 300px;
}

.promo-banner__visual div {
    position: absolute;
    border-radius: 50%;
    background: rgba(212, 175, 55, .12);
}

.promo-banner__visual div:nth-child(1) {
    width: 240px;
    height: 240px;
    top: 1rem;
    right: 2rem;
}

.promo-banner__visual div:nth-child(2) {
    width: 150px;
    height: 150px;
    bottom: 1rem;
    right: 0;
    background: rgba(255, 255, 255, .06);
}

.promo-banner__visual div:nth-child(3) {
    width: 86px;
    height: 86px;
    top: 3rem;
    right: 6rem;
}

@media (max-width: 900px) {
    .promo-banner {
        grid-template-columns: 1fr;
    }

    .promo-banner__visual {
        display: none;
    }
}
</style>
