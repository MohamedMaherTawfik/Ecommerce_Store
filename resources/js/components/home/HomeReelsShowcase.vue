<template>
    <section
        ref="target"
        class="reels-showcase"
        :class="{ 'is-visible': isSectionVisible }"
        aria-labelledby="reels-showcase-title"
    >
        <header class="reels-showcase__header">
            <Button
                as="button"
                variant="secondary"
                class="reels-showcase__view-all"
                type="button"
                @click="emit('view-all')"
                :aria-label="`View all reels for ${brandName}`"
            >
                عرض الكل
            </Button>

            <div class="reels-showcase__heading">
                <div class="reels-showcase__eyebrow">
                    <i class="bi bi-facebook" aria-hidden="true"></i>
                    <span>Facebook</span>
                </div>
                <h2 id="reels-showcase-title">{{ brandName }}</h2>
                <p>{{ subtitle }}</p>
            </div>
        </header>

        <div v-if="loading" class="reels-showcase__state">
            <div class="reels-skeleton__header">
                <div class="reels-skeleton__pill"></div>
                <div class="reels-skeleton__line reels-skeleton__line--lg"></div>
                <div class="reels-skeleton__line reels-skeleton__line--md"></div>
            </div>
            <div class="reels-skeleton__track">
                <article v-for="n in 7" :key="n" class="reels-skeleton__card">
                    <div class="reels-skeleton__media"></div>
                    <div class="reels-skeleton__footer">
                        <div class="reels-skeleton__line reels-skeleton__line--sm"></div>
                        <div class="reels-skeleton__line reels-skeleton__line--xs"></div>
                    </div>
                </article>
            </div>
        </div>

        <div v-else-if="hasError" class="reels-empty reels-empty--error">
            <div class="reels-empty__icon">
                <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
            </div>
            <h3>تعذر تحميل الفيديوهات حاليا</h3>
            <p>حاول مرة أخرى بعد لحظات أو انتقل إلى صفحة الفيديوهات الكاملة.</p>
            <Button variant="gold" type="button" @click="emit('view-all')">
                عرض الكل
            </Button>
        </div>

        <div v-else-if="normalizedVideos.length === 0" class="reels-empty">
            <div class="reels-empty__icon">
                <i class="bi bi-camera-video" aria-hidden="true"></i>
            </div>
            <h3>لا توجد فيديوهات حاليا</h3>
            <p>سيظهر هنا أحدث الريلز والعروض بمجرد توفرها من لوحة التحكم أو الـ API.</p>
            <Button variant="gold" type="button" @click="emit('view-all')">
                عرض الكل
            </Button>
        </div>

        <div v-else class="reels-showcase__content">
            <Swiper
                class="reels-swiper"
                :modules="swiperModules"
                :slides-per-view="slidesPerView"
                :space-between="12"
                :breakpoints="breakpoints"
                :loop="normalizedVideos.length > 1"
                :navigation="navigationOptions"
                :mousewheel="mousewheelOptions"
                :touch-start-prevent-default="true"
                :touch-move-stop-propagation="true"
                :keyboard="{ enabled: true, onlyInViewport: true }"
                :a11y="{ enabled: true }"
                :grab-cursor="true"
                :watch-slides-progress="true"
                :slides-per-group="1"
                @touchstart.stop
                @touchmove.stop
                @pointerdown.stop
                @pointermove.stop
                @wheel.stop
                @dragstart.prevent
            >
                <SwiperSlide
                    v-for="video in normalizedVideos"
                    :key="video.id"
                    class="reels-swiper__slide"
                >
                    <article
                        class="reel-card"
                        :class="{ 'is-liked': isLiked(video.id), 'is-loaded': isSectionVisible }"
                        role="button"
                        tabindex="0"
                        :aria-label="`Open reel: ${video.title}`"
                        @click="openVideo(video)"
                        @keydown.enter.prevent="openVideo(video)"
                        @keydown.space.prevent="openVideo(video)"
                    >
                        <div class="reel-card__media">
                            <img
                                v-if="isSectionVisible"
                                class="reel-card__thumb"
                                :src="video.thumbnail"
                                :alt="video.title"
                                loading="lazy"
                                decoding="async"
                            />
                            <div v-else class="reel-card__thumb reel-card__thumb--placeholder" aria-hidden="true"></div>
                            <div class="reel-card__overlay"></div>
                        </div>

                        <div class="reel-card__content">
                            <div class="reel-card__top">
                                <span class="reel-card__duration">{{ formatDuration(video.duration) }}</span>

                                <button
                                    class="reel-card__like"
                                    type="button"
                                    :aria-label="isLiked(video.id) ? `Unlike ${video.title}` : `Like ${video.title}`"
                                    :aria-pressed="isLiked(video.id)"
                                    @click.stop="toggleLike(video)"
                                >
                                    <i class="bi" :class="isLiked(video.id) ? 'bi-heart-fill' : 'bi-heart'"></i>
                                </button>
                            </div>

                            <button
                                class="reel-card__play"
                                type="button"
                                :aria-label="`Play ${video.title}`"
                                @click.stop="openVideo(video)"
                            >
                                <i class="bi bi-play-fill" aria-hidden="true"></i>
                            </button>

                            <div class="reel-card__footer">
                                <span class="reel-card__views">{{ formatViews(video.views) }} مشاهدة</span>
                                <h3 class="reel-card__title">{{ video.title }}</h3>
                            </div>
                        </div>
                    </article>
                </SwiperSlide>
            </Swiper>

            <button
                class="reels-nav reels-nav--prev"
                :class="prevNavClass"
                type="button"
                :aria-label="`Previous reels for ${brandName}`"
            >
                <i class="bi bi-chevron-left" aria-hidden="true"></i>
            </button>
            <button
                class="reels-nav reels-nav--next"
                :class="nextNavClass"
                type="button"
                :aria-label="`Next reels for ${brandName}`"
            >
                <i class="bi bi-chevron-right" aria-hidden="true"></i>
            </button>
        </div>

        <button class="reels-banner" type="button" @click="emit('follow')" :aria-label="`Follow ${followTitle} on Facebook`">
            <div class="reels-banner__actions">
                <span class="reels-banner__chip">Facebook</span>
                <span class="reels-banner__chip reels-banner__chip--light">Follow</span>
            </div>

            <div class="reels-banner__body">
                <h3>{{ followTitle }}</h3>
                <p>{{ followSubtitle }}</p>
            </div>

            <div class="reels-banner__icon" aria-hidden="true">
                <i class="bi bi-facebook"></i>
            </div>
        </button>
    </section>
