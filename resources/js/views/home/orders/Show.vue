<template>
    <main class="store-shell orders-page">
        <header class="orders-header store-card">
            <div>
                <span class="store-eyebrow">Orders</span>
                <h1 class="store-title">Order tracking</h1>
                <p class="store-subtitle">Track order status and timeline updates.</p>
            </div>
        </header>

        <section class="tracker store-card">
            <form class="tracker-form" @submit.prevent="fetchStatus">
                <input v-model="orderId" class="store-input form-control" type="number" min="1" placeholder="Order ID" required />
                <button class="store-btn store-btn--primary" :disabled="loading">{{ loading ? 'Loading...' : 'Track' }}</button>
            </form>

            <div v-if="error" class="tracker-alert tracker-alert--error">{{ error }}</div>

            <div v-if="order" class="tracking-body">
                <div class="tracking-head">
                    <div>
                        <p>Order</p>
                        <h2>{{ order.order_number }}</h2>
                    </div>
                    <div>
                        <p>Total</p>
                        <strong>{{ money(order.total || 0) }}</strong>
                    </div>
                </div>

                <div class="timeline">
                    <article v-for="log in order.timeline || []" :key="log.id" class="timeline-row">
                        <span class="timeline-dot"></span>
                        <div>
                            <strong>{{ log.to_status }}</strong>
                            <p>{{ formatDate(log.created_at) }} {{ log.note ? `- ${log.note}` : '' }}</p>
                        </div>
                    </article>
                </div>
            </div>

            <div v-else-if="!loading" class="tracker-empty">
                Enter order ID to view timeline.
            </div>
        </section>
    </main>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { useSeoMeta } from '@/composables/useSeoMeta';
import OrderService from '@/services/home/OrderService';

const route = useRoute();
const orderId = ref(route.params.id || '');
const order = ref(null);

useSeoMeta({
    title: () => order.value ? `Order ${order.value.order_number}` : 'Order Tracking',
    description: 'Track your EliteShop order status and timeline updates.',
    robots: 'noindex,nofollow'
});

const loading = ref(false);
const error = ref('');

const fetchStatus = async () => {
    if (!orderId.value) {
        return;
    }

    loading.value = true;
    error.value = '';

    try {
        order.value = await OrderService.status(orderId.value);
    } catch {
        error.value = 'Unable to fetch order status right now.';
    } finally {
        loading.value = false;
    }
};

const money = (value) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(Number(value || 0));
const formatDate = (value) => value ? new Intl.DateTimeFormat('en', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : '';

onMounted(async () => {
    if (orderId.value) {
        await fetchStatus();
    }
});
</script>

<style scoped>
.orders-page {
    display: grid;
    gap: 1rem;
}

.orders-header {
    padding: 1.2rem;
}

.tracker {
    display: grid;
    gap: 0.9rem;
    padding: 1rem;
}

.tracker-form {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 0.65rem;
}

.tracker-alert {
    padding: 0.65rem 0.8rem;
    border-radius: 0.7rem;
    font-size: 0.86rem;
}

.tracker-alert--error {
    background: color-mix(in srgb, var(--sf-danger) 14%, transparent);
    color: var(--sf-danger);
}

.tracking-body {
    display: grid;
    gap: 1rem;
}

.tracking-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.tracking-head p {
    margin: 0;
    color: var(--sf-muted);
    font-size: 0.8rem;
}

.tracking-head h2 {
    margin: 0.2rem 0 0;
    color: var(--sf-text);
    font-size: 1.2rem;
    font-weight: 800;
}

.tracking-head strong {
    color: var(--sf-text);
    font-size: 1.1rem;
}

.timeline {
    border-top: 1px solid var(--sf-border);
}

.timeline-row {
    display: flex;
    gap: 0.8rem;
    padding: 0.85rem 0;
    border-bottom: 1px solid var(--sf-border);
}

.timeline-dot {
    width: 13px;
    height: 13px;
    margin-top: 0.35rem;
    border-radius: 999px;
    background: var(--sf-primary);
    flex: 0 0 auto;
}

.timeline-row strong {
    color: var(--sf-text);
    font-size: 0.9rem;
}

.timeline-row p {
    margin: 0.3rem 0 0;
    color: var(--sf-muted);
    font-size: 0.82rem;
}

.tracker-empty {
    color: var(--sf-muted);
    font-size: 0.9rem;
}

@media (max-width: 575.98px) {
    .tracker-form {
        grid-template-columns: 1fr;
    }

    .tracking-head {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>
