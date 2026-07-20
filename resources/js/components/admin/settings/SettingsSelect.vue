<template>
    <div class="admin-field" :class="{ 'admin-field--full': fullWidth }">
        <label :for="id" class="admin-label">
            <i :class="icon"></i>
            <span>{{ label }}</span>
        </label>

        <div class="application-settings__input-wrap">
            <select
                :id="id"
                :value="modelValue"
                @change="$emit('update:modelValue', $event.target.value)"
                class="form-control admin-control form-select"
                :disabled="disabled"
            >
                <option v-for="option in options" :key="option.value" :value="option.value">
                    {{ option.label }}
                </option>
            </select>
        </div>

        <p v-if="help" class="admin-helper-text">
            {{ help }}
        </p>
    </div>
</template>

<script setup>
defineProps({
    modelValue: [String, Number],
    id: String,
    label: String,
    icon: {
        type: String,
        default: "bi bi-list-ul",
    },
    options: {
        type: Array,
        default: () => [],
    },
    help: String,
    disabled: Boolean,
    fullWidth: Boolean,
});

defineEmits(['update:modelValue']);
</script>

<style scoped>
.application-settings__input-wrap {
    position: relative;
}
</style>
