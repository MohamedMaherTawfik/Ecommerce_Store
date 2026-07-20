<template>
    <div class="ui-price">
        <strong class="ui-price__current">{{ formattedCurrent }}</strong>
        <s v-if="compareAt !== null" class="ui-price__compare">{{ formattedCompare }}</s>
    </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
    value: { type: [Number, String], required: true },
    compareAt: { type: [Number, String, null], default: null },
    currency: { type: String, default: "USD" },
});

const formatter = computed(() => new Intl.NumberFormat("en-US", { style: "currency", currency: props.currency }));

const formattedCurrent = computed(() => formatter.value.format(Number(props.value || 0)));
const formattedCompare = computed(() => (props.compareAt === null ? "" : formatter.value.format(Number(props.compareAt || 0))));
</script>

<style scoped>
.ui-price {
    display: inline-flex;
    align-items: baseline;
    gap: .6rem;
}

.ui-price__current {
    font-size: 1.05rem;
    font-weight: 800;
    color: var(--premium-ink);
}

.ui-price__compare {
    color: var(--premium-muted);
    font-size: .82rem;
}
</style>
