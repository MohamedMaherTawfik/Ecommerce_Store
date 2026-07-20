<template>
    <button :type="type" class="btn-admin" :class="variantClass" :disabled="disabled || loading">
        <span v-if="loading" class="spinner-border spinner-border-sm"></span>
        <i v-else-if="icon" :class="icon"></i>
        <span>{{ loading ? loadingLabel : label }}</span>
    </button>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
    label: {
        type: String,
        default: "Save Settings",
    },
    loadingLabel: {
        type: String,
        default: "Saving...",
    },
    loading: {
        type: Boolean,
        default: false,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    icon: {
        type: String,
        default: "bi bi-save",
    },
    variant: {
        type: String,
        default: "primary",
    },
    type: {
        type: String,
        default: "submit",
    },
});

const variantClass = computed(() => `btn-admin--${props.variant}`);
</script>
