<template>
    <AdminLayout>
        <div class="admin-page payments-page">
            <section class="admin-page-header">
                <div class="admin-page-copy">
                    <div class="admin-page-kicker">
                        <i class="bi bi-receipt"></i>
                        Order Details
                    </div>
                    <h2 class="admin-page-title">{{ order?.order_number || "Loading order" }}</h2>
                    <p class="admin-page-description">
                        Inspect the customer, PayPal transaction, item lines, and current payment state.
                    </p>
                </div>
                <div class="admin-page-actions">
                    <RouterLink to="/admin/payments/orders" class="btn-admin btn-admin--soft">
                        <i class="bi bi-arrow-left"></i>
                        Back
                    </RouterLink>
                </div>
            </section>

            <div v-if="loading" class="admin-grid">
                <div v-for="card in 4" :key="card" class="admin-grid__item--6">
                    <div class="admin-skeleton-panel">
                        <div class="admin-skeleton-line admin-skeleton-line--lg"></div>
                        <div class="admin-skeleton-line"></div>
                        <div class="admin-skeleton-line admin-skeleton-line--md"></div>
                    </div>
                </div>
            </div>

            <template v-else-if="order">
                <section class="admin-grid">
                    <article class="payment-card admin-grid__item--6">
                        <div class="payment-card__header">
                            <h3>Order info</h3>
                            <span class="payment-badge" :class="statusClass(order.payment_status)">
                                {{ labelStatus(order.payment_status) }}
                            </span>
                        </div>
                        <dl class="payment-facts">
                            <div>
                                <dt>Order number</dt>
                                <dd>{{ order.order_number }}</dd>
                            </div>
                            <div>
                                <dt>Order status</dt>
                                <dd>{{ order.status }}</dd>
                            </div>
                            <div>
                                <dt>Created</dt>
                                <dd>{{ formatDate(order.created_at) }}</dd>
                            </div>
                            <div>
                                <dt>Total</dt>
                                <dd class="payment-total">{{ formatMoney(order.total) }}</dd>
                            </div>
                        </dl>
                    </article>

                    <article class="payment-card admin-grid__item--6">
                        <div class="payment-card__header">
                            <h3>Customer info</h3>
                        </div>
                        <dl class="payment-facts">
                            <div>
                                <dt>Name</dt>
                                <dd>{{ order.user?.name || "Guest" }}</dd>
                            </div>
                            <div>
                                <dt>Email</dt>
                                <dd>{{ order.user?.email || "-" }}</dd>
                            </div>
                            <div>
                                <dt>Phone</dt>
                                <dd>{{ order.phone || order.user?.phone || "-" }}</dd>
                            </div>
                            <div>
                                <dt>Address</dt>
                                <dd>{{ fullAddress }}</dd>
                            </div>
                        </dl>
                    </article>

                    <article class="payment-card admin-grid__item--6">
                        <div class="payment-card__header">
                            <h3>PayPal info</h3>
                        </div>
                        <dl class="payment-facts">
                            <div>
                                <dt>PayPal order</dt>
                                <dd>{{ order.paypal?.paypal_order_id || "-" }}</dd>
                            </div>
                            <div>
                                <dt>Transaction</dt>
                                <dd>{{ order.paypal?.transaction_id || "-" }}</dd>
                            </div>
                            <div>
                                <dt>Payer email</dt>
                                <dd>{{ order.paypal?.payer_email || "-" }}</dd>
                            </div>
                            <div>
                                <dt>Paid at</dt>
                                <dd>{{ formatDate(order.paypal?.paid_at) }}</dd>
                            </div>
                        </dl>
                    </article>

                    <article v-if="canManageOrders" class="payment-card admin-grid__item--6">
                        <div class="payment-card__header">
                            <h3>Status update</h3>
                        </div>
                        <label class="admin-label payment-status-control">
                            Payment status
                            <select
                                v-model="selectedStatus"
                                class="form-select admin-control"
                                :disabled="updating"
                                @change="updateStatus"
                            >
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="paid">Paid</option>
                                <option value="failed">Failed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </label>
                        <p class="payment-card__hint">
                            Updates are saved immediately and reflected in the order timeline.
                        </p>
                    </article>
                </section>

                <section class="admin-panel">
                    <div class="admin-panel__header">
                        <div>
                            <h3 class="admin-panel__title">Items list</h3>
                            <p class="admin-panel__meta">Products captured when the order was created.</p>
                        </div>
                        <strong class="payment-grand-total">{{ formatMoney(order.total) }}</strong>
                    </div>

                    <div class="admin-panel__body">
                        <div v-if="!order.items?.length" class="admin-empty-state">
                            <div class="admin-empty-state__icon">
                                <i class="bi bi-bag-x"></i>
                            </div>
                            <h3 class="admin-empty-state__title">No items recorded</h3>
                        </div>

                        <div v-else class="admin-table-wrap">
                            <table class="admin-table payment-items-table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>SKU</th>
                                        <th>Quantity</th>
                                        <th class="text-end">Line total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in order.items" :key="item.id">
                                        <td>
                                            <div class="admin-table__primary">{{ item.product?.name || `Product #${item.product_id}` }}</div>
                                            <div class="admin-table__secondary">Item #{{ item.id }}</div>
                                        </td>
                                        <td>{{ item.product?.sku || "-" }}</td>
                                        <td>{{ item.quantity }}</td>
                                        <td class="text-end payments-total">{{ formatMoney(item.price) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </template>
        </div>
    </AdminLayout>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import { RouterLink, useRoute } from "vue-router";
import toastr from "toastr";
import { hasAdminPermission } from "@/config/adminAccess";
import { getUserData } from "@/services/auth/authSession";
import AdminLayout from "@/views/admin/layout/AdminLayout.vue";
import OrderService from "@/services/admin/payments/OrderService";

const route = useRoute();
const canManageOrders = hasAdminPermission(getUserData() || {}, "orders.manage");
const order = ref(null);
const loading = ref(false);
const updating = ref(false);
const selectedStatus = ref("pending");

const fetchOrder = async () => {
    loading.value = true;

    try {
        const payload = await OrderService.getOrder(route.params.id);
        order.value = payload.data;
        selectedStatus.value = order.value?.payment_status || "pending";
    } catch (error) {
        toastr.error("Unable to load order details.");
    } finally {
        loading.value = false;
    }
};

const updateStatus = async () => {
    if (!order.value || selectedStatus.value === order.value.payment_status) {
        return;
    }

    updating.value = true;

    try {
        const payload = await OrderService.updateStatus(order.value.id, { status: selectedStatus.value });
        order.value = payload.data;
        selectedStatus.value = order.value.payment_status;
        toastr.success("Payment status updated.");
    } catch (error) {
        selectedStatus.value = order.value.payment_status;
        toastr.error("Unable to update status.");
    } finally {
        updating.value = false;
    }
};

const fullAddress = computed(() => {
    if (!order.value) return "-";
    return [order.value.address, order.value.city, order.value.country].filter(Boolean).join(", ") || "-";
});

const statusClass = (status) => ({
    "payment-badge--paid": status === "paid",
    "payment-badge--failed": status === "failed",
    "payment-badge--pending": !["paid", "failed"].includes(status),
});

const labelStatus = (status) => status ? status.replace("_", " ") : "pending";

const formatMoney = (value) => new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
}).format(Number(value || 0));