</template>

<script setup>
import { computed, ref, watch } from "vue";
import { Swiper, SwiperSlide } from "swiper/vue";
import { A11y, Keyboard, Mousewheel, Navigation } from "swiper/modules";
import "swiper/css";
import "swiper/css/navigation";
import Button from "@/components/ui/Button.vue";
import { useIntersectionObserver } from "@/composables/useIntersectionObserver";

const props = defineProps({
    videos: { type: null, default: () => [] },
    loading: { type: Boolean, default: false },
    brandName: { type: String, default: "Allosh Chocolates" },
    subtitle: { type: String, default: "تابع أحدث العروض والمنتجات" },
    followTitle: { type: String, default: "Ecommerce Store" },
    followSubtitle: {
        type: String,
        default: "اكتشف أحدث المنتجات والعروض الحصرية",
    },
});

const emit = defineEmits(["open-video", "like", "follow", "view-all"]);

const brandName = computed(() => props.brandName || "Allosh Chocolates");
const subtitle = computed(() => props.subtitle || "تابع أحدث العروض والمنتجات");
const followTitle = computed(() => props.followTitle || "Ecommerce Store");
const followSubtitle = computed(() => props.followSubtitle || "اكتشف أحدث المنتجات والعروض الحصرية");

const likedState = ref({});

