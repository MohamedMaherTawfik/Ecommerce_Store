<template>
    <AdminLayout>
        <div class="admin-page">
            <section class="admin-page-header">
                <div class="admin-page-copy">
                    <div class="admin-page-kicker"><i class="bi bi-boxes"></i><span>Inventory</span></div>
                    <h2 class="admin-page-title">Stock management</h2>
                    <p class="admin-page-description">Review low and out-of-stock products and update stock levels.</p>
                </div>
            </section>

            <section class="admin-panel">
                <div class="admin-panel__body">
                    <div class="inventory-tabs">
                        <button class="btn-admin" :class="view === 'low' ? 'btn-admin--primary' : 'btn-admin--soft'" @click="load('low')">Low stock</button>
                        <button class="btn-admin" :class="view === 'out' ? 'btn-admin--primary' : 'btn-admin--soft'" @click="load('out')">Out of stock</button>
                    </div>

                    <div class="admin-table-wrap">
                        <table class="admin-table">
                            <thead><tr><th>Product</th><th>SKU</th><th>Stock</th><th>Threshold</th><th>Status</th><th></th></tr></thead>
                            <tbody>
                                <tr v-for="product in products" :key="product.id">
                                    <td>{{ product.name }}</td>
                                    <td>{{ product.sku || '-' }}</td>
                                    <td><input v-model.number="drafts[product.id].stock_quantity" class="form-control admin-control compact" type="number" min="0" /></td>
                                    <td><input v-model.number="drafts[product.id].low_stock_threshold" class="form-control admin-control compact" type="number" min="0" /></td>
                                    <td>
                                        <select v-model="drafts[product.id].stock_status" class="form-select admin-control compact">
                                            <option value="in_stock">In stock</option>
                                            <option value="low_stock">Low stock</option>
                                            <option value="out_of_stock">Out of stock</option>
                                        </select>
                                    </td>
                                    <td><button class="btn-admin btn-admin--primary" @click="save(product.id)">Save</button></td>
                                </tr>
                                <tr v-if="!products.length"><td colspan="6">No products in this inventory view.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import toastr from 'toastr';
import api from '@/services/AdminApiClient';
import AdminLayout from '@/views/admin/layout/AdminLayout.vue';

const view = ref('low');
const products = ref([]);
const drafts = reactive({});

const hydrateDrafts = () => {
    products.value.forEach((product) => {
        drafts[product.id] = {
            stock_quantity: product.stock_quantity ?? 0,
            low_stock_threshold: product.low_stock_threshold ?? 0,
            stock_status: product.stock_status || 'in_stock',
            manage_stock: product.manage_stock ?? true,
            allow_backorder: product.allow_backorder ?? false,
        };
    });
};

const load = async (next = view.value) => {
    view.value = next;
    const endpoint = next === 'out' ? '/admin/inventory/out-of-stock' : '/admin/inventory/low-stock';
    const response = await api.get(endpoint);
    products.value = response.data?.data?.data || response.data?.data || [];
    hydrateDrafts();
};

const save = async (id) => {
    try {
        await api.patch(`/admin/products/${id}/stock`, drafts[id]);
        toastr.success('Stock updated.');
        await load();
    } catch (error) {
        toastr.error(error.response?.data?.message || 'Unable to update stock.');
    }
};

onMounted(() => load());
</script>

<style scoped>
.inventory-tabs {
    display: flex;
    gap: 0.6rem;
    margin-bottom: 1rem;
}
.compact {
    min-width: 96px;
}
</style>
