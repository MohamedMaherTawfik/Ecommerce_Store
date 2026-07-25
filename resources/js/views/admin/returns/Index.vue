<template>
    <AdminLayout>
        <div class="admin-page">
            <section class="admin-page-header">
                <div class="admin-page-copy">
                    <div class="admin-page-kicker"><i class="bi bi-arrow-repeat"></i><span>Returns</span></div>
                    <h2 class="admin-page-title">Return processing</h2>
                    <p class="admin-page-description">Approve, reject, receive, and record Paymob refund requests.</p>
                </div>
            </section>

            <section class="admin-panel">
                <div class="admin-panel__body">
                    <div class="admin-table-wrap">
                        <table class="admin-table">
                            <thead><tr><th>Return</th><th>Order</th><th>Customer</th><th>Status</th><th>Reason</th><th>Actions</th></tr></thead>
                            <tbody>
                                <tr v-for="item in returns" :key="item.id">
                                    <td>#{{ item.id }}</td>
                                    <td>{{ item.order?.order_number || item.order_id }}</td>
                                    <td>{{ item.order?.user?.name || '-' }}</td>
                                    <td>{{ item.status }}</td>
                                    <td>{{ item.reason }}</td>
                                    <td class="actions-cell">
                                        <button class="btn-admin btn-admin--soft" @click="act(item.id, 'approve')">Approve</button>
                                        <button class="btn-admin btn-admin--soft" @click="act(item.id, 'reject')">Reject</button>
                                        <button class="btn-admin btn-admin--soft" @click="act(item.id, 'mark-received')">Received</button>
                                        <button class="btn-admin btn-admin--primary" @click="refund(item)">Refund</button>
                                    </td>
                                </tr>
                                <tr v-if="!returns.length"><td colspan="6">No return requests yet.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import toastr from 'toastr';
import api from '@/services/AdminApiClient';
import AdminLayout from '@/views/admin/layout/AdminLayout.vue';

const returns = ref([]);

const load = async () => {
    const response = await api.get('/admin/returns');
    returns.value = response.data?.data?.data || response.data?.data || [];
};

const act = async (id, action) => {
    try {
        await api.post(`/admin/returns/${id}/${action}`, { admin_note: '' });
        toastr.success('Return updated.');
        await load();
    } catch (error) {
        toastr.error(error.response?.data?.message || 'Unable to update return.');
    }
};

const refund = async (item) => {
    try {
        await api.post(`/admin/returns/${item.id}/refund`, {
            amount: item.order?.total || 0,
            admin_note: 'Refund recorded for Paymob dashboard processing.',
        });
        toastr.success('Refund request recorded.');
        await load();
    } catch (error) {
        toastr.error(error.response?.data?.message || 'Unable to record refund.');
    }
};

onMounted(load);
</script>

<style scoped>
.actions-cell {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
}
</style>
