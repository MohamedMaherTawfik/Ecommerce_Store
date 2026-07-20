<template>
    <AdminLayout>
        <div class="admin-page">
            <section class="admin-page-header">
                <div class="admin-page-copy">
                    <div class="admin-page-kicker"><i class="bi bi-graph-up"></i><span>Overview</span></div>
                    <h2 class="admin-page-title">Store performance</h2>
                    <p class="admin-page-description">Revenue, orders, customers and product performance from live data.</p>
                </div>
            </section>

            <section class="admin-panel mb-4">
                <div class="admin-panel__body d-flex gap-3 flex-wrap align-items-end">
                    <label>Range
                        <select v-model="filters.period" class="form-select admin-control" @change="clearCustom">
                            <option value="daily">Today</option><option value="weekly">Week</option>
                            <option value="monthly">Month</option><option value="yearly">Year</option>
                        </select>
                    </label>
                    <label>From<input v-model="filters.date_from" type="date" class="form-control admin-control" /></label>
                    <label>To<input v-model="filters.date_to" type="date" class="form-control admin-control" /></label>
                    <button class="btn-admin btn-admin--primary" :disabled="loading" @click="fetchDashboard">Apply</button>
                </div>
            </section>

            <section class="admin-grid">
                <div v-for="card in cards" :key="card.title" class="admin-grid__item--3">
                    <div class="admin-stat-card">
                        <div class="admin-stat-card__label">{{ card.title }}</div>
                        <div class="admin-stat-card__value">{{ loading ? "..." : card.value }}</div>
                    </div>
                </div>
            </section>

            <section class="admin-grid mt-4">
                <article v-for="chart in charts" :key="chart.title" class="admin-grid__item--6 admin-panel">
                    <div class="admin-panel__body chart-panel">
                        <h3>{{ chart.title }}</h3>
                        <LineChart :data="chart.data" :options="chartOptions" />
                    </div>
                </article>
            </section>

            <section class="admin-grid mt-4">
                <article class="admin-grid__item--6 admin-panel">
                    <div class="admin-panel__body">
                        <h3>Top products</h3>
                        <div class="admin-table-wrap"><table class="admin-table">
                            <thead><tr><th>Product</th><th>Sold</th><th>Revenue</th></tr></thead>
                            <tbody><tr v-for="item in data.best_selling_products || []" :key="item.product_id">
                                <td>{{ item.name }}</td><td>{{ item.total_sold }}</td><td>{{ money(item.revenue) }}</td>
                            </tr></tbody>
                        </table></div>
                    </div>
                </article>
                <article class="admin-grid__item--6 admin-panel">
                    <div class="admin-panel__body">
                        <h3>Top customers</h3>
                        <div class="admin-table-wrap"><table class="admin-table">
                            <thead><tr><th>Customer</th><th>Orders</th><th>Spent</th></tr></thead>
                            <tbody><tr v-for="item in data.top_customers || []" :key="item.user_id">
                                <td>{{ item.name }}</td><td>{{ item.total_orders }}</td><td>{{ money(item.total_spent) }}</td>
                            </tr></tbody>
                        </table></div>
                    </div>
                </article>
            </section>
        </div>
    </AdminLayout>
</template>

<script setup>
import { computed, onMounted, reactive, ref, defineAsyncComponent } from "vue";
import AdminLayout from "./layout/AdminLayout.vue";
import dashboardService from "@/services/admin/dashboardService";

const LineChart = defineAsyncComponent(() => import('@/components/admin/LineChart.vue'));
const loading = ref(false);
const data = ref({});
const filters = reactive({ period: "monthly", date_from: "", date_to: "" });
const chartOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } };
const money = (value) => new Intl.NumberFormat(undefined, { style: "currency", currency: "USD" }).format(value || 0);
const cards = computed(() => [
    { title: "Revenue", value: money(data.value.total_sales) },
    { title: "Orders", value: data.value.orders_count || 0 },
    { title: "Customers", value: data.value.customers_count || 0 },
    { title: "Products", value: data.value.products_count || 0 },
]);
const chartData = (items, color) => ({
    labels: (items || []).map((item) => item.label),
    datasets: [{ data: (items || []).map((item) => item.value), borderColor: color, backgroundColor: color, tension: 0.3 }],
});
const charts = computed(() => [
    { title: "Revenue", data: chartData(data.value.revenue_chart, "#2563eb") },
    { title: "Orders", data: chartData(data.value.orders_chart, "#7c3aed") },
    { title: "Items sold", data: chartData(data.value.sales_chart, "#16a34a") },
    { title: "Customer growth", data: chartData(data.value.customer_growth_chart, "#ea580c") },
]);
const clearCustom = () => { filters.date_from = ""; filters.date_to = ""; };
const fetchDashboard = async () => {
    loading.value = true;
    try {
        const params = Object.fromEntries(Object.entries(filters).filter(([, value]) => value));
        data.value = (await dashboardService.statistics(params)).data || {};
    } finally { loading.value = false; }
};
onMounted(fetchDashboard);
</script>

<style scoped>
.chart-panel { height: 360px; }
.chart-panel h3 { margin-bottom: 1rem; }
</style>
