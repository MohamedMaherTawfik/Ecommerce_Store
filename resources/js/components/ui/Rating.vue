<template>
    <div class="ui-rating" :aria-label="`${value} out of 5`">
        <i
            v-for="n in 5"
            :key="n"
            class="bi"
            :class="n <= filled ? 'bi-star-fill' : n - value < 1 ? 'bi-star-half' : 'bi-star'"
        ></i>
        <span v-if="showValue" class="ui-rating__value">{{ normalized.toFixed(1) }}</span>
    </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
    value: { type: Number, default: 0 },
    showValue: { type: Boolean, default: true },
});

const normalized = computed(() => Number(props.value || 0));
const filled = computed(() => Math.round(normalized.value));
</script>

<style scoped>
.ui-rating {
    display: inline-flex;
    align-items: center;
    gap: .2rem;
    color: #d7a72d;
    font-size: .82rem;
}

.ui-rating__value {
    margin-left: .2rem;
    color: var(--premium-muted);
    font-size: .8rem;
    font-weight: 700;
}
</style>
