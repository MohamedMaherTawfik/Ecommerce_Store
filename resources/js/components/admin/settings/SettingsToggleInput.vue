<template>
    <div class="admin-field" :class="{ 'admin-field--full': fullWidth }">
        <label class="admin-label">
            <i :class="icon"></i>
            <span>{{ field.label }}</span>
        </label>

        <label class="settings-toggle">
            <input
                :checked="Boolean(modelValue)"
                type="checkbox"
                :disabled="disabled"
                @change="$emit('update:modelValue', $event.target.checked)"
            />
            <span class="settings-toggle__track">
                <span class="settings-toggle__thumb"></span>
            </span>
            <span class="settings-toggle__copy">
                {{ Boolean(modelValue) ? enabledLabel : disabledLabel }}
            </span>
        </label>

        <p v-if="field.help" class="admin-helper-text">{{ field.help }}</p>
    </div>
</template>

<script setup>
defineProps({
    modelValue: {
        type: [Boolean, String, Number],
        default: false,
    },
    field: {
        type: Object,
        required: true,
    },
    icon: {
        type: String,
        default: "bi bi-toggle-on",
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    fullWidth: {
        type: Boolean,
        default: false,
    },
    enabledLabel: {
        type: String,
        default: "Enabled",
    },
    disabledLabel: {
        type: String,
        default: "Disabled",
    },
});

defineEmits(["update:modelValue"]);
</script>

<style scoped>
.settings-toggle {
    display: inline-flex;
    align-items: center;
    gap: 0.8rem;
    cursor: pointer;
}

.settings-toggle input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.settings-toggle__track {
    position: relative;
    width: 54px;
    height: 30px;
    border-radius: 999px;
    background: rgba(148, 163, 184, 0.34);
    transition: background-color 0.2s ease;
}

.settings-toggle__thumb {
    position: absolute;
    top: 4px;
    left: 4px;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: #ffffff;
    box-shadow: 0 6px 14px rgba(15, 23, 42, 0.15);
    transition: transform 0.2s ease;
}

.settings-toggle input:checked + .settings-toggle__track {
    background: linear-gradient(135deg, #2563eb, #38bdf8);
}

.settings-toggle input:checked + .settings-toggle__track .settings-toggle__thumb {
    transform: translateX(24px);
}

.settings-toggle__copy {
    font-weight: 700;
    color: #334155;
}
</style>
