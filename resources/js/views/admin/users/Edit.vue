<template>
    <AdminLayout>
        <Transition appear name="page-fade">
            <div class="admin-page">
                <section class="admin-page-header">
                    <div class="admin-page-copy">
                        <div class="admin-page-kicker">
                            <i class="bi bi-pencil-square"></i>
                            <span>Edit User</span>
                        </div>
                        <h2 class="admin-page-title">Update user details smoothly</h2>
                        <p class="admin-page-description">
                            The form keeps the same update behavior while improving readability and focus.
                        </p>
                    </div>

                    <div class="admin-page-actions">
                        <RouterLink to="/admin/users" class="btn-admin btn-admin--soft">
                            <i class="bi bi-arrow-left"></i>
                            <span>Back</span>
                        </RouterLink>
                    </div>
                </section>

                <section class="admin-panel">
                    <div class="admin-panel__header">
                        <div>
                            <h3 class="admin-panel__title">Editable User Fields</h3>
                            <p class="admin-panel__meta">Adjust profile information and optional password changes.</p>
                        </div>
                    </div>

                    <div class="admin-panel__body">
                        <div v-if="loading" class="admin-skeleton-panel">
                            <div class="admin-skeleton-line admin-skeleton-line--lg"></div>
                            <div class="admin-skeleton-line admin-skeleton-line--md"></div>
                            <div class="admin-skeleton-line"></div>
                            <div class="admin-skeleton-line admin-skeleton-line--sm"></div>
                        </div>

                        <form v-else class="admin-form-grid" @submit.prevent="handleSubmit">
                            <div class="admin-field">
                                <label for="name" class="admin-label">
                                    <i class="bi bi-person"></i>
                                    <span>Name</span>
                                </label>
                                <input id="name" v-model="form.name" type="text" class="form-control admin-control" />
                            </div>

                            <div class="admin-field">
                                <label for="email" class="admin-label">
                                    <i class="bi bi-envelope"></i>
                                    <span>Email</span>
                                </label>
                                <input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    class="form-control admin-control"
                                />
                            </div>

                            <div class="admin-field">
                                <label for="phone" class="admin-label">
                                    <i class="bi bi-telephone"></i>
                                    <span>Phone</span>
                                </label>
                                <input id="phone" v-model="form.phone" type="text" class="form-control admin-control" />
                            </div>

                            <div class="admin-field">
                                <label for="role" class="admin-label">
                                    <i class="bi bi-person-badge"></i>
                                    <span>Role</span>
                                </label>
                                <input id="role" v-model="form.role" type="text" class="form-control admin-control" />
                            </div>

                            <div class="admin-field">
                                <label for="password" class="admin-label">
                                    <i class="bi bi-shield-lock"></i>
                                    <span>New Password</span>
                                </label>
                                <input
                                    id="password"
                                    v-model="form.password"
                                    type="password"
                                    class="form-control admin-control"
                                />
                            </div>

                            <div class="admin-field">
                                <label for="password_confirmation" class="admin-label">
                                    <i class="bi bi-check2-square"></i>
                                    <span>Confirm New Password</span>
                                </label>
                                <input
                                    id="password_confirmation"
                                    v-model="form.password_confirmation"
                                    type="password"
                                    class="form-control admin-control"
                                />
                            </div>

                            <div class="admin-field admin-field--full">
                                <div class="admin-switch-grid">
                                    <label class="admin-switch">
                                        <input
                                            id="is_active"
                                            v-model="form.is_active"
                                            class="form-check-input"
                                            type="checkbox"
                                        />
                                        <span>
                                            <strong class="d-block">Active User</strong>
                                            <small class="text-muted">Toggle current account availability.</small>
                                        </span>
                                    </label>
                                </div>
                            </div>

                            <div class="admin-field admin-field--full">
                                <div class="admin-actions">
                                    <RouterLink to="/admin/users" class="btn-admin btn-admin--soft">
                                        <i class="bi bi-x-lg"></i>
                                        <span>Cancel</span>
                                    </RouterLink>
                                    <button type="submit" class="btn-admin btn-admin--primary" :disabled="submitting">
                                        <i class="bi bi-save2"></i>
                                        <span>{{ submitting ? "Saving..." : "Save Changes" }}</span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </section>
            </div>
        </Transition>
    </AdminLayout>
</template>

<script setup>
import { onMounted, reactive, ref } from "vue";
import { RouterLink, useRoute, useRouter } from "vue-router";
import AdminLayout from "@/views/admin/layout/AdminLayout.vue";
import userService from "@/services/admin/users/userService";

const route = useRoute();
const router = useRouter();
const loading = ref(true);
const submitting = ref(false);
const form = reactive({
    name: "",
    email: "",
    phone: "",
    role: "",
    is_active: false,
    password: "",
    password_confirmation: "",
});

const getRecord = (payload) => payload?.data ?? payload ?? {};

const fetchUser = async () => {
    loading.value = true;

    try {
        const response = await userService.getUserById(route.params.id);
        const user = getRecord(response);

        form.name = user.name ?? "";
        form.email = user.email ?? "";
        form.phone = user.phone ?? "";
        form.role = user.role ?? "";
        form.is_active = Boolean(user.is_active);
    } catch (error) {
        console.error(`Failed to fetch user ${route.params.id}:`, error);
    } finally {
        loading.value = false;
    }
};

const buildPayload = () => {
    const payload = {
        name: form.name,
        email: form.email,
        phone: form.phone,
        role: form.role,
        is_active: form.is_active,
    };

    if (form.password) {
        payload.password = form.password;
        payload.password_confirmation = form.password_confirmation;
    }

    return payload;
};

const handleSubmit = async () => {
    submitting.value = true;

    try {
        await userService.updateUser(route.params.id, buildPayload());
        await router.push("/admin/users");
    } catch (error) {
        console.error(`Failed to update user ${route.params.id}:`, error);
    } finally {
        submitting.value = false;
    }
};

onMounted(() => {
    fetchUser();
});
</script>
