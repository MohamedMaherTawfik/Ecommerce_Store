<template>
    <header class="section-title">
        <div>
            <slot name="eyebrow">
                <span v-if="eyebrow" class="section-title__eyebrow">{{ eyebrow }}</span>
            </slot>
            <h2 class="section-title__heading">{{ title }}</h2>
            <p v-if="description" class="section-title__description">{{ description }}</p>
        </div>
        <div v-if="$slots.actions" class="section-title__actions">
            <slot name="actions" />
        </div>
    </header>
</template>

<script setup>
defineProps({
    eyebrow: { type: String, default: "" },
    title: { type: String, required: true },
    description: { type: String, default: "" },
});
</script>

<style scoped>
.section-title {
    display: flex;
    align-items: end;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.4rem;
}

.section-title__eyebrow {
    display: inline-flex;
    align-items: center;
    padding: .35rem .72rem;
    border-radius: 999px;
    background: var(--premium-gold-soft);
    color: var(--premium-gold-ink);
    font-size: .7rem;
    font-weight: 800;
    letter-spacing: .14em;
    text-transform: uppercase;
}

.section-title__heading {
    margin: .6rem 0 .3rem;
    font-size: clamp(1.55rem, 2vw, 2.35rem);
    line-height: 1.08;
    letter-spacing: -.04em;
    color: var(--premium-ink);
}

.section-title__description {
    margin: 0;
    max-width: 58ch;
    color: var(--premium-muted);
}

.section-title__actions {
    flex-shrink: 0;
}

@media (max-width: 700px) {
    .section-title {
        align-items: start;
        flex-direction: column;
    }
}
</style>
