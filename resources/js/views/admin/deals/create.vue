<template>
    <AdminLayout>
        <div class="admin-page">
            <section class="admin-page-header">
                <div class="admin-page-copy">
                    <h2 class="admin-page-title">Create Deal</h2>
                </div>
            </section>
            <section class="admin-panel">
                <form @submit.prevent="handleSubmit" class="admin-panel__body">
                    <div class="mb-3">
                        <label>Name</label>
                        <input v-model="form.name" type="text" class="form-control" required />
                    </div>
                    <div class="mb-3">
                        <label>Category</label>
                        <input v-model="form.category" type="text" class="form-control" required />
                    </div>
                    <div class="mb-3">
                        <label>Icon</label>
                        <input v-model="form.icon" type="text" class="form-control" />
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label>Original Price</label>
                            <input v-model="form.original_price" type="number" step="0.01" class="form-control" required />
                        </div>
                        <div class="col-md-4">
                            <label>Sale Price</label>
                            <input v-model="form.sale_price" type="number" step="0.01" class="form-control" required />
                        </div>
                        <div class="col-md-4">
                            <label>Discount %</label>
                            <input v-model="form.discount" type="number" class="form-control" required />
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label>Sold Percent</label>
                            <input v-model="form.sold_percent" type="number" class="form-control" />
                        </div>
                        <div class="col-md-6">
                            <label>Sold Label</label>
                            <input v-model="form.sold_label" type="text" class="form-control" />
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Expires At</label>
                        <input v-model="form.expires_at" type="datetime-local" class="form-control" />
                    </div>
                    <div class="mb-3">
                        <label>Sort Order</label>
                        <input v-model="form.sort_order" type="number" class="form-control" />
                    </div>
                    <div class="mb-3">
                        <label><input type="checkbox" v-model="form.is_active" /> Active</label>
                    </div>
                    <button type="submit" class="btn-admin btn-admin--primary">Save</button>
                    <RouterLink to="/admin/deals" class="btn-admin btn-admin--outline ms-2">Cancel</RouterLink>
                </form>
            </section>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import AdminLayout from "@/views/admin/layout/AdminLayout.vue";
import dealService from "@/services/admin/dealService";
import toastr from "toastr";

const router = useRouter();
const form = ref({ name: "", category: "", icon: "", discount: 0, sale_price: 0, original_price: 0, sold_percent: 0, sold_label: "", sort_order: 0, is_active: true, expires_at: "" });

const handleSubmit = async () => {
    try {
        await dealService.create(form.value);
        toastr.success("Created successfully");
        router.push("/admin/deals");
    } catch (e) {}
};
</script>