const prevNavClass = `reels-nav-prev-${Math.random().toString(36).slice(2, 9)}`;
const nextNavClass = `reels-nav-next-${Math.random().toString(36).slice(2, 9)}`;

const swiperModules = [Navigation, Mousewheel, Keyboard, A11y];
const slidesPerView = 2;

const breakpoints = {
    640: {
        slidesPerView: 5,
        spaceBetween: 14,
    },
    1024: {
        slidesPerView: 7,
        spaceBetween: 14,
    },
};

const navigationOptions = {
    prevEl: `.${prevNavClass}`,
    nextEl: `.${nextNavClass}`,
};

const mousewheelOptions = {
    enabled: true,
    forceToAxis: true,
    sensitivity: 0.8,
    releaseOnEdges: true,
};

const normalizedVideos = computed(() => (Array.isArray(props.videos) ? props.videos : []));
const hasError = computed(() => !props.loading && !Array.isArray(props.videos));

const { target, inView } = useIntersectionObserver({ threshold: 0.12 });
const isSectionVisible = computed(() => inView.value || props.loading);

watch(
    normalizedVideos,
    (nextVideos) => {
        const nextState = {};
        nextVideos.forEach((video) => {
            nextState[video.id] = Boolean(video.liked);
        });
        likedState.value = nextState;
    },
    { immediate: true },
);

const openVideo = (video) => {
    emit("open-video", video);
};

const toggleLike = (video) => {
    const nextLiked = !isLiked(video.id);
    likedState.value = {
        ...likedState.value,
        [video.id]: nextLiked,
    };

    emit("like", {
        ...video,
        liked: nextLiked,
    });
};

const isLiked = (videoId) => Boolean(likedState.value?.[videoId]);

const formatViews = (value) => {
    const number = Number(value || 0);
    return new Intl.NumberFormat("en", {
        notation: "compact",
        maximumFractionDigits: 1,
    })
        .format(number)
        .toLowerCase();
};

const formatDuration = (duration) => {
    if (duration === null || duration === undefined || duration === "") {
        return "0:00";
    }

    if (typeof duration === "number" && Number.isFinite(duration)) {
        const minutes = Math.floor(duration / 60);
        const seconds = Math.abs(Math.round(duration % 60));
        return `${minutes}:${String(seconds).padStart(2, "0")}`;
    }

    const raw = String(duration).trim();
    const segments = raw.split(":").map((segment) => Number(segment));

    if (segments.some((segment) => Number.isNaN(segment))) {
        return raw;
    }

    if (segments.length === 2) {
        const [minutes, seconds] = segments;
        return `${minutes}:${String(seconds).padStart(2, "0")}`;
    }

    if (segments.length === 3) {
        const [hours, minutes, seconds] = segments;
        if (hours > 0) {
            return `${hours}:${String(minutes).padStart(2, "0")}:${String(seconds).padStart(2, "0")}`;
        }
        return `${minutes}:${String(seconds).padStart(2, "0")}`;
    }

    return raw;
};

defineExpose({
    toggleLike,
    formatViews,
    formatDuration,
});
</script>

<style scoped>
.reels-showcase {
    position: relative;
    width: 100%;
    max-width: 100%;
    display: grid;
    gap: 1rem;
    overflow: hidden;
    overscroll-behavior-x: contain;
    overscroll-behavior-y: auto;
    touch-action: pan-y;
    opacity: 0;
    transform: translateY(16px);
    transition: opacity .65s ease, transform .65s ease;
}

.reels-showcase.is-visible {
    opacity: 1;
    transform: translateY(0);
}

.reels-showcase__header {
    display: grid;
    grid-template-columns: auto 1fr;
    align-items: start;
    gap: 1rem;
    padding: 0 0.15rem;
}

