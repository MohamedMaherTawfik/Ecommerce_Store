<template>
    <div class="admin-field" :class="{ 'admin-field--full': fullWidth }">
        <label :for="id" class="admin-label">
            <i :class="icon"></i>
            <span>{{ label }}</span>
        </label>

        <div class="application-settings__input-wrap">
            <input
                :id="id"
                :value="modelValue"
                @input="$emit('update:modelValue', $event.target.value)"
                :type="type"
                class="form-control admin-control"
                :placeholder="placeholder"
                :disabled="disabled"
                :readonly="readonly"
                autocomplete="off"
            />
        </div>

        <p v-if="help" class="admin-helper-text">
            {{ help }}
        </p>
        <slot></slot>
    </div>
</template>

<script setup>
defineProps({
    modelValue: [String, Number],
    id: String,
    label: String,
    icon: {
        type: String,
        default: "bi bi-input-cursor-text",
    },
    type: {
        type: String,
        default: "text",
    },
    placeholder: String,
    help: String,
    disabled: Boolean,
    readonly: Boolean,
    fullWidth: Boolean,
});

defineEmits(['update:modelValue']);
</script>

<style scoped>
.application-settings__input-wrap {
    position: relative;
}
</style>
