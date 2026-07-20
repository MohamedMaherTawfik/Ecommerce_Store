<template>
    <AdminLayout>
        <Transition appear name="page-fade">
            <div class="admin-page">
                <section class="admin-page-header">
                    <div class="admin-page-copy">
                        <div class="admin-page-kicker">
                            <i class="bi bi-envelope"></i>
                            <span>Contact Messages</span>
                        </div>
                        <h2 class="admin-page-title">Manage user inquiries and support requests</h2>
                    </div>
                </section>

                <section class="admin-panel">
                    <div class="admin-panel__header">
                        <div>
                            <h3 class="admin-panel__title">Messages List</h3>
                        </div>

                        <!-- Filters -->
                        <div class="d-flex gap-2">
                            <input type="text" v-model="filters.search" class="form-control form-control-sm"
                                placeholder="Search name, email, subject" @keyup.enter="fetchMessages" />
                            <select v-model="filters.status" class="form-select form-select-sm" @change="fetchMessages">
                                <option value="">All Statuses</option>
                                <option value="replied">Replied</option>
                                <option value="not_replied">Not Replied</option>
                            </select>
                            <button class="btn btn-sm btn-primary" @click="fetchMessages">Filter</button>
                        </div>
                    </div>

                    <div class="admin-panel__body">
                        <div v-if="loading" class="admin-skeleton-panel">
                            <div class="admin-skeleton-line admin-skeleton-line--lg"></div>
                            <div class="admin-skeleton-line"></div>
                            <div class="admin-skeleton-line admin-skeleton-line--md"></div>
                        </div>

                        <div v-else-if="messages.data && messages.data.length === 0" class="admin-empty-state">
                            <div class="admin-empty-state__icon">
                                <i class="bi bi-inbox"></i>
                            </div>
                            <h3 class="admin-empty-state__title">No messages found</h3>
                        </div>

                        <div v-else class="admin-table-wrap">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Subject</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="msg in messages.data" :key="msg.id">
                                        <td class="admin-table__primary">#{{ msg.id }}</td>
                                        <td>{{ msg.name }}</td>
                                        <td>{{ msg.email }}</td>
                                        <td>{{ msg.subject }}</td>
                                        <td>{{ new Date(msg.created_at).toLocaleDateString() }}</td>
                                        <td>
                                            <span v-if="msg.replied_at" class="badge bg-success">Replied</span>
                                            <span v-else class="badge bg-danger">Not Replied</span>
                                        </td>
                                        <td class="text-end">
                                            <div class="admin-actions">
                                                <RouterLink :to="`/admin/contact-messages/${msg.id}`"
                                                    class="btn-admin btn-admin--soft btn-admin--sm">
                                                    <i class="bi bi-eye"></i>
                                                    <span>View & Reply</span>
                                                </RouterLink>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- Simple Pagination Controls -->
                            <div class="d-flex justify-content-between align-items-center mt-3 p-3"
                                v-if="messages.last_page > 1">
                                <button class="btn btn-sm btn-outline-secondary" :disabled="messages.current_page === 1"
                                    @click="changePage(messages.current_page - 1)">Previous</button>
                                <span>Page {{ messages.current_page }} of {{ messages.last_page }}</span>
                                <button class="btn btn-sm btn-outline-secondary"
                                    :disabled="messages.current_page === messages.last_page"
                                    @click="changePage(messages.current_page + 1)">Next</button>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </Transition>
    </AdminLayout>
</template>

<script setup>
import { onMounted, ref, reactive } from "vue";
import { RouterLink } from "vue-router";
import AdminLayout from "@/views/admin/layout/AdminLayout.vue";
import api from "@/services/AdminApiClient";

const messages = ref({ data: [], current_page: 1, last_page: 1 });
const loading = ref(false);

const filters = reactive({
    search: '',
    status: '',
    page: 1
});

const fetchMessages = async () => {
    loading.value = true;
    try {
        const response = await api.get('/admin/contact-messages', { params: filters });
        if (response.data && response.data.data) {
            messages.value = response.data.data;
        }
    } catch (error) {
        console.error("Failed to fetch messages:", error);
    } finally {
        loading.value = false;
    }
};

const changePage = (page) => {
    filters.page = page;
    fetchMessages();
};

onMounted(() => {
    fetchMessages();
});
</script>
