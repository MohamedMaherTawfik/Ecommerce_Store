<template>
    <AdminLayout>
        <Transition appear name="page-fade">
            <div class="admin-page">
                <section class="admin-page-header">
                    <div class="admin-page-copy">
                        <div class="admin-page-kicker">
                            <i class="bi bi-shield-check"></i>
                            <span>Trust Items</span>
                        </div>
                        <h2 class="admin-page-title">Manage Trust Badges</h2>
                    </div>
                    <div class="admin-page-actions">
                        <RouterLink to="/admin/trust-items/create" class="btn-admin btn-admin--primary">
                            <i class="bi bi-plus-lg"></i>
                            <span>Create Trust Item</span>
                        </RouterLink>
                    </div>
                </section>

                <section class="admin-panel">
                    <div class="admin-panel__body">
                        <div v-if="loading" class="admin-skeleton-panel">
                            <div class="admin-skeleton-line"></div>
                        </div>
                        <div v-else-if="items.length === 0" class="admin-empty-state">
                            <h3 class="admin-empty-state__title">No Trust Items</h3>
                        </div>
                        <div v-else class="admin-table-wrap">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Label</th>
                                        <th>Icon</th>
                                        <th>Sub</th>
                                        <th>Status</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in items" :key="item.id">
                                        <td>#{{ item.id }}</td>
                                        <td>{{ item.label }}</td>
                                        <td><i :class="item.icon"></i> {{ item.icon }}</td>
                                        <td>{{ item.sub }}</td>
                                        <td>{{ item.is_active ? 'Active' : 'Inactive' }}</td>
                                        <td class="text-end">
                                            <div class="admin-actions">
                                                <RouterLink :to="`/admin/trust-items/${item.id}/edit`" class="btn-admin btn-admin--outline btn-admin--sm">Edit</RouterLink>
                                                <button @click="handleDelete(item.id)" class="btn-admin btn-admin--danger btn-admin--sm">Delete</button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </div>
        </Transition>
    </AdminLayout>
</template>

<script setup>
import { onMounted, ref } from "vue";
import { RouterLink } from "vue-router";
import AdminLayout from "@/views/admin/layout/AdminLayout.vue";
import trustItemService from "@/services/admin/trustItemService";

const items = ref([]);
const loading = ref(false);

const fetchItems = async () => {
    loading.value = true;
    try {
        const response = await trustItemService.getAll();
        items.value = response.data?.data || response.data || [];
    } catch (e) {} finally {
        loading.value = false;
    }
};

const handleDelete = async (id) => {
    if (!window.confirm("Are you sure?")) return;
    try {
        await trustItemService.delete(id);
        fetchItems();
    } catch (e) {}
};

onMounted(fetchItems);
</script>
