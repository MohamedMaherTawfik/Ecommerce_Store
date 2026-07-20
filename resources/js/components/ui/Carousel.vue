<template>
    <div class="ui-carousel">
        <div class="ui-carousel__viewport">
            <slot :active-index="activeIndex" />
        </div>
        <div v-if="$slots.controls || showControls" class="ui-carousel__controls">
            <slot name="controls" :previous="previous" :next="next" :go-to="goTo" :active-index="activeIndex" />
            <template v-if="showControls && !$slots.controls">
                <button class="ui-carousel__nav" type="button" @click="previous" aria-label="Previous slide">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <button class="ui-carousel__nav" type="button" @click="next" aria-label="Next slide">
                    <i class="bi bi-chevron-right"></i>
                </button>
            </template>
        </div>
    </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
    modelValue: { type: Number, default: 0 },
    length: { type: Number, default: 0 },
    showControls: { type: Boolean, default: true },
});

const emit = defineEmits(["update:modelValue", "change"]);

const activeIndex = computed(() => props.modelValue);

const wrap = (value) => {
    if (!props.length) return 0;
    return ((value % props.length) + props.length) % props.length;
};

const goTo = (value) => {
    const nextValue = wrap(value);
    emit("update:modelValue", nextValue);
    emit("change", nextValue);
};

const next = () => goTo(props.modelValue + 1);
const previous = () => goTo(props.modelValue - 1);
</script>

<style scoped>
.ui-carousel {
    position: relative;
}

.ui-carousel__controls {
    display: flex;
    align-items: center;
    gap: .6rem;
}

.ui-carousel__nav {
    width: 44px;
    height: 44px;
    border-radius: 999px;
    border: 1px solid var(--premium-border);
    background: var(--premium-surface);
    color: var(--premium-ink);
}
</style>