.reels-showcase__view-all {
    order: 1;
    align-self: center;
    min-width: 124px;
}

.reels-showcase__heading {
    order: 2;
    text-align: right;
    display: grid;
    justify-items: end;
    gap: 0.25rem;
}

.reels-showcase__eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    color: var(--info);
    font-weight: 700;
    font-size: 0.92rem;
}

.reels-showcase__eyebrow i {
    font-size: 1.1rem;
}

.reels-showcase__heading h2 {
    margin: 0;
    font-size: clamp(1.7rem, 2.9vw, 2.8rem);
    line-height: 1;
    letter-spacing: -0.04em;
    color: var(--premium-ink);
}

.reels-showcase__heading p {
    margin: 0;
    color: var(--premium-muted);
    font-size: 0.95rem;
}

.reels-showcase__content {
    position: relative;
    width: 100%;
    max-width: 100%;
    overflow: hidden;
    padding-inline: 0.1rem;
}

.reels-swiper {
    width: 100%;
    max-width: 100%;
    overflow: hidden;
    padding: 0.35rem 0.1rem 0.85rem;
    touch-action: pan-y;
    overscroll-behavior: contain;
}

.reels-swiper__slide {
    width: auto;
    min-width: 0;
}

:deep(.swiper) {
    width: 100%;
    max-width: 100%;
    overflow: hidden;
}

:deep(.swiper-wrapper) {
    touch-action: pan-y;
}

:deep(.swiper-slide) {
    min-width: 0;
}

.reel-card {
    position: relative;
    width: 100%;
    max-width: 152px;
    height: 240px;
    border-radius: 18px;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.12);
    background: #0f172a;
    box-shadow: 0 16px 35px rgba(15, 23, 42, 0.18);
    cursor: pointer;
    transform: translateZ(0);
    transition: transform .28s ease, box-shadow .28s ease, border-color .28s ease;
}

.reel-card:hover,
.reel-card:focus-visible {
    transform: translateY(-4px) scale(1.01);
    box-shadow: 0 26px 45px rgba(15, 23, 42, 0.28);
    border-color: rgba(255, 255, 255, 0.24);
    outline: none;
}

.reel-card__media {
    position: absolute;
    inset: 0;
}

.reel-card__thumb {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    transform: scale(1);
    transition: transform .55s ease, filter .55s ease, opacity .35s ease;
}

.reel-card:hover .reel-card__thumb,
.reel-card:focus-visible .reel-card__thumb {
    transform: scale(1.08);
}

.reel-card__thumb--placeholder {
    background:
        linear-gradient(145deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.02)),
        radial-gradient(circle at 20% 20%, rgba(255, 255, 255, 0.18), transparent 42%),
        linear-gradient(180deg, #1f2937, #0f172a);
}

.reel-card__overlay {
    position: absolute;
    inset: 0;
    background:
        linear-gradient(180deg, rgba(2, 6, 23, 0.08) 0%, rgba(2, 6, 23, 0.16) 35%, rgba(2, 6, 23, 0.86) 100%),
        linear-gradient(180deg, transparent 45%, rgba(0, 0, 0, 0.2) 100%);
}

.reel-card__content {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    width: 100%;
    height: 100%;
    padding: 10px 10px 11px;
    color: #fff;
}

.reel-card__top {
    display: flex;
    align-items: start;
    justify-content: space-between;
    gap: 0.5rem;
}

.reel-card__duration {
    display: inline-flex;
    align-items: center;
    min-height: 28px;
    padding: 0 0.55rem;
    border-radius: 999px;
    background: rgba(15, 23, 42, 0.68);
    border: 1px solid rgba(255, 255, 255, 0.16);
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.03em;
    backdrop-filter: blur(10px);
}

