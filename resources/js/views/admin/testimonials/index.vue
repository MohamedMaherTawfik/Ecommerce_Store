<template>
    <AdminLayout>
        <Transition appear name="page-fade">
            <div class="admin-page">
                <section class="admin-page-header">
                    <div class="admin-page-copy">
                        <div class="admin-page-kicker">
                            <i class="bi bi-chat-quote"></i>
                            <span>Testimonials</span>
                        </div>
                        <h2 class="admin-page-title">Manage Testimonials</h2>
                    </div>
                    <div class="admin-page-actions">
                        <RouterLink to="/admin/testimonials/create" class="btn-admin btn-admin--primary">
                            <i class="bi bi-plus-lg"></i>
                            <span>Create Testimonial</span>
                        </RouterLink>
                    </div>
                </section>

                <section class="admin-panel">
                    <div class="admin-panel__body">
                        <div v-if="loading" class="admin-skeleton-panel">
                            <div class="admin-skeleton-line"></div>
                        </div>
                        <div v-else-if="items.length === 0" class="admin-empty-state">
                            <h3 class="admin-empty-state__title">No Testimonials</h3>
                        </div>
                        <div v-else class="admin-table-wrap">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Role</th>
                                        <th>Rating</th>
                                        <th>Status</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in items" :key="item.id">
                                        <td>#{{ item.id }}</td>
                                        <td>{{ item.name }}</td>
                                        <td>{{ item.role }}</td>
                                        <td>{{ item.rating }} <i class="bi bi-star-fill text-warning"></i></td>
                                        <td>{{ item.is_active ? 'Active' : 'Inactive' }}</td>
                                        <td class="text-end">
                                            <div class="admin-actions">
                                                <RouterLink :to="`/admin/testimonials/${item.id}/edit`" class="btn-admin btn-admin--outline btn-admin--sm">Edit</RouterLink>
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
import testimonialService from "@/services/admin/testimonialService";

const items = ref([]);
const loading = ref(false);

const fetchItems = async () => {
    loading.value = true;
    try {
        const response = await testimonialService.getAll();
        items.value = response.data?.data || response.data || [];
    } catch (e) {} finally {
        loading.value = false;
    }
};

const handleDelete = async (id) => {
    if (!window.confirm("Are you sure?")) return;
    try {
        await testimonialService.delete(id);
        fetchItems();
    } catch (e) {}
};

onMounted(fetchItems);
</script>
