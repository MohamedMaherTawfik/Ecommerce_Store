<template>
    <div class="ui-skeleton" :class="[`ui-skeleton--${variant}`]" :style="styleObject">
        <slot />
    </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
    variant: { type: String, default: "block" },
    height: { type: [String, Number], default: "100%" },
    radius: { type: String, default: "1rem" },
});

const styleObject = computed(() => ({
    minHeight: typeof props.height === "number" ? `${props.height}px` : props.height,
    borderRadius: props.radius,
}));
</script>

<style scoped>
.ui-skeleton {
    position: relative;
    overflow: hidden;
    background: linear-gradient(90deg, #e8e8eb 0%, #f5f5f7 40%, #e8e8eb 80%);
    background-size: 400% 100%;
    animation: shimmer 1.5s linear infinite;
}

.ui-skeleton--circle {
    border-radius: 999px;
}

@keyframes shimmer {
    0% {
        background-position: 100% 0;
    }
    100% {
        background-position: -100% 0;
    }
}
</style>
