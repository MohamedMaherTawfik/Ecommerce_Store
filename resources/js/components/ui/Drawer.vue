<template>
    <Teleport to="body">
        <div v-if="modelValue" class="ui-drawer" @click.self="$emit('update:modelValue', false)">
            <aside class="ui-drawer__panel">
                <slot />
            </aside>
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
.ui-drawer {
    position: fixed;
    inset: 0;
    z-index: 1200;
    background: rgba(2, 6, 23, .45);
}

.ui-drawer__panel {
    position: absolute;
    right: 0;
    top: 0;
    width: min(420px, 92vw);
    height: 100%;
    background: var(--premium-surface);
    box-shadow: var(--premium-shadow-lg);
    padding: 1.25rem;
}
</style>
