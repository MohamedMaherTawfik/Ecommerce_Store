<template>
    <AdminLayout>
        <Transition appear name="page-fade">
            <div class="admin-page">
                <section class="admin-page-header">
                    <div class="admin-page-copy">
                        <div class="admin-page-kicker">
                            <i class="bi bi-people"></i>
                            <span>Users</span>
                        </div>
                        <h2 class="admin-page-title">Manage users with more clarity</h2>
                        <p class="admin-page-description">
                            Review account details, jump to edit or detail views, and keep actions tidy
                            in one modern table.
                        </p>
                    </div>

                    <div v-if="canManageUsers" class="admin-page-actions">
                        <RouterLink to="/admin/users/create" class="btn-admin btn-admin--primary">
                            <i class="bi bi-plus-lg"></i>
                            <span>Create User</span>
                        </RouterLink>
                    </div>
                </section>

                <section class="admin-panel">
                    <div class="admin-panel__header">
                        <div>
                            <h3 class="admin-panel__title">User Directory</h3>
                            <p class="admin-panel__meta">Browse, inspect, and manage user records.</p>
                        </div>
                        <span class="admin-pill">
                            <i class="bi bi-database"></i>
                            <span>{{ users.length }} visible</span>
                        </span>
                    </div>

                    <div class="admin-panel__body">
                        <div v-if="loading" class="admin-skeleton-panel">
                            <div class="admin-skeleton-line admin-skeleton-line--lg"></div>
                            <div class="admin-skeleton-line admin-skeleton-line--md"></div>
                            <div class="admin-skeleton-line"></div>
                            <div class="admin-skeleton-line admin-skeleton-line--sm"></div>
                        </div>

                        <div v-else-if="users.length === 0" class="admin-empty-state">
                            <div class="admin-empty-state__icon">
                                <i class="bi bi-person-plus"></i>
                            </div>
                            <h3 class="admin-empty-state__title">No users yet</h3>
                            <p class="admin-empty-state__text">
                                Once users start appearing, their details and actions will be available here.
                            </p>
                        </div>

                        <div v-else class="admin-table-wrap">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>User</th>
                                        <th>Phone</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="user in users" :key="user.id">
                                        <td class="admin-table__primary">#{{ user.id }}</td>
                                        <td>
                                            <div class="admin-table__primary">{{ user.name || "-" }}</div>
                                            <div class="admin-table__secondary">{{ user.email || "-" }}</div>
                                        </td>
                                        <td>{{ user.phone || "-" }}</td>
                                        <td>{{ user.role || "user" }}</td>
                                        <td>
                                            <span
                                                class="admin-pill"
                                                :class="user.is_active ? 'admin-pill--success' : 'admin-pill--danger'"
                                            >
                                                <i class="bi" :class="user.is_active ? 'bi-check2-circle' : 'bi-slash-circle'"></i>
                                                <span>{{ user.is_active ? "Active" : "Inactive" }}</span>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <div class="admin-actions">
                                                <RouterLink
                                                    :to="`/admin/users/${user.id}`"
                                                    class="btn-admin btn-admin--soft btn-admin--sm"
                                                >
                                                    <i class="bi bi-eye"></i>
                                                    <span>View</span>
                                                </RouterLink>
                                                <RouterLink
                                                    v-if="canManageUsers"
                                                    :to="`/admin/users/${user.id}/edit`"
                                                    class="btn-admin btn-admin--outline btn-admin--sm"
                                                >
                                                    <i class="bi bi-pencil-square"></i>
                                                    <span>Edit</span>
                                                </RouterLink>
                                                <button
                                                    v-if="canManageUsers"
                                                    type="button"
                                                    class="btn-admin btn-admin--danger btn-admin--sm"
                                                    :disabled="deletingId === user.id"
                                                    @click="handleDelete(user.id)"
                                                >
                                                    <i class="bi bi-trash3"></i>
                                                    <span>{{ deletingId === user.id ? "Deleting..." : "Delete" }}</span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <section v-if="pagination.lastPage > 1" class="admin-pagination">
                    <p class="admin-pagination__meta">
                        Page {{ pagination.currentPage }} of {{ pagination.lastPage }}
                    </p>

                    <div class="admin-actions">
                        <button
                            type="button"
                            class="btn-admin btn-admin--soft btn-admin--sm"
                            :disabled="loading || pagination.currentPage <= 1"
                            @click="fetchUsers(pagination.currentPage - 1)"
                        >
                            <i class="bi bi-arrow-left"></i>
                            <span>Previous</span>
                        </button>
                        <button
                            type="button"
                            class="btn-admin btn-admin--soft btn-admin--sm"
                            :disabled="loading || pagination.currentPage >= pagination.lastPage"
                            @click="fetchUsers(pagination.currentPage + 1)"
                        >
                            <span>Next</span>
                            <i class="bi bi-arrow-right"></i>
                        </button>
                    </div>
                </section>
            </div>
        </Transition>
    </AdminLayout>
</template>

<script setup>
import { onMounted, reactive, ref } from "vue";
import { RouterLink } from "vue-router";
import { hasAdminPermission } from "@/config/adminAccess";
import { getUserData } from "@/services/auth/authSession";
import AdminLayout from "@/views/admin/layout/AdminLayout.vue";
import userService from "@/services/admin/users/userService";

const canManageUsers = hasAdminPermission(getUserData() || {}, "users.manage");
const users = ref([]);
const loading = ref(false);
const deletingId = ref(null);
const pagination = reactive({
    currentPage: 1,
    lastPage: 1,
    total: 0,
});

const getCollection = (payload) => {
    if (Array.isArray(payload?.data?.data)) {
        return payload.data.data;
    }

    if (Array.isArray(payload?.data)) {
        return payload.data;
    }

    return [];
};

const syncPagination = (payload) => {
    const source = payload?.data;
    pagination.currentPage = source?.current_page ?? 1;
    pagination.lastPage = source?.last_page ?? 1;
    pagination.total = source?.total ?? users.value.length;
};

const fetchUsers = async (page = 1) => {
    loading.value = true;

    try {
        const response = await userService.getAllUsers({ page });
        users.value = getCollection(response);
        syncPagination(response);
    } catch (error) {
        console.error("Failed to fetch users:", error);
    } finally {
        loading.value = false;
    }
};

const handleDelete = async (id) => {
    if (!window.confirm("Are you sure you want to delete this user?")) {
        return;
    }

    deletingId.value = id;

    try {
        await userService.deleteUser(id);
        await fetchUsers(pagination.currentPage);
    } catch (error) {
        console.error(`Failed to delete user ${id}:`, error);
    } finally {
        deletingId.value = null;
    }
};

onMounted(() => {
    fetchUsers();
});
</script>
