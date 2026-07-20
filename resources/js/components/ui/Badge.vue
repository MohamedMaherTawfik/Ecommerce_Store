<template>
    <span class="ui-badge" :class="`ui-badge--${variant}`">
        <slot />
    </span>
</template>

<script setup>
defineProps({
    variant: { type: String, default: "default" },
});
</script>

<style scoped>
.ui-badge {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .38rem .7rem;
    border-radius: 999px;
    font-size: .72rem;
    font-weight: 800;
    letter-spacing: .12em;
    text-transform: uppercase;
}

.ui-badge--default {
    background: var(--premium-surface-soft);
    color: var(--premium-muted);
}

.ui-badge--gold {
    background: rgba(184, 134, 11, .12);
    color: #9c6b06;
}

.ui-badge--dark {
    background: rgba(15, 23, 42, .08);
    color: var(--premium-ink);
}

[data-theme="dark"] .ui-badge--dark {
    background: rgba(255, 255, 255, .08);
    color: #fff;
}
</style>
