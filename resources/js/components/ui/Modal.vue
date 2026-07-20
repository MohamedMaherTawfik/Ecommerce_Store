<template>
    <Teleport to="body">
        <div v-if="modelValue" class="ui-modal" @click.self="$emit('update:modelValue', false)">
            <div class="ui-modal__panel">
                <button class="ui-modal__close" type="button" @click="$emit('update:modelValue', false)" aria-label="Close">
                    <i class="bi bi-x-lg"></i>
                </button>
                <slot />
            </div>
        </div>
    </Teleport>
</template>

<script setup>
defineProps({
    modelValue: { type: Boolean, default: false },
});

defineEmits(["update:modelValue"]);
</script>

<style scoped>
.ui-modal {
    position: fixed;
    inset: 0;
    z-index: 1200;
    display: grid;
    place-items: center;
    padding: 1rem;
    background: rgba(2, 6, 23, .6);
}

.ui-modal__panel {
    position: relative;
    width: min(780px, 100%);
    border-radius: 1.5rem;
    background: var(--premium-surface);
    box-shadow: var(--premium-shadow-lg);
    padding: 1.25rem;
}

.ui-modal__close {
    position: absolute;
    top: .75rem;
    right: .75rem;
    width: 40px;
    height: 40px;
    border: 0;
    border-radius: 999px;
    background: var(--premium-surface-soft);
}
</style>