const formatDate = (value) => {
    if (!value) return "-";
    return new Intl.DateTimeFormat("en", { dateStyle: "medium", timeStyle: "short" }).format(new Date(value));
};

onMounted(fetchOrder);
</script>

<style scoped>
.payments-page {
    gap: 1.25rem;
}

.payment-card {
    grid-column: span 6;
    padding: 1.25rem;
    border: 1px solid var(--admin-border);
    border-radius: var(--admin-radius-xl);
    background: var(--admin-surface);
    box-shadow: var(--admin-shadow-soft);
}

.payment-card__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1rem;
}

.payment-card__header h3 {
    margin: 0;
    font-size: 1rem;
    font-weight: 800;
}

.payment-card__hint {
    margin: 0.75rem 0 0;
    color: var(--admin-muted);
    font-size: 0.9rem;
}

.payment-facts {
    display: grid;
    gap: 0.85rem;
    margin: 0;
}

.payment-facts div {
    display: grid;
    grid-template-columns: 140px minmax(0, 1fr);
    gap: 1rem;
    align-items: baseline;
}

.payment-facts dt {
    color: var(--admin-muted);
    font-size: 0.78rem;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.payment-facts dd {
    margin: 0;
    color: var(--admin-text);
    font-weight: 700;
    overflow-wrap: anywhere;
}

.payment-total,
.payments-total,
.payment-grand-total {
    color: #0f172a;
    font-weight: 900;
}

.payment-grand-total {
    font-size: 1.25rem;
}

.payment-status-control {
    display: flex;
    flex-direction: column;
    margin-bottom: 0;
}

.payment-items-table {
    min-width: 720px;
}

.payment-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.38rem 0.75rem;
    border-radius: 999px;
    font-size: 0.8rem;
    font-weight: 800;
    text-transform: capitalize;
}

.payment-badge--pending {
    background: rgba(148, 163, 184, 0.14);
    color: #475569;
}

.payment-badge--paid {
    background: rgba(22, 163, 74, 0.12);
    color: #15803d;
}

.payment-badge--failed {
    background: rgba(239, 68, 68, 0.12);
    color: #b91c1c;
}

@media (max-width: 991.98px) {
    .payment-card {
        grid-column: span 12;
    }
}

@media (max-width: 575.98px) {
    .payment-facts div {
        grid-template-columns: 1fr;
        gap: 0.25rem;
    }
}
</style>
