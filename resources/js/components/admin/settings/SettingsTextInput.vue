<template>
    <div class="admin-field" :class="{ 'admin-field--full': fullWidth }">
        <label :for="field.key" class="admin-label">
            <i :class="icon"></i>
            <span>{{ field.label }}</span>
        </label>

        <input
            :id="field.key"
            :value="modelValue"
            :type="inputType"
            class="form-control admin-control"
            :placeholder="field.placeholder || ''"
            :disabled="disabled"
            :readonly="readonly"
            autocomplete="off"
            @input="$emit('update:modelValue', $event.target.value)"
        />

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
        default: "bi bi-input-cursor-text",
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    readonly: {
        type: Boolean,
        default: false,
    },
    fullWidth: {
        type: Boolean,
        default: false,
    },
});

defineEmits(["update:modelValue"]);

const inputType = defineModel ? undefined : null;
</script>

<script>
export default {
    computed: {
        inputType() {
            if (this.field.type === "number") {
                return "number";
            }

            if (this.field.type === "email") {
                return "email";
            }

            if (this.field.type === "url") {
                return "url";
            }

            return "text";
        },
    },
};
</script>