.reel-card__like {
    display: grid;
    place-items: center;
    width: 34px;
    height: 34px;
    border: 0;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.94);
    color: #111827;
    box-shadow: 0 12px 24px rgba(15, 23, 42, 0.18);
    transition: transform .22s ease, color .22s ease, box-shadow .22s ease;
}

.reel-card__like:hover,
.reel-card__like:focus-visible {
    transform: scale(1.06);
    box-shadow: 0 16px 28px rgba(15, 23, 42, 0.24);
    outline: none;
}

.reel-card.is-liked .reel-card__like {
    color: #ef4444;
    animation: heart-pop .28s ease;
}

.reel-card__play {
    position: absolute;
    inset: 0;
    display: grid;
    place-items: center;
    margin: auto;
    width: 64px;
    height: 64px;
    border: 0;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.32);
    color: #fff;
    backdrop-filter: blur(12px);
    box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.18), 0 20px 40px rgba(59, 130, 246, 0.18);
    transition: transform .25s ease, background .25s ease, box-shadow .25s ease;
}

.reel-card:hover .reel-card__play,
.reel-card:focus-visible .reel-card__play {
    transform: scale(1.08);
    background: rgba(255, 255, 255, 0.38);
    box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.28), 0 0 32px rgba(96, 165, 250, 0.28);
}

.reel-card__play i {
    font-size: 2rem;
    line-height: 1;
    margin-left: 2px;
}

.reel-card__footer {
    display: grid;
    gap: 0.32rem;
}

.reel-card__views {
    font-size: 0.75rem;
    font-weight: 700;
    color: rgba(255, 255, 255, 0.82);
}

.reel-card__title {
    margin: 0;
    display: -webkit-box;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
    overflow: hidden;
    text-align: center;
    font-size: 0.94rem;
    line-height: 1.35;
    letter-spacing: -0.02em;
}

.reels-nav {
    position: absolute;
    top: 50%;
    z-index: 5;
    display: grid;
    place-items: center;
    width: 42px;
    height: 42px;
    border: 0;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.96);
    color: #0f172a;
    box-shadow: 0 18px 34px rgba(15, 23, 42, 0.16);
    transform: translateY(-50%);
    transition: transform .2s ease, box-shadow .2s ease, opacity .2s ease;
}

.reels-nav:hover,
.reels-nav:focus-visible {
    transform: translateY(-50%) scale(1.06);
    box-shadow: 0 22px 38px rgba(15, 23, 42, 0.22);
    outline: none;
}

.reels-nav--prev {
    left: -6px;
}

.reels-nav--next {
    right: -6px;
}

.reels-empty {
    display: grid;
    justify-items: center;
    gap: 0.9rem;
    padding: 2.3rem 1.25rem;
    border-radius: 1.5rem;
    border: 1px solid var(--premium-border);
    background: var(--premium-surface);
    box-shadow: var(--premium-shadow-sm);
    text-align: center;
}

.reels-empty__icon {
    display: grid;
    place-items: center;
    width: 70px;
    height: 70px;
    border-radius: 999px;
    background: var(--premium-gold-soft);
    color: var(--premium-gold-ink);
    font-size: 1.45rem;
}

.reels-empty h3 {
    margin: 0;
    font-size: 1.2rem;
    color: var(--premium-ink);
}

.reels-empty p {
    margin: 0;
    color: var(--premium-muted);
    max-width: 50ch;
    line-height: 1.7;
}

.reels-empty--error .reels-empty__icon {
    background: rgba(239, 68, 68, 0.1);
    color: #dc2626;
}

.reels-banner {
    display: grid;
    grid-template-columns: auto 1fr auto;
    align-items: center;
    gap: 1rem;
    width: 100%;
    padding: 1rem 1.15rem;
    border: 0;
    border-radius: 1.6rem;
    background:
        radial-gradient(circle at top right, rgba(255, 255, 255, 0.18), transparent 24%),
        linear-gradient(135deg, var(--primary) 0%, var(--accent) 48%, color-mix(in srgb, var(--primary) 60%, var(--accent)) 100%);
    color: #fff;
    box-shadow: 0 18px 42px color-mix(in srgb, var(--primary) 26%, transparent);
    text-align: inherit;
    transition: transform .24s ease, box-shadow .24s ease, filter .24s ease;
}

