<template>
    <AdminLayout>
        <Transition appear name="page-fade">
            <div class="admin-page">
                <section class="admin-page-header">
                    <div class="admin-page-copy">
                        <div class="admin-page-kicker">
                            <i class="bi bi-link-45deg"></i>
                            <span>Nav Links</span>
                        </div>
                        <h2 class="admin-page-title">Manage Navigation Links</h2>
                    </div>
                    <div class="admin-page-actions">
                        <RouterLink to="/admin/nav-links/create" class="btn-admin btn-admin--primary">
                            <i class="bi bi-plus-lg"></i>
                            <span>Create Nav Link</span>
                        </RouterLink>
                    </div>
                </section>

                <section class="admin-panel">
                    <div class="admin-panel__body">
                        <div v-if="loading" class="admin-skeleton-panel">
                            <div class="admin-skeleton-line"></div>
                        </div>
                        <div v-else-if="items.length === 0" class="admin-empty-state">
                            <h3 class="admin-empty-state__title">No Nav Links</h3>
                        </div>
                        <div v-else class="admin-table-wrap">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Key</th>
                                        <th>Route</th>
                                        <th>Location</th>
                                        <th>Status</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in items" :key="item.id">
                                        <td>#{{ item.id }}</td>
                                        <td>{{ item.key }}</td>
                                        <td>{{ item.route }}</td>
                                        <td>{{ item.location }}</td>
                                        <td>{{ item.is_active ? 'Active' : 'Inactive' }}</td>
                                        <td class="text-end">
                                            <div class="admin-actions">
                                                <RouterLink :to="`/admin/nav-links/${item.id}/edit`" class="btn-admin btn-admin--outline btn-admin--sm">Edit</RouterLink>
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
import navLinkService from "@/services/admin/navLinkService";

const items = ref([]);
const loading = ref(false);

const fetchItems = async () => {
    loading.value = true;
    try {
        const response = await navLinkService.getAll();
        items.value = response.data?.data || response.data || [];
    } catch (e) {} finally {
        loading.value = false;
    }
};

const handleDelete = async (id) => {
    if (!window.confirm("Are you sure?")) return;
    try {
        await navLinkService.delete(id);
        fetchItems();
    } catch (e) {}
};

onMounted(fetchItems);
</script>
