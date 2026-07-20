import { computed, onBeforeUnmount, ref } from "vue";

const toTarget = (value) => (value instanceof Date ? value : new Date(value));

export function useCountdown(target) {
    const now = ref(Date.now());
    let timer = null;

    const tick = () => {
        now.value = Date.now();
    };

    timer = setInterval(tick, 1000);

    onBeforeUnmount(() => {
        if (timer) clearInterval(timer);
    });

    const remaining = computed(() => {
        const targetDate = toTarget(target?.value || target);
        const diff = Math.max(0, targetDate.getTime() - now.value);
        const totalSeconds = Math.floor(diff / 1000);
        const days = Math.floor(totalSeconds / 86400);
        const hours = Math.floor((totalSeconds % 86400) / 3600);
        const minutes = Math.floor((totalSeconds % 3600) / 60);
        const seconds = totalSeconds % 60;

        return {
            days,
            hours,
            minutes,
            seconds,
            totalSeconds,
            expired: diff <= 0,
        };
    });

    return { remaining };
}
