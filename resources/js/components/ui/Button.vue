<template>
    <component
        :is="tag"
        v-bind="attrs"
        class="ui-button"
        :class="[`ui-button--${variant}`, { 'is-loading': loading, 'is-block': block }]"
    >
        <span v-if="loading" class="ui-button__spinner" aria-hidden="true"></span>
        <span class="ui-button__content">
            <slot />
        </span>
    </component>
</template>

<script setup>
import { computed, useAttrs } from "vue";

const props = defineProps({
    variant: { type: String, default: "primary" },
    as: { type: String, default: "button" },
    loading: { type: Boolean, default: false },
    block: { type: Boolean, default: false },
});

const attrs = useAttrs();
const tag = computed(() => props.as || "button");
</script>

<style scoped>
.ui-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .65rem;
    min-height: 44px;
    padding: .9rem 1.2rem;
    border-radius: 999px;
    border: 1px solid transparent;
    font-weight: 700;
    font-size: .92rem;
    text-decoration: none;
    cursor: pointer;
    transition: transform .2s ease, box-shadow .2s ease, opacity .2s ease, background .2s ease;
}

.ui-button:hover {
    transform: translateY(-1px);
}

.ui-button--primary {
    background: linear-gradient(135deg, var(--premium-ink), #232a36);
    color: #fff;
    box-shadow: var(--premium-shadow-sm);
}

.ui-button--secondary {
    background: var(--premium-surface);
    border-color: var(--premium-border);
    color: var(--premium-ink);
}

.ui-button--ghost {
    background: transparent;
    border-color: rgba(255, 255, 255, .18);
    color: #fff;
}

.ui-button--soft {
    background: var(--premium-surface-soft);
    border-color: var(--premium-border);
    color: var(--premium-text);
}

.ui-button--gold {
    background: linear-gradient(135deg, var(--premium-gold), #f6d992);
    color: #231a04;
    box-shadow: 0 18px 30px rgba(184, 134, 11, .18);
}

.ui-button.is-block {
    width: 100%;
}

.ui-button:disabled,
.ui-button.is-loading {
    opacity: .72;
    cursor: wait;
}

.ui-button__spinner {
    width: 16px;
    height: 16px;
    border-radius: 50%;
    border: 2px solid currentColor;
    border-right-color: transparent;
    animation: spin .75s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}
</style>
