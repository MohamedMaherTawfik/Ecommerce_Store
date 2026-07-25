<template>
    <main class="store-shell orders-page">
        <header class="orders-header store-card">
            <div>
                <span class="store-eyebrow">Orders</span>
                <h1 class="store-title">{{ order ? order.order_number : 'My orders' }}</h1>
                <p class="store-subtitle">Review order history, invoices, shipment status, and returns.</p>
            </div>
        </header>

        <div v-if="loading" class="orders-loading store-card">Loading orders...</div>

        <section v-else-if="!order" class="orders-list store-card">
            <div v-if="!orders.length" class="empty-state">
                <i class="bi bi-bag-x"></i>
                <h2>No orders yet</h2>
                <RouterLink :to="`/${lang}/products`" class="store-btn store-btn--primary">Shop products</RouterLink>
            </div>

            <RouterLink
                v-for="item in orders"
                :key="item.id"
                :to="`/${lang}/orders/${item.id}`"
                class="order-row"
            >
                <div>
                    <strong>{{ item.order_number }}</strong>
                    <span>{{ formatDate(item.created_at) }}</span>
                </div>
                <div>
                    <span>{{ item.order_status }}</span>
                    <span>{{ item.payment_status }}</span>
                </div>
                <strong>{{ money(item.total, item.currency) }}</strong>
            </RouterLink>
        </section>

        <template v-else>
            <section class="order-detail store-card">
                <div class="detail-head">
                    <RouterLink :to="`/${lang}/orders`" class="store-btn store-btn--soft">Back to orders</RouterLink>
                    <a
                        v-if="order.invoice"
                        class="store-btn store-btn--primary"
                        :href="OrderService.invoiceUrl(order.id)"
                        target="_blank"
                        rel="noopener"
                    >
                        Download invoice
                    </a>
                </div>

                <div class="facts">
                    <div><span>Order status</span><strong>{{ order.order_status }}</strong></div>
                    <div><span>Payment</span><strong>{{ order.payment_status }}</strong></div>
                    <div><span>Shipping</span><strong>{{ order.shipping_status }}</strong></div>
                    <div><span>Total</span><strong>{{ money(order.total, order.currency) }}</strong></div>
                </div>

                <div class="items">
                    <h2>Items</h2>
                    <article v-for="item in order.items || []" :key="item.id" class="item-row">
                        <span>{{ item.product_name || item.product?.name || `Product #${item.product_id}` }}</span>
                        <span>x{{ item.quantity }}</span>
                        <strong>{{ money(item.total_price || item.price, order.currency) }}</strong>
                    </article>
                </div>

                <div class="timeline">
                    <h2>Timeline</h2>
                    <article v-for="log in order.timeline || []" :key="log.id" class="timeline-row">
                        <span class="timeline-dot"></span>
                        <div>
                            <strong>{{ log.to_status }}</strong>
                            <p>{{ formatDate(log.created_at) }} {{ log.note ? `- ${log.note}` : '' }}</p>
                        </div>
                    </article>
                    <p v-if="!order.timeline?.length" class="muted">No timeline updates yet.</p>
                </div>
            </section>

            <section class="returns-panel store-card">
                <h2>Returns</h2>
                <div v-if="order.returns?.length" class="return-list">
                    <article v-for="item in order.returns" :key="item.id" class="return-row">
                        <div>
                            <strong>Return #{{ item.id }}</strong>
                            <span>{{ item.status }}</span>
                        </div>
                        <p>{{ item.reason }}</p>
                    </article>
                </div>
                <form class="return-form" @submit.prevent="submitReturn">
                    <textarea v-model.trim="returnForm.reason" class="store-textarea form-control" rows="3" required maxlength="1000" placeholder="Reason for return"></textarea>
                    <textarea v-model.trim="returnForm.notes" class="store-textarea form-control" rows="2" maxlength="1000" placeholder="Additional notes"></textarea>
                    <button class="store-btn store-btn--primary" :disabled="busy">Request return</button>
                </form>
            </section>
        </template>
    </main>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { useSeoMeta } from '@/composables/useSeoMeta';
import toastr from 'toastr';
import OrderService from '@/services/home/OrderService';

