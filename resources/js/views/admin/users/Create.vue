<template>
    <AdminLayout>
        <Transition appear name="page-fade">
            <div class="admin-page">
                <section class="admin-page-header">
                    <div class="admin-page-copy">
                        <div class="admin-page-kicker">
                            <i class="bi bi-person-plus"></i>
                            <span>Create User</span>
                        </div>
                        <h2 class="admin-page-title">Add a new user record</h2>
                        <p class="admin-page-description">
                            Keep the form focused and readable while preserving the current submission flow.
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
                            <h3 class="admin-panel__title">User Information</h3>
                            <p class="admin-panel__meta">Fill in the details below to create a new account.</p>
                        </div>
                    </div>

                    <div class="admin-panel__body">
                        <form class="admin-form-grid" @submit.prevent="handleSubmit">
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
                                <label for="password" class="admin-label">
                                    <i class="bi bi-shield-lock"></i>
                                    <span>Password</span>
                                </label>
                                <input
                                    id="password"
                                    v-model="form.password"
                                    type="password"
                                    class="form-control admin-control"
                                />
                            </div>

                            <div class="admin-field admin-field--full">
                                <label for="password_confirmation" class="admin-label">
                                    <i class="bi bi-check2-square"></i>
                                    <span>Confirm Password</span>
                                </label>
                                <input
                                    id="password_confirmation"
                                    v-model="form.password_confirmation"
                                    type="password"
                                    class="form-control admin-control"
                                />
                                <p class="admin-helper-text">
                                    Use a strong password to match the current backend validation rules.
                                </p>
                            </div>

                            <div class="admin-field admin-field--full">
                                <div class="admin-actions">
                                    <RouterLink to="/admin/users" class="btn-admin btn-admin--soft">
                                        <i class="bi bi-x-lg"></i>
                                        <span>Cancel</span>
                                    </RouterLink>
                                    <button type="submit" class="btn-admin btn-admin--primary" :disabled="submitting">
                                        <i class="bi bi-check2-circle"></i>
                                        <span>{{ submitting ? "Saving..." : "Create User" }}</span>
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
import { reactive, ref } from "vue";
import { RouterLink, useRouter } from "vue-router";
import AdminLayout from "@/views/admin/layout/AdminLayout.vue";
import userService from "@/services/admin/users/userService";

const router = useRouter();
const submitting = ref(false);
const form = reactive({
    name: "",
    email: "",
    phone: "",
    password: "",
    password_confirmation: "",
});

const handleSubmit = async () => {
    submitting.value = true;

    try {
        await userService.createUser({ ...form });
        await router.push("/admin/users");
    } catch (error) {
        console.error("Failed to create user:", error);
    } finally {
        submitting.value = false;
    }
};
</script>
