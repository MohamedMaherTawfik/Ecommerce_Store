import { onBeforeUnmount, onMounted, ref } from "vue";

export function useIntersectionObserver(options = {}) {
    const target = ref(null);
    const inView = ref(false);
    let observer = null;

    onMounted(() => {
        if (!("IntersectionObserver" in window)) {
            inView.value = true;
            return;
        }

        observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    inView.value = true;
                    if (options.once !== false) {
                        observer?.unobserve(entry.target);
                    }
                }
            });
        }, {
            threshold: options.threshold ?? 0.2,
            rootMargin: options.rootMargin || "0px 0px -12% 0px",
        });

        if (target.value) {
            observer.observe(target.value);
        }
    });

    onBeforeUnmount(() => {
        observer?.disconnect();
    });

    return { target, inView };
}
