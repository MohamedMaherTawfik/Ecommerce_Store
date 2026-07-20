<template>
    <AdminLayout>
        <div class="admin-page">
            <section class="admin-page-header">
                <div class="admin-page-copy">
                    <h2 class="admin-page-title">Create Feature</h2>
                </div>
            </section>
            <section class="admin-panel">
                <form @submit.prevent="handleSubmit" class="admin-panel__body">
                    <div class="mb-3">
                        <label>Icon</label>
                        <input v-model="form.icon" type="text" class="form-control" required />
                    </div>
                    <div class="mb-3">
                        <label>Label</label>
                        <input v-model="form.label" type="text" class="form-control" required />
                    </div>
                    <div class="mb-3">
                        <label>Text</label>
                        <textarea v-model="form.text" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label>Sort Order</label>
                        <input v-model="form.sort_order" type="number" class="form-control" />
                    </div>
                    <div class="mb-3">
                        <label><input type="checkbox" v-model="form.is_active" /> Active</label>
                    </div>
                    <button type="submit" class="btn-admin btn-admin--primary">Save</button>
                    <RouterLink to="/admin/features" class="btn-admin btn-admin--outline ms-2">Cancel</RouterLink>
                </form>
            </section>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import AdminLayout from "@/views/admin/layout/AdminLayout.vue";
import featureService from "@/services/admin/featureService";
import toastr from "toastr";

const router = useRouter();
const form = ref({ icon: "", label: "", text: "", sort_order: 0, is_active: true });

const handleSubmit = async () => {
    try {
        await featureService.create(form.value);
        toastr.success("Created successfully");
        router.push("/admin/features");
    } catch (e) {}
};
</script>
