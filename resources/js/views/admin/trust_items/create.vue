<template>
    <AdminLayout>
        <div class="admin-page">
            <section class="admin-page-header">
                <div class="admin-page-copy">
                    <h2 class="admin-page-title">Create Trust Item</h2>
                </div>
            </section>
            <section class="admin-panel">
                <form @submit.prevent="handleSubmit" class="admin-panel__body">
                    <div class="mb-3">
                        <label>Icon (e.g. bi-shield)</label>
                        <input v-model="form.icon" type="text" class="form-control" required />
                    </div>
                    <div class="mb-3">
                        <label>Label</label>
                        <input v-model="form.label" type="text" class="form-control" required />
                    </div>
                    <div class="mb-3">
                        <label>Sub Text</label>
                        <input v-model="form.sub" type="text" class="form-control" required />
                    </div>
                    <div class="mb-3">
                        <label>Sort Order</label>
                        <input v-model="form.sort_order" type="number" class="form-control" />
                    </div>
                    <div class="mb-3">
                        <label><input type="checkbox" v-model="form.is_active" /> Active</label>
                    </div>
                    <button type="submit" class="btn-admin btn-admin--primary">Save</button>
                    <RouterLink to="/admin/trust-items" class="btn-admin btn-admin--outline ms-2">Cancel</RouterLink>
                </form>
            </section>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import AdminLayout from "@/views/admin/layout/AdminLayout.vue";
import trustItemService from "@/services/admin/trustItemService";
import toastr from "toastr";

const router = useRouter();
const form = ref({ icon: "", label: "", sub: "", sort_order: 0, is_active: true });

const handleSubmit = async () => {
    try {
        await trustItemService.create(form.value);
        toastr.success("Created successfully");
        router.push("/admin/trust-items");
    } catch (e) {}
};
</script>
