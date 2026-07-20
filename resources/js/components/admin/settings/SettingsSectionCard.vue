<template>
    <div class="card border-0 shadow-sm rounded-4 mb-4" style="background: rgba(255, 255, 255, 0.72);">
        <div class="card-body p-4">
            <h4 v-if="title" class="border-bottom pb-3 mb-4 fw-bold" style="color: #1e293b; font-size: 1.1rem;">{{ title }}</h4>
            <div class="admin-form-grid">
                <slot />
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    title: {
        type: String,
        default: "",
    },
});
</script>


