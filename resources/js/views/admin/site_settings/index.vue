<template>
    <AdminLayout>
        <Transition appear name="page-fade">
            <div class="admin-page">
                <section class="admin-page-header">
                    <div class="admin-page-copy">
                        <div class="admin-page-kicker">
                            <i class="bi bi-gear"></i>
                            <span>Site Settings</span>
                        </div>
                        <h2 class="admin-page-title">Manage Global Configuration</h2>
                    </div>
                    <div class="admin-page-actions">
                        <RouterLink to="/admin/site-settings/create" class="btn-admin btn-admin--primary">
                            <i class="bi bi-plus-lg"></i>
                            <span>Create Setting</span>
                        </RouterLink>
                    </div>
                </section>

                <section class="admin-panel">
                    <div class="admin-panel__body">
                        <div v-if="loading" class="admin-skeleton-panel">
                            <div class="admin-skeleton-line"></div>
                        </div>
                        <div v-else-if="Object.keys(items).length === 0" class="admin-empty-state">
                            <h3 class="admin-empty-state__title">No Settings</h3>
                        </div>
                        <div v-else class="admin-table-wrap">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Key</th>
                                        <th>Value</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(value, key) in items" :key="key">
                                        <td>{{ key }}</td>
                                        <td>
                                            <img v-if="['navbar_image', 'footer_image', 'register_image', 'tab_icon'].includes(key) && value" :src="value" alt="Preview" style="height: 40px; border-radius: 4px; object-fit: contain; background: #fff; padding: 2px; border: 1px solid #ccc;" />
                                            <span v-else>{{ String(value).substring(0, 50) }}</span>
                                        </td>
                                        <td class="text-end">
                                            <div class="admin-actions">
                                                <RouterLink :to="`/admin/site-settings/${key}/edit`" class="btn-admin btn-admin--outline btn-admin--sm">Edit</RouterLink>
                                                <button @click="handleDelete(key)" class="btn-admin btn-admin--danger btn-admin--sm">Delete</button>
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
import siteSettingService from "@/services/admin/siteSettingService";

const items = ref({});
const loading = ref(false);

const fetchItems = async () => {
    loading.value = true;
    try {
        const response = await siteSettingService.getAll();
        items.value = response.data?.data || response.data || {};
    } catch (e) {} finally {
        loading.value = false;
    }
};

const handleDelete = async (key) => {
    if (!window.confirm("Are you sure?")) return;
    try {
        await siteSettingService.delete(key);
        fetchItems();
    } catch (e) {}
};

onMounted(fetchItems);
</script>
