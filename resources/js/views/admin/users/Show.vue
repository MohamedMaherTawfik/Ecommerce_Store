<template>
    <AdminLayout>
        <Transition appear name="page-fade">
            <div class="admin-page">
                <section class="admin-page-header">
                    <div class="admin-page-copy">
                        <div class="admin-page-kicker">
                            <i class="bi bi-eye"></i>
                            <span>User Details</span>
                        </div>
                        <h2 class="admin-page-title">A clearer view of account information</h2>
                        <p class="admin-page-description">
                            Review the selected record in a clean two-column layout with direct access to editing.
                        </p>
                    </div>

                    <div class="admin-page-actions">
                        <button type="button" class="btn-admin btn-admin--soft" @click="router.push('/admin/users')">
                            <i class="bi bi-arrow-left"></i>
                            <span>Back</span>
                        </button>
                        <button
                            v-if="canManageUsers"
                            type="button"
                            class="btn-admin btn-admin--primary"
                            @click="router.push(`/admin/users/${route.params.id}/edit`)"
                        >
                            <i class="bi bi-pencil-square"></i>
                            <span>Edit</span>
                        </button>
                    </div>
                </section>

                <section class="admin-panel">
                    <div class="admin-panel__header">
                        <div>
                            <h3 class="admin-panel__title">Profile Snapshot</h3>
                            <p class="admin-panel__meta">Every field is surfaced in a cleaner inspection layout.</p>
                        </div>
                    </div>

                    <div class="admin-panel__body">
                        <div v-if="loading" class="admin-skeleton-panel">
                            <div class="admin-skeleton-line admin-skeleton-line--lg"></div>
                            <div class="admin-skeleton-line"></div>
                            <div class="admin-skeleton-line admin-skeleton-line--md"></div>
                            <div class="admin-skeleton-line admin-skeleton-line--sm"></div>
                        </div>

                        <div v-else class="admin-detail-grid">
                            <article class="admin-detail-card" v-for="field in fields" :key="field.label">
                                <div class="admin-detail-card__label">{{ field.label }}</div>
                                <div class="admin-detail-card__value">{{ field.value }}</div>
                            </article>
                        </div>
                    </div>
                </section>
            </div>
        </Transition>
    </AdminLayout>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { hasAdminPermission } from "@/config/adminAccess";
import { getUserData } from "@/services/auth/authSession";
import AdminLayout from "@/views/admin/layout/AdminLayout.vue";
import userService from "@/services/admin/users/userService";

const route = useRoute();
const router = useRouter();
const canManageUsers = hasAdminPermission(getUserData() || {}, "users.manage");
const loading = ref(true);
const user = ref({});

const getRecord = (payload) => payload?.data ?? payload ?? {};

const fields = computed(() => [
    { label: "ID", value: user.value.id ?? "-" },
    { label: "Name", value: user.value.name ?? "-" },
    { label: "Email", value: user.value.email ?? "-" },
    { label: "Phone", value: user.value.phone ?? "-" },
    { label: "Role", value: user.value.role ?? "-" },
    { label: "Active", value: user.value.is_active ? "Yes" : "No" },
    { label: "Created At", value: user.value.created_at ?? "-" },
    { label: "Updated At", value: user.value.updated_at ?? "-" },
]);

const fetchUser = async () => {
    loading.value = true;

    try {
        const response = await userService.getUserById(route.params.id);
        user.value = getRecord(response);
    } catch (error) {
        console.error(`Failed to fetch user ${route.params.id}:`, error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchUser();
});
</script>
