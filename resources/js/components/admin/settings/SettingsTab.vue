<template>
    <button
        type="button"
        class="application-settings__tab"
        :class="{ 'is-active': active }"
        @click="$emit('click')"
    >
        <span class="application-settings__tab-copy">
            <span class="application-settings__tab-title">{{ title }}</span>
            <span class="application-settings__tab-text">{{ description }}</span>
        </span>
        <i class="bi" :class="active ? 'bi-arrow-right-circle-fill' : 'bi-arrow-right-circle'"></i>
    </button>
</template>

<script setup>
defineProps({
    title: String,
    description: String,
    active: Boolean,
});
defineEmits(['click']);
</script>

<style scoped>
.application-settings__tab {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.8rem;
    width: 100%;
    padding: 1.05rem 1.1rem;
    border: 1px solid rgba(148, 163, 184, 0.18);
    border-radius: 22px;
    background: rgba(255, 255, 255, 0.7);
    color: #334155;
    text-align: left;
    transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease, background-color 0.22s ease;
}

.application-settings__tab:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.application-settings__tab.is-active {
    border-color: rgba(37, 99, 235, 0.22);
    background: linear-gradient(135deg, rgba(37, 99, 235, 0.12), rgba(14, 165, 233, 0.12));
    color: #0f172a;
    box-shadow: 0 16px 28px rgba(37, 99, 235, 0.08);
}

.application-settings__tab-copy {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.application-settings__tab-title {
    font-weight: 800;
}

.application-settings__tab-text {
    color: #64748b;
    font-size: 0.88rem;
    line-height: 1.55;
}
</style>
