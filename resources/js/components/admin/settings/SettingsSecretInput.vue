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
                :type="showSecret ? 'text' : 'password'"
                class="form-control admin-control"
                :placeholder="placeholder"
                :disabled="disabled"
                autocomplete="off"
            />
            <button
                type="button"
                class="application-settings__input-action"
                @click="showSecret = !showSecret"
                :disabled="disabled"
            >
                <i class="bi" :class="showSecret ? 'bi-eye-slash' : 'bi-eye'"></i>
            </button>
        </div>

        <p v-if="help" class="admin-helper-text">
            {{ help }}
        </p>
    </div>
</template>

<script setup>
import { ref } from 'vue';

defineProps({
    modelValue: String,
    id: String,
    label: String,
    icon: {
        type: String,
        default: "bi bi-key",
    },
    placeholder: String,
    help: String,
    disabled: Boolean,
    fullWidth: Boolean,
});

defineEmits(['update:modelValue']);

const showSecret = ref(false);
</script>

<style scoped>
.application-settings__input-wrap {
    position: relative;
}

.application-settings__input-action {
    position: absolute;
    top: 50%;
    right: 0.95rem;
    transform: translateY(-50%);
    border: 0;
    background: transparent;
    color: var(--admin-muted, #64748b);
    cursor: pointer;
}
.application-settings__input-action:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>
