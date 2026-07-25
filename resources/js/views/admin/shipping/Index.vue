<template>
    <AdminLayout>
        <div class="admin-page">
            <section class="admin-page-header">
                <div class="admin-page-copy">
                    <div class="admin-page-kicker"><i class="bi bi-truck"></i><span>Shipping</span></div>
                    <h2 class="admin-page-title">Shipping configuration</h2>
                    <p class="admin-page-description">Manage shipping methods, zones, and customer-facing rates.</p>
                </div>
            </section>

            <section class="admin-grid">
                <article class="admin-grid__item--4 admin-panel">
                    <div class="admin-panel__body admin-form">
                        <h3>Method</h3>
                        <input v-model.trim="method.name" class="form-control admin-control" placeholder="Name" />
                        <select v-model="method.code" class="form-select admin-control">
                            <option value="manual">Manual</option>
                            <option value="flat_rate">Flat rate</option>
                            <option value="free_shipping">Free shipping</option>
                            <option value="local_pickup">Local pickup</option>
                            <option value="easypost">EasyPost</option>
                        </select>
                        <label class="admin-check"><input v-model="method.is_active" type="checkbox" /> Active</label>
                        <button class="btn-admin btn-admin--primary" :disabled="busy" @click="saveMethod">Save method</button>
                    </div>
                </article>

                <article class="admin-grid__item--4 admin-panel">
                    <div class="admin-panel__body admin-form">
                        <h3>Zone</h3>
                        <input v-model.trim="zone.name" class="form-control admin-control" placeholder="Name" />
                        <input v-model.trim="zone.country" class="form-control admin-control" placeholder="Country" />
                        <input v-model.trim="zone.city" class="form-control admin-control" placeholder="City" />
                        <label class="admin-check"><input v-model="zone.is_active" type="checkbox" /> Active</label>
                        <button class="btn-admin btn-admin--primary" :disabled="busy" @click="saveZone">Save zone</button>
                    </div>
                </article>

                <article class="admin-grid__item--4 admin-panel">
                    <div class="admin-panel__body admin-form">
                        <h3>Rate</h3>
                        <input v-model.trim="rate.name" class="form-control admin-control" placeholder="Name" />
                        <select v-model="rate.shipping_method_id" class="form-select admin-control">
                            <option value="">Method</option>
                            <option v-for="item in methods" :key="item.id" :value="item.id">{{ item.name }}</option>
                        </select>
                        <select v-model="rate.shipping_zone_id" class="form-select admin-control">
                            <option value="">All zones</option>
                            <option v-for="item in zones" :key="item.id" :value="item.id">{{ item.name }}</option>
                        </select>
                        <input v-model.number="rate.rate" class="form-control admin-control" type="number" min="0" step="0.01" placeholder="Rate" />
                        <label class="admin-check"><input v-model="rate.is_active" type="checkbox" /> Active</label>
                        <button class="btn-admin btn-admin--primary" :disabled="busy" @click="saveRate">Save rate</button>
                    </div>
                </article>
            </section>

            <section class="admin-grid mt-4">
                <article class="admin-grid__item--4 admin-panel">
                    <div class="admin-panel__body"><h3>Methods</h3><ListTable :items="methods" @remove="removeMethod" /></div>
                </article>
                <article class="admin-grid__item--4 admin-panel">
                    <div class="admin-panel__body"><h3>Zones</h3><ListTable :items="zones" @remove="removeZone" /></div>
                </article>
                <article class="admin-grid__item--4 admin-panel">
                    <div class="admin-panel__body"><h3>Rates</h3><ListTable :items="rates" @remove="removeRate" /></div>
                </article>
            </section>
        </div>
    </AdminLayout>
</template>

<script setup>
import { defineComponent, h, onMounted, reactive, ref } from 'vue';
import toastr from 'toastr';
import api from '@/services/AdminApiClient';
import AdminLayout from '@/views/admin/layout/AdminLayout.vue';

const busy = ref(false);
const methods = ref([]);
const zones = ref([]);
const rates = ref([]);
const method = reactive({ name: '', code: 'manual', is_active: true });
const zone = reactive({ name: '', country: 'Egypt', city: '', is_active: true });
const rate = reactive({ name: '', shipping_method_id: '', shipping_zone_id: '', rate: 0, is_active: true });

const rows = (payload) => payload?.data?.data || payload?.data || [];
const load = async () => {
    const [methodResponse, zoneResponse, rateResponse] = await Promise.all([
        api.get('/admin/shipping/methods'),
        api.get('/admin/shipping/zones'),
        api.get('/admin/shipping/rates'),
    ]);
    methods.value = rows(methodResponse.data);
    zones.value = rows(zoneResponse.data);
    rates.value = rows(rateResponse.data);
};

const save = async (url, payload, reset) => {
    busy.value = true;
    try {
        await api.post(url, payload);
        reset();
        await load();
        toastr.success('Saved successfully.');
    } catch (error) {
        toastr.error(error.response?.data?.message || 'Unable to save.');
    } finally {
        busy.value = false;
    }
};

const saveMethod = () => save('/admin/shipping/methods', method, () => Object.assign(method, { name: '', code: 'manual', is_active: true }));
const saveZone = () => save('/admin/shipping/zones', zone, () => Object.assign(zone, { name: '', country: 'Egypt', city: '', is_active: true }));
const saveRate = () => save('/admin/shipping/rates', { ...rate, shipping_zone_id: rate.shipping_zone_id || null }, () => Object.assign(rate, { name: '', shipping_method_id: '', shipping_zone_id: '', rate: 0, is_active: true }));
const removeMethod = async (id) => { await api.delete(`/admin/shipping/methods/${id}`); await load(); };
const removeZone = async (id) => { await api.delete(`/admin/shipping/zones/${id}`); await load(); };
const removeRate = async (id) => { await api.delete(`/admin/shipping/rates/${id}`); await load(); };

const ListTable = defineComponent({
    props: { items: { type: Array, default: () => [] } },
    emits: ['remove'],
    setup(props, { emit }) {
        return () => h('div', { class: 'admin-list' }, props.items.length
            ? props.items.map((item) => h('div', { class: 'admin-list-row', key: item.id }, [
                h('span', item.name),
                h('button', { class: 'btn-admin btn-admin--soft', onClick: () => emit('remove', item.id) }, 'Delete'),
            ]))
            : h('p', { class: 'admin-muted' }, 'No records yet.'));
    },
});

onMounted(load);
</script>

<style scoped>
.admin-form,
.admin-list {
    display: grid;
    gap: 0.7rem;
}
.admin-check {
    display: flex;
    align-items: center;
    gap: 0.45rem;
}
.admin-list-row {
    display: flex;
    justify-content: space-between;
    gap: 0.7rem;
    align-items: center;
    border-bottom: 1px solid var(--admin-border);
    padding: 0.6rem 0;
}
.admin-muted {
    color: var(--admin-muted);
}
</style>
