<template>
    <AdminLayout>
        <div class="admin-page">
            <section class="admin-page-header">
                <div class="admin-page-copy">
                    <h2 class="admin-page-title">Edit Nav Link</h2>
                </div>
            </section>
            <section class="admin-panel" v-if="!loading">
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
                    <button type="submit" class="btn-admin btn-admin--primary">Update</button>
                    <RouterLink to="/admin/nav-links" class="btn-admin btn-admin--outline ms-2">Cancel</RouterLink>
                </form>
            </section>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useRouter, useRoute } from "vue-router";
import AdminLayout from "@/views/admin/layout/AdminLayout.vue";
import navLinkService from "@/services/admin/navLinkService";
import toastr from "toastr";

const router = useRouter();
const route = useRoute();
const form = ref({});
const loading = ref(true);

onMounted(async () => {
    try {
        const res = await navLinkService.get(route.params.id);
        form.value = res.data?.data || res.data;
    } catch (e) {
        router.push("/admin/nav-links");
    } finally {
        loading.value = false;
    }
});

const handleSubmit = async () => {
    try {
        await navLinkService.update(route.params.id, form.value);
        toastr.success("Updated successfully");
        router.push("/admin/nav-links");
    } catch (e) {}
};
</script>
