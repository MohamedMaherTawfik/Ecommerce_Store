<template>
    <AdminLayout>
        <div class="admin-page payments-page">
            <section class="admin-page-header">
                <div class="admin-page-copy">
                    <div class="admin-page-kicker">
                        <i class="bi bi-credit-card"></i>
                        Payments
                    </div>
                    <h2 class="admin-page-title">Manage PayPal orders with confidence</h2>
                    <p class="admin-page-description">
                        Review payment status, customer details, PayPal transactions, and order totals from one focused table.
                    </p>
                </div>
            </section>

            <section class="admin-panel">
                <div class="admin-panel__header payments-filter-header">
                    <div>
                        <h3 class="admin-panel__title">Orders</h3>
                        <p class="admin-panel__meta">Filter by payment state or checkout date.</p>
                    </div>
                    <span class="admin-pill">{{ pagination.total }} total</span>
                </div>

                <div class="admin-panel__body">
                    <div class="payments-filters">
                        <label class="admin-label payments-filter">
                            Status
                            <select v-model="filters.status" class="form-select admin-control" @change="applyFilters">
                                <option value="">All statuses</option>
                                <option value="pending">Pending</option>
                                <option value="paid">Paid</option>
                                <option value="failed">Failed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </label>

                        <label class="admin-label payments-filter">
                            From
                            <input v-model="filters.date_from" type="date" class="form-control admin-control" @change="applyFilters" />
                        </label>

                        <label class="admin-label payments-filter">
                            To
                            <input v-model="filters.date_to" type="date" class="form-control admin-control" @change="applyFilters" />
                        </label>

                        <button type="button" class="btn-admin btn-admin--soft payments-clear" @click="clearFilters">
                            <i class="bi bi-x-lg"></i>
                            Clear
                        </button>
                    </div>

                    <div v-if="loading" class="payments-skeleton-table">
                        <div v-for="row in 6" :key="row" class="payments-skeleton-row">
                            <span></span>
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>

                    <div v-else-if="orders.length === 0" class="admin-empty-state">
                        <div class="admin-empty-state__icon">
                            <i class="bi bi-receipt"></i>
                        </div>
                        <h3 class="admin-empty-state__title">No orders found</h3>
                        <p class="admin-empty-state__text">
                            Adjust filters or wait for the next PayPal checkout to appear here.
                        </p>
                    </div>

                    <div v-else class="admin-table-wrap">
                        <table class="admin-table payments-table">
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Customer</th>
                                    <th>Payment</th>
                                    <th>Total</th>
                                    <th>Date</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="order in orders" :key="order.id">
                                    <td>
                                        <div class="admin-table__primary">{{ order.order_number || `#${order.id}` }}</div>
                                        <div class="admin-table__secondary">{{ order.items_count || 0 }} items</div>
                                    </td>
                                    <td>
                                        <div class="admin-table__primary">{{ order.user?.name || "Guest" }}</div>
                                        <div class="admin-table__secondary">{{ order.user?.email || order.phone || "-" }}</div>
                                    </td>
                                    <td>
                                        <span class="payment-badge" :class="statusClass(order.payment_status)">
                                            {{ labelStatus(order.payment_status) }}
                                        </span>
                                        <div class="admin-table__secondary">
                                            {{ order.paypal?.transaction_id || "No transaction yet" }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="payments-total">{{ formatMoney(order.total) }}</div>
                                    </td>
                                    <td>{{ formatDate(order.created_at) }}</td>
                                    <td>
                                        <div class="admin-actions justify-content-end">
                                            <RouterLink
                                                :to="`/admin/payments/orders/${order.id}`"
                                                class="btn-admin btn-admin--soft btn-admin--sm"
                                            >
                                                <i class="bi bi-eye"></i>
                                                View
                                            </RouterLink>
                                            <button
                                                v-if="canDeleteOrders"
                                                type="button"
                                                class="btn-admin btn-admin--danger btn-admin--sm"
                                                :disabled="deletingId === order.id"
                                                @click="deleteOrder(order)"
                                            >
                                                <i class="bi bi-trash"></i>
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section v-if="pagination.last_page > 1" class="admin-pagination">
                <p class="admin-pagination__meta">
                    Page {{ pagination.current_page }} of {{ pagination.last_page }}
                </p>
                <div class="admin-actions">
                    <button
                        type="button"
                        class="btn-admin btn-admin--soft btn-admin--sm"
                        :disabled="pagination.current_page <= 1 || loading"
                        @click="fetchOrders(pagination.current_page - 1)"
                    >
                        Previous
                    </button>
                    <button
                        type="button"
                        class="btn-admin btn-admin--soft btn-admin--sm"
                        :disabled="pagination.current_page >= pagination.last_page || loading"
                        @click="fetchOrders(pagination.current_page + 1)"
                    >
                        Next
                    </button>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>

<script setup>
import { onMounted, reactive, ref } from "vue";
import { RouterLink } from "vue-router";
import toastr from "toastr";
import { hasAdminPermission } from "@/config/adminAccess";
import { getUserData } from "@/services/auth/authSession";
import AdminLayout from "@/views/admin/layout/AdminLayout.vue";
import OrderService from "@/services/admin/payments/OrderService";

const canDeleteOrders = hasAdminPermission(getUserData() || {}, "orders.delete");
const orders = ref([]);
const loading = ref(false);
const deletingId = ref(null);

const filters = reactive({
    status: "",
    date_from: "",
    date_to: "",
});

const pagination = reactive({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0,
});

const fetchOrders = async (page = 1) => {
    loading.value = true;

    try {
        const payload = await OrderService.getOrders({
            page,
            per_page: pagination.per_page,
            ...activeFilters(),
        });

        orders.value = payload.data || [];
        Object.assign(pagination, payload.meta || {});
    } catch (error) {
        toastr.error("Unable to load orders.");
    } finally {
        loading.value = false;
    }
};

const activeFilters = () => Object.fromEntries(
    Object.entries(filters).filter(([, value]) => value !== "" && value !== null),
);

const applyFilters = () => {
    fetchOrders(1);
};

const clearFilters = () => {
    filters.status = "";
    filters.date_from = "";
    filters.date_to = "";
    fetchOrders(1);
};

const deleteOrder = async (order) => {
    if (!window.confirm(`Delete order ${order.order_number || `#${order.id}`}?`)) {
        return;
    }

    deletingId.value = order.id;

    try {
        await OrderService.deleteOrder(order.id);
        toastr.success("Order deleted.");
        fetchOrders(pagination.current_page);
    } catch (error) {
        toastr.error("Unable to delete order.");
    } finally {
        deletingId.value = null;
    }
};

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

onMounted(() => fetchOrders());
</script>

<style scoped>
.payments-page {
    gap: 1.25rem;
}

.payments-filter-header {
    align-items: center;
}

.payments-filters {
    display: grid;
    grid-template-columns: minmax(180px, 1fr) minmax(160px, 0.8fr) minmax(160px, 0.8fr) auto;
    gap: 1rem;
    align-items: end;
    margin-bottom: 1.25rem;
}

.payments-filter {
    display: flex;
    flex-direction: column;
    margin-bottom: 0;
}

.payments-clear {
    min-height: 54px;
}

.payments-table {
    min-width: 900px;
}

.payments-total {
    color: #0f172a;
    font-weight: 800;
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

.payments-skeleton-table {
    display: grid;
    gap: 0.75rem;
}

.payments-skeleton-row {
    display: grid;
    grid-template-columns: 1.2fr 1.4fr 1fr 0.8fr;
    gap: 1rem;
    padding: 1rem;
    border-radius: 18px;
    background: rgba(255, 255, 255, 0.65);
}

.payments-skeleton-row span {
    height: 16px;
    border-radius: 999px;
    background: rgba(148, 163, 184, 0.2);
}

@media (max-width: 991.98px) {
    .payments-filters {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 575.98px) {
    .payments-filters {
        grid-template-columns: 1fr;
    }
}
</style>