.reels-banner:hover,
.reels-banner:focus-visible {
    transform: translateY(-2px);
    box-shadow: 0 22px 52px color-mix(in srgb, var(--primary) 34%, transparent);
    filter: saturate(1.02);
    outline: none;
}

.reels-banner__actions {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    flex-wrap: wrap;
}

.reels-banner__chip {
    display: inline-flex;
    align-items: center;
    min-height: 34px;
    padding: 0 0.75rem;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.14);
    border: 1px solid rgba(255, 255, 255, 0.18);
    font-size: 0.78rem;
    font-weight: 800;
}

.reels-banner__chip--light {
    background: rgba(255, 255, 255, 0.92);
    color: var(--primary);
}

.reels-banner__body {
    display: grid;
    justify-items: center;
    gap: 0.2rem;
}

.reels-banner__body h3 {
    margin: 0;
    font-size: clamp(1rem, 2vw, 1.45rem);
    line-height: 1.1;
}

.reels-banner__body p {
    margin: 0;
    color: rgba(255, 255, 255, 0.82);
    font-size: 0.92rem;
}

.reels-banner__icon {
    display: grid;
    place-items: center;
    width: 52px;
    height: 52px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.18);
    border: 1px solid rgba(255, 255, 255, 0.18);
    font-size: 1.45rem;
}

.reels-showcase__state {
    display: grid;
    gap: 1rem;
}

.reels-skeleton__header {
    display: grid;
    gap: 0.65rem;
    justify-items: end;
}

.reels-skeleton__pill,
.reels-skeleton__line,
.reels-skeleton__media {
    position: relative;
    overflow: hidden;
    background: linear-gradient(90deg, rgba(226, 232, 240, 0.8) 0%, rgba(241, 245, 249, 1) 45%, rgba(226, 232, 240, 0.8) 100%);
    background-size: 220% 100%;
    animation: shimmer 1.35s linear infinite;
}

.reels-skeleton__pill {
    width: 118px;
    height: 42px;
    border-radius: 999px;
}

.reels-skeleton__line {
    height: 14px;
    border-radius: 999px;
}

.reels-skeleton__line--lg {
    width: min(320px, 70%);
}

.reels-skeleton__line--md {
    width: min(240px, 56%);
}

.reels-skeleton__line--sm {
    width: 72%;
}

.reels-skeleton__line--xs {
    width: 44%;
}

.reels-skeleton__track {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: 12px;
}

.reels-skeleton__card {
    display: grid;
    gap: 0.55rem;
}

.reels-skeleton__media {
    height: 240px;
    border-radius: 18px;
}

.reels-skeleton__footer {
    display: grid;
    gap: 0.35rem;
}

@keyframes shimmer {
    0% {
        background-position: 200% 0;
    }

    100% {
        background-position: -200% 0;
    }
}

@keyframes heart-pop {
    0% {
        transform: scale(1);
    }

    45% {
        transform: scale(1.18);
    }

    100% {
        transform: scale(1);
    }
}

@media (max-width: 1024px) {
    .reels-skeleton__track {
        grid-template-columns: repeat(5, minmax(0, 1fr));
    }
}

@media (max-width: 640px) {
    .reels-showcase__header {
        grid-template-columns: 1fr;
    }

    .reels-showcase__view-all {
        order: 2;
        justify-self: start;
    }

    .reels-showcase__heading {
        order: 1;
        justify-items: start;
        text-align: left;
    }

    .reels-banner {
        grid-template-columns: 1fr;
        justify-items: center;
        text-align: center;
    }

    .reels-skeleton__track {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .reels-nav {
        display: none;
    }
}
</style>
