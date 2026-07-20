<template>
    <div class="admin-field" :class="{ 'admin-field--full': fullWidth }">
        <label :for="field.key" class="admin-label">
            <i :class="icon"></i>
            <span>{{ field.label }}</span>
        </label>

        <div class="settings-password__wrap">
            <input
                :id="field.key"
                :value="modelValue"
                :type="visible ? 'text' : 'password'"
                class="form-control admin-control settings-password__input"
                :placeholder="field.placeholder || ''"
                :disabled="disabled"
                autocomplete="off"
                @input="$emit('update:modelValue', $event.target.value)"
            />

            <button type="button" class="settings-password__toggle" :disabled="disabled" @click="visible = !visible">
                <i class="bi" :class="visible ? 'bi-eye-slash' : 'bi-eye'"></i>
            </button>
        </div>

        <p v-if="field.help" class="admin-helper-text">{{ field.help }}</p>
    </div>
</template>

<script setup>
import { ref } from "vue";

defineProps({
    modelValue: {
        type: String,
        default: "",
    },
    field: {
        type: Object,
        required: true,
    },
    icon: {
        type: String,
        default: "bi bi-key",
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

const visible = ref(false);
</script>

<style scoped>
.settings-password__wrap {
    position: relative;
}

.settings-password__input {
    padding-right: 3rem;
}

.settings-password__toggle {
    position: absolute;
    top: 50%;
    right: 0.95rem;
    transform: translateY(-50%);
    border: 0;
    background: transparent;
    color: var(--admin-muted);
}
</style>
