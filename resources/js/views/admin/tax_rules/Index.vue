<template>
    <AdminLayout>
        <div class="admin-page">
            <section class="admin-page-header">
                <div class="admin-page-copy">
                    <div class="admin-page-kicker"><i class="bi bi-percent"></i><span>Taxes</span></div>
                    <h2 class="admin-page-title">Tax rules</h2>
                    <p class="admin-page-description">Configure tax rates used by checkout totals.</p>
                </div>
            </section>

            <section class="admin-panel">
                <div class="admin-panel__body tax-layout">
                    <form class="tax-form" @submit.prevent="save">
                        <input v-model.trim="form.name" class="form-control admin-control" required placeholder="Name" />
                        <input v-model.trim="form.country" class="form-control admin-control" placeholder="Country" />
                        <input v-model.trim="form.city" class="form-control admin-control" placeholder="City" />
                        <input v-model.number="form.rate" class="form-control admin-control" required type="number" min="0" step="0.0001" placeholder="Rate" />
                        <select v-model="form.type" class="form-select admin-control">
                            <option value="percentage">Percentage</option>
                            <option value="fixed">Fixed</option>
                        </select>
                        <input v-model.number="form.priority" class="form-control admin-control" type="number" min="0" placeholder="Priority" />
                        <label><input v-model="form.price_includes_tax" type="checkbox" /> Price includes tax</label>
                        <label><input v-model="form.applies_to_shipping" type="checkbox" /> Applies to shipping</label>
                        <label><input v-model="form.is_active" type="checkbox" /> Active</label>
                        <button class="btn-admin btn-admin--primary" :disabled="busy">Save tax rule</button>
                    </form>

                    <div class="admin-table-wrap">
                        <table class="admin-table">
                            <thead><tr><th>Name</th><th>Location</th><th>Rate</th><th>Status</th><th></th></tr></thead>
                            <tbody>
                                <tr v-for="rule in rules" :key="rule.id">
                                    <td>{{ rule.name }}</td>
                                    <td>{{ [rule.country, rule.city].filter(Boolean).join(', ') || 'All' }}</td>
                                    <td>{{ rule.rate }} {{ rule.type }}</td>
                                    <td>{{ rule.is_active ? 'Active' : 'Inactive' }}</td>
                                    <td><button class="btn-admin btn-admin--soft" @click="remove(rule.id)">Delete</button></td>
                                </tr>
                                <tr v-if="!rules.length"><td colspan="5">No tax rules yet.</td></tr>
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

const busy = ref(false);
const rules = ref([]);
const form = reactive({
    name: '',
    country: 'Egypt',
    city: '',
    rate: 0,
    type: 'percentage',
    priority: 0,
    price_includes_tax: false,
    applies_to_shipping: false,
    is_active: true,
});

const load = async () => {
    const response = await api.get('/admin/tax-rules');
    rules.value = response.data?.data?.data || response.data?.data || [];
};

const save = async () => {
    busy.value = true;
    try {
        await api.post('/admin/tax-rules', form);
        Object.assign(form, { name: '', country: 'Egypt', city: '', rate: 0, type: 'percentage', priority: 0, price_includes_tax: false, applies_to_shipping: false, is_active: true });
        await load();
        toastr.success('Tax rule saved.');
    } catch (error) {
        toastr.error(error.response?.data?.message || 'Unable to save tax rule.');
    } finally {
        busy.value = false;
    }
};

const remove = async (id) => {
    await api.delete(`/admin/tax-rules/${id}`);
    await load();
};

onMounted(load);
</script>

<style scoped>
.tax-layout,
.tax-form {
    display: grid;
    gap: 1rem;
}
.tax-form {
    grid-template-columns: repeat(3, minmax(0, 1fr));
}
.tax-form label {
    display: flex;
    gap: 0.45rem;
    align-items: center;
}
@media (max-width: 991.98px) {
    .tax-form {
        grid-template-columns: 1fr;
    }
}
</style>
