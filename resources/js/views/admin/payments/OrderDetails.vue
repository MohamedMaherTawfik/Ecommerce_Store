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
                        Inspect the customer, Paymob transaction, item lines, and current payment state.
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
                                <dd class="payment-total">{{ formatMoney(order.total, order.currency) }}</dd>
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
                            <h3>Paymob info</h3>
                        </div>
                        <dl class="payment-facts">
                            <div>
                                <dt>Payment channel</dt>
                                <dd>{{ labelStatus(order.payment?.channel) }}</dd>
                            </div>
                            <div>
                                <dt>Transaction</dt>
                                <dd>{{ order.payment?.transaction_id || "-" }}</dd>
                            </div>
                            <div>
                                <dt>Intention</dt>
                                <dd>{{ order.payment?.intention_id || "-" }}</dd>
                            </div>
                            <div>
                                <dt>Paid at</dt>
                                <dd>{{ formatDate(order.payment?.paid_at) }}</dd>
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

                    <article v-if="canManageOrders" class="payment-card admin-grid__item--12">
                        <div class="payment-card__header">
                            <h3>Fulfillment actions</h3>
                            <a
                                class="btn-admin btn-admin--soft"
                                :href="OrderService.invoiceUrl(order.id)"
                                target="_blank"
                                rel="noopener"
                            >
                                <i class="bi bi-file-earmark-arrow-down"></i>
                                Invoice
                            </a>
                        </div>
                        <div class="fulfillment-grid">
                            <label class="admin-label">
                                Order status
                                <select v-model="orderStatus" class="form-select admin-control">
                                    <option v-for="status in orderStatuses" :key="status" :value="status">{{ status }}</option>
                                </select>
                            </label>
                            <label class="admin-label">
                                Order shipping status
                                <select v-model="orderShippingStatus" class="form-select admin-control">
                                    <option v-for="status in orderShippingStatuses" :key="status" :value="status">{{ status }}</option>
                                </select>
                            </label>
                            <label class="admin-label">
                                Shipment status
                                <select v-model="shipmentStatus" class="form-select admin-control">
                                    <option v-for="status in shipmentStatuses" :key="status" :value="status">{{ status }}</option>
                                </select>
                            </label>
                            <label class="admin-label">
                                Tracking number
                                <input v-model.trim="trackingNumber" class="form-control admin-control" />
                            </label>
                            <label class="admin-label">
                                Tracking URL
                                <input v-model.trim="trackingUrl" class="form-control admin-control" type="url" />
                            </label>
                        </div>
                        <div class="fulfillment-actions">
                            <button class="btn-admin btn-admin--primary" :disabled="updating" @click="updateOrderState">Save statuses</button>
                            <button class="btn-admin btn-admin--soft" :disabled="updating" @click="createShipment">Create shipment</button>
                            <button class="btn-admin btn-admin--soft" :disabled="updating" @click="updateShipment">Save shipment</button>
                            <button class="btn-admin btn-admin--soft" :disabled="updating" @click="trackShipment">Track</button>
                            <button class="btn-admin btn-admin--soft" :disabled="updating" @click="buyLabel">Buy label</button>
                            <RouterLink to="/admin/returns" class="btn-admin btn-admin--soft">Returns and refunds</RouterLink>
                        </div>
                    </article>
                </section>

                <section class="admin-panel">
                    <div class="admin-panel__header">
                        <div>
                            <h3 class="admin-panel__title">Items list</h3>
                            <p class="admin-panel__meta">Products captured when the order was created.</p>
                        </div>
                        <strong class="payment-grand-total">{{ formatMoney(order.total, order.currency) }}</strong>
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
                                        <td class="text-end payments-total">{{ formatMoney(item.price, order.currency) }}</td>
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
const orderStatus = ref("pending");
const orderShippingStatus = ref("pending");
const shipmentStatus = ref("pending");
const trackingNumber = ref("");
const trackingUrl = ref("");
const orderStatuses = ["pending", "confirmed", "processing", "completed", "cancelled"];
const orderShippingStatuses = ["pending", "packed", "shipped", "in_transit", "delivered", "returned", "cancelled"];
const shipmentStatuses = ["pending", "processing", "label_created", "shipped", "in_transit", "delivered", "failed", "returned", "cancelled"];

const fetchOrder = async () => {
    loading.value = true;

    try {
        const payload = await OrderService.getOrder(route.params.id);
        order.value = payload.data;
        selectedStatus.value = order.value?.payment_status || "pending";
        orderStatus.value = order.value?.order_status || order.value?.status || "pending";
        orderShippingStatus.value = order.value?.shipping_status || "pending";
        shipmentStatus.value = order.value?.shipping?.shipment_status || order.value?.shipping_status || "pending";
        trackingNumber.value = order.value?.shipping?.tracking_number || "";
        trackingUrl.value = order.value?.shipping?.tracking_url || "";
    } catch (error) {
        toastr.error("Unable to load order details.");
    } finally {
        loading.value = false;
    }
};

const updateOrderState = async () => {
    if (!order.value) return;
    updating.value = true;
    try {
        await Promise.all([
            OrderService.updateOrderStatus(order.value.id, { order_status: orderStatus.value }),
            OrderService.updateShippingStatus(order.value.id, { shipping_status: orderShippingStatus.value }),
        ]);
        toastr.success("Order statuses updated.");
        await fetchOrder();
    } catch {
        toastr.error("Unable to update order statuses.");
    } finally {
        updating.value = false;
    }
};

const createShipment = async () => {
    if (!order.value) return;
    updating.value = true;
    try {
        await OrderService.createShipment(order.value.id, { provider: order.value.shipping?.provider || "manual" });
        toastr.success("Shipment created.");
        await fetchOrder();
    } catch {
        toastr.error("Unable to create shipment.");
    } finally {
        updating.value = false;
    }
};

const updateShipment = async () => {
    if (!order.value) return;
    updating.value = true;
    try {
        await OrderService.updateShipmentStatus(order.value.id, {
            shipment_status: shipmentStatus.value,
            tracking_number: trackingNumber.value || null,
            tracking_url: trackingUrl.value || null,
        });
        toastr.success("Shipment updated.");
        await fetchOrder();
    } catch {
        toastr.error("Unable to update shipment.");
    } finally {
        updating.value = false;
    }
};

const trackShipment = async () => {
    if (!order.value) return;
    try {
        await OrderService.trackShipment(order.value.id);
        toastr.success("Tracking request completed.");
    } catch {
        toastr.error("Tracking is unavailable for this shipment.");
    }
};

const buyLabel = async () => {
    if (!order.value) return;
    try {
        await OrderService.buyLabel(order.value.id);
        toastr.success("Shipment label created.");
        await fetchOrder();
    } catch {
        toastr.error("Label purchase is unavailable. Configure EasyPost or use manual shipment.");
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

const formatMoney = (value, currency = "EGP") => new Intl.NumberFormat("en-US", {
    style: "currency",
    currency,
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

.fulfillment-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.8rem;
}

.fulfillment-actions {
    display: flex;
    gap: 0.55rem;
    flex-wrap: wrap;
    margin-top: 1rem;
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

    .fulfillment-grid {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 575.98px) {
    .payment-facts div {
        grid-template-columns: 1fr;
        gap: 0.25rem;
    }

    .fulfillment-grid {
        grid-template-columns: 1fr;
    }
}
</style>
