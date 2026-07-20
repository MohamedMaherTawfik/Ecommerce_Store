<template>
    <div class="admin-field" :class="{ 'admin-field--full': fullWidth }">
        <div class="application-settings__switch-wrap">
            <label :for="id" class="application-settings__switch">
                <input
                    :id="id"
                    type="checkbox"
                    :checked="modelValue"
                    @change="$emit('update:modelValue', $event.target.checked)"
                    :disabled="disabled"
                />
                <span class="switch-label-text">
                    <i v-if="icon" :class="icon"></i>
                    {{ label }}
                </span>
            </label>
        </div>

        <p v-if="help" class="admin-helper-text" style="margin-top: 0.5rem;">
            {{ help }}
        </p>
    </div>
</template>

<script setup>
defineProps({
    modelValue: Boolean,
    id: String,
    label: String,
    icon: {
        type: String,
        default: "",
    },
    help: String,
    disabled: Boolean,
    fullWidth: Boolean,
});

defineEmits(['update:modelValue']);
</script>

<style scoped>
.application-settings__switch {
    display: inline-flex;
    align-items: center;
    gap: 0.65rem;
    color: #475569;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
}
.switch-label-text {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.switch-label-text i {
    color: #1d4ed8;
}
</style>
