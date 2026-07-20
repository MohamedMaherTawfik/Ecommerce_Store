<template>
    <div class="admin-field" :class="{ 'admin-field--full': fullWidth }">
        <label :for="field.key" class="admin-label">
            <i :class="icon"></i>
            <span>{{ field.label }}</span>
        </label>

        <select
            :id="field.key"
            :value="modelValue"
            class="form-select admin-control"
            :disabled="disabled"
            @change="$emit('update:modelValue', $event.target.value)"
        >
            <option v-for="option in field.options || []" :key="option.value" :value="option.value">
                {{ option.label }}
            </option>
        </select>

        <p v-if="field.help" class="admin-helper-text">{{ field.help }}</p>
    </div>
</template>

<script setup>
defineProps({
    modelValue: {
        type: [String, Number],
        default: "",
    },
    field: {
        type: Object,
        required: true,
    },
    icon: {
        type: String,
        default: "bi bi-chevron-expand",
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    fullWidth: {
        type: Boolean,
        default: false,
    },
});

defineEmits(["update:modelValue"]);
</script>