const route = useRoute();
const lang = computed(() => route.params.lang || localStorage.getItem('language') || 'en');
const orders = ref([]);
const order = ref(null);
const loading = ref(false);
const busy = ref(false);
const returnForm = reactive({ reason: '', notes: '' });

useSeoMeta({
    title: () => order.value ? `Order ${order.value.order_number}` : 'My Orders',
    description: 'Review your EliteShop orders, invoices, shipment status, and returns.',
    robots: 'noindex,nofollow'
});

const loadOrders = async () => {
    loading.value = true;
    try {
        const payload = await OrderService.list();
        orders.value = Array.isArray(payload?.data) ? payload.data : [];
        order.value = null;
    } catch {
        toastr.error('Unable to load orders.');
    } finally {
        loading.value = false;
    }
};

const loadOrder = async (id) => {
    loading.value = true;
    try {
        order.value = await OrderService.show(id);
    } catch {
        toastr.error('Unable to load order.');
    } finally {
        loading.value = false;
    }
};

const submitReturn = async () => {
    if (!order.value) return;
    busy.value = true;
    try {
        await OrderService.createReturn(order.value.id, {
            ...returnForm,
            items: (order.value.items || []).map((item) => ({
                order_item_id: item.id,
                quantity: item.quantity,
                reason: returnForm.reason,
            })),
        });
        returnForm.reason = '';
        returnForm.notes = '';
        toastr.success('Return request submitted.');
        await loadOrder(order.value.id);
    } catch (error) {
        toastr.error(error.response?.data?.message || 'Unable to request return.');
    } finally {
        busy.value = false;
    }
};

const load = async () => {
    if (route.params.id) await loadOrder(route.params.id);
    else await loadOrders();
};

const money = (value, currency = 'EGP') => new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(Number(value || 0));
const formatDate = (value) => value ? new Intl.DateTimeFormat('en', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : '-';

watch(() => route.params.id, load);
onMounted(load);
</script>

<style scoped>
.orders-page,
.order-detail,
.returns-panel {
    display: grid;
    gap: 1rem;
}

.orders-header,
.orders-loading,
.orders-list,
.order-detail,
.returns-panel {
    padding: 1rem;
}

.empty-state {
    min-height: 260px;
    display: grid;
    place-items: center;
    gap: 0.6rem;
    text-align: center;
}

.empty-state i {
    color: var(--sf-muted);
    font-size: 1.8rem;
}

.order-row,
.item-row,
.return-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto auto;
    gap: 1rem;
    align-items: center;
    padding: 0.85rem 0;
    border-bottom: 1px solid var(--sf-border);
    color: var(--sf-text);
    text-decoration: none;
}

.order-row div,
.return-row div {
    display: grid;
    gap: 0.2rem;
}

.order-row span,
.return-row span,
.muted {
    color: var(--sf-muted);
    font-size: 0.84rem;
}

.detail-head {
    display: flex;
    gap: 0.7rem;
    justify-content: space-between;
    flex-wrap: wrap;
}

.facts {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.7rem;
}

.facts div {
    padding: 0.75rem;
    border: 1px solid var(--sf-border);
    border-radius: 0.75rem;
    background: var(--sf-surface-soft);
}

.facts span {
    display: block;
    color: var(--sf-muted);
    font-size: 0.78rem;
}

.items h2,
.timeline h2,
.returns-panel h2 {
    margin: 0 0 0.7rem;
    color: var(--sf-text);
    font-size: 1.05rem;
}

.timeline-row {
    display: flex;
    gap: 0.75rem;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--sf-border);
}

.timeline-dot {
    width: 12px;
    height: 12px;
    margin-top: 0.35rem;
    border-radius: 999px;
    background: var(--sf-primary);
    flex: 0 0 auto;
}

.timeline-row p,
.return-row p {
    margin: 0.25rem 0 0;
    color: var(--sf-muted);
    font-size: 0.84rem;
}

.return-list,
.return-form {
    display: grid;
    gap: 0.7rem;
}

.return-form {
    margin-top: 0.6rem;
}

@media (max-width: 767.98px) {
    .facts,
    .order-row,
    .item-row,
    .return-row {
        grid-template-columns: 1fr;
    }
}
</style>
