import { computed, onBeforeUnmount, ref, watch } from "vue";

export function useCarousel(items, options = {}) {
    const index = ref(options.startIndex || 0);
    const autoplay = options.autoplay ?? true;
    const interval = options.interval || 5000;
    let timer = null;

    const length = computed(() => (Array.isArray(items) ? items.length : items?.value?.length || 0));

    const clamp = (value) => {
        const total = length.value;
        if (!total) return 0;
        return ((value % total) + total) % total;
    };

    const goTo = (nextIndex) => {
        index.value = clamp(nextIndex);
    };

    const next = () => goTo(index.value + 1);
    const prev = () => goTo(index.value - 1);

    const stop = () => {
        if (timer) {
            clearInterval(timer);
            timer = null;
        }
    };

    const start = () => {
        stop();
        if (!autoplay || length.value <= 1) return;
        timer = setInterval(next, interval);
    };

    watch(length, () => {
        index.value = clamp(index.value);
        start();
    }, { immediate: true });

    onBeforeUnmount(stop);

    return {
        index,
        length,
        goTo,
        next,
        prev,
        start,
        stop,
    };
}
