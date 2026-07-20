<template>
    <AdminLayout>
        <div class="admin-page">
            <section class="admin-page-header">
                <div class="admin-page-copy">
                    <h2 class="admin-page-title">Create Testimonial</h2>
                </div>
            </section>
            <section class="admin-panel">
                <form @submit.prevent="handleSubmit" class="admin-panel__body">
                    <div class="mb-3">
                        <label>Name</label>
                        <input v-model="form.name" type="text" class="form-control" required />
                    </div>
                    <div class="mb-3">
                        <label>Role</label>
                        <input v-model="form.role" type="text" class="form-control" required />
                    </div>
                    <div class="mb-3">
                        <label>Text</label>
                        <textarea v-model="form.text" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label>Avatar URL (optional)</label>
                        <input v-model="form.avatar" type="text" class="form-control" />
                    </div>
                    <div class="mb-3">
                        <label>Rating (1-5)</label>
                        <input v-model="form.rating" type="number" min="1" max="5" class="form-control" />
                    </div>
                    <div class="mb-3">
                        <label>Sort Order</label>
                        <input v-model="form.sort_order" type="number" class="form-control" />
                    </div>
                    <div class="mb-3">
                        <label><input type="checkbox" v-model="form.is_active" /> Active</label>
                    </div>
                    <button type="submit" class="btn-admin btn-admin--primary">Save</button>
                    <RouterLink to="/admin/testimonials" class="btn-admin btn-admin--outline ms-2">Cancel</RouterLink>
                </form>
            </section>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import AdminLayout from "@/views/admin/layout/AdminLayout.vue";
import testimonialService from "@/services/admin/testimonialService";
import toastr from "toastr";

const router = useRouter();
const form = ref({ name: "", role: "", text: "", avatar: "", rating: 5, sort_order: 0, is_active: true });

const handleSubmit = async () => {
    try {
        await testimonialService.create(form.value);
        toastr.success("Created successfully");
        router.push("/admin/testimonials");
    } catch (e) {}
};
</script>
