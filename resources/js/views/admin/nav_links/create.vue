<template>
    <AdminLayout>
        <div class="admin-page">
            <section class="admin-page-header">
                <div class="admin-page-copy">
                    <h2 class="admin-page-title">Create Nav Link</h2>
                </div>
            </section>
            <section class="admin-panel">
                <form @submit.prevent="handleSubmit" class="admin-panel__body">
                    <div class="mb-3">
                        <label>Key</label>
                        <input v-model="form.key" type="text" class="form-control" required />
                    </div>
                    <div class="mb-3">
                        <label>Route URL</label>
                        <input v-model="form.route" type="text" class="form-control" required />
                    </div>
                    <div class="mb-3">
                        <label>Icon</label>
                        <input v-model="form.icon" type="text" class="form-control" />
                    </div>
                    <div class="mb-3">
                        <label>Location</label>
                        <select v-model="form.location" class="form-control" required>
                            <option value="navbar">Navbar</option>
                            <option value="footer_quick">Footer Quick Links</option>
                            <option value="footer_support">Footer Support Links</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Sort Order</label>
                        <input v-model="form.sort_order" type="number" class="form-control" />
                    </div>
                    <div class="mb-3">
                        <label><input type="checkbox" v-model="form.is_active" /> Active</label>
                    </div>
                    <button type="submit" class="btn-admin btn-admin--primary">Save</button>
                    <RouterLink to="/admin/nav-links" class="btn-admin btn-admin--outline ms-2">Cancel</RouterLink>
                </form>
            </section>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import AdminLayout from "@/views/admin/layout/AdminLayout.vue";
import navLinkService from "@/services/admin/navLinkService";
import toastr from "toastr";

const router = useRouter();
const form = ref({ key: "", route: "", icon: "", location: "navbar", sort_order: 0, is_active: true });

const handleSubmit = async () => {
    try {
        await navLinkService.create(form.value);
        toastr.success("Created successfully");
        router.push("/admin/nav-links");
    } catch (e) {}
};
</script>
