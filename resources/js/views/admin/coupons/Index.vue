<template>
    <AdminLayout>
        <div class="admin-page">
            <section class="admin-page-header">
                <div class="admin-page-copy">
                    <div class="admin-page-kicker">
                        <i class="bi bi-ticket-perforated"></i>
                        Coupons
                    </div>
                    <h2 class="admin-page-title">Manage discount campaigns</h2>
                    <p class="admin-page-description">Create fixed or percentage coupons, track usage, and disable expired campaigns.</p>
                </div>
            </section>

            <section class="admin-panel position-relative">
                <!-- Global Loading Overlay for Fetches -->
                <div v-if="fetching" class="loading-overlay d-flex justify-content-center align-items-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>

                <div class="admin-panel__body">
                    <form class="coupon-form" @submit.prevent="saveCoupon">
                        <input v-model="form.code" class="form-control admin-control" placeholder="Code" required :disabled="submitting" />
                        <select v-model="form.type" class="form-select admin-control" :disabled="submitting">
                            <option value="percentage">Percentage</option>
                            <option value="fixed">Fixed</option>
                        </select>
                        <input v-model="form.value" type="number" step="0.01" min="0" class="form-control admin-control" placeholder="Value" required :disabled="submitting" />
                        <input v-model="form.usage_limit" type="number" min="1" class="form-control admin-control" placeholder="Usage limit" :disabled="submitting" />
                        <input v-model="form.expires_at" type="datetime-local" class="form-control admin-control" :disabled="submitting" />
                        <label class="coupon-active">
                            <input v-model="form.is_active" type="checkbox" :disabled="submitting" />
                            Active
                        </label>
                        <button class="btn-admin" type="submit" :disabled="submitting">
                            <span v-if="submitting" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            {{ editingId ? "Update" : "Create" }}
                        </button>
                        <button v-if="editingId" class="btn-admin btn-admin--soft" type="button" @click="resetForm" :disabled="submitting">Cancel</button>
                    </form>

                    <div class="admin-table-wrap mt-4">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Type</th>
                                    <th>Value</th>
                                    <th>Usage</th>
                                    <th>Expires</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="coupon in coupons" :key="coupon.id" :class="{'opacity-50': processingId === coupon.id}">
                                    <td class="admin-table__primary">{{ coupon.code }}</td>
                                    <td>{{ coupon.type }}</td>
                                    <td>{{ coupon.value }}</td>
                                    <td>{{ coupon.used_count || 0 }} / {{ coupon.usage_limit || "∞" }}</td>
                                    <td>{{ formatDate(coupon.expires_at) }}</td>
                                    <td>
                                        <div class="admin-actions justify-content-end">
                                            <button class="btn-admin btn-admin--soft btn-admin--sm" @click="edit(coupon)" :disabled="submitting || processingId === coupon.id">Edit</button>
                                            <button class="btn-admin btn-admin--danger btn-admin--sm" @click="remove(coupon.id)" :disabled="submitting || processingId === coupon.id">
                                                <span v-if="processingId === coupon.id" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                                <span v-else>Delete</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="coupons.length === 0 && !fetching">
                                    <td colspan="6" class="text-center text-muted py-4">No coupons found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>

<script setup>
import { onMounted, reactive, ref } from "vue";
import toastr from "toastr";
import AdminLayout from "@/views/admin/layout/AdminLayout.vue";
import CouponService from "@/services/admin/CouponService";

const coupons = ref([]);
const editingId = ref(null);
const fetching = ref(false);
const submitting = ref(false);
const processingId = ref(null); // Tracks the ID being deleted/updated

const form = reactive({
    code: "",
    type: "percentage",
    value: "",
    usage_limit: "",
    expires_at: "",
    is_active: true,
});

// Fetch coupons with anti-caching parameter for guaranteed fresh data
const fetchCoupons = async (showLoading = true) => {
    if (showLoading) fetching.value = true;
    try {
        const response = await CouponService.getCoupons({ t: new Date().getTime() });
        coupons.value = response.data?.data || [];
    } catch (e) {
        toastr.error("Failed to load coupons");
    } finally {
        fetching.value = false;
    }
};

const saveCoupon = async () => {
    submitting.value = true;
    const payload = { ...form, usage_limit: form.usage_limit || null, expires_at: form.expires_at || null };
    
    try {
        if (editingId.value) {
            processingId.value = editingId.value;
            const res = await CouponService.update(editingId.value, payload);
            
            // Optimistic / local state update
            const index = coupons.value.findIndex(c => c.id === editingId.value);
            if (index !== -1 && res.data) {
                coupons.value[index] = { ...coupons.value[index], ...res.data };
            }
            toastr.success("Coupon updated successfully.");
        } else {
            const res = await CouponService.create(payload);
            
            // Optimistic insert at the top
            if (res.data) {
                coupons.value.unshift(res.data);
            }
            toastr.success("Coupon created successfully.");
        }
        
        resetForm();
        // Silent refetch to sync any server-side changes (like counts) without flicker
        fetchCoupons(false);
    } catch (e) {
        toastr.error(e.response?.data?.message || "Operation failed.");
    } finally {
        submitting.value = false;
        processingId.value = null;
    }
};

const edit = (coupon) => {
    editingId.value = coupon.id;
    Object.assign(form, {
        code: coupon.code,
        type: coupon.type,
        value: coupon.value,
        usage_limit: coupon.usage_limit || "",
        expires_at: coupon.expires_at ? coupon.expires_at.slice(0, 16) : "",
        is_active: Boolean(coupon.is_active),
    });
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const remove = async (id) => {
    if (!window.confirm("Are you sure you want to delete this coupon?")) return;
    
    processingId.value = id;
    try {
        await CouponService.delete(id);
        
        // Optimistic UI update
        coupons.value = coupons.value.filter(c => c.id !== id);
        toastr.success("Coupon deleted successfully.");
        
        // Silent refetch
        fetchCoupons(false);
    } catch (e) {
        toastr.error("Failed to delete coupon.");
    } finally {
        processingId.value = null;
    }
};

const resetForm = () => {
    editingId.value = null;
    Object.assign(form, { code: "", type: "percentage", value: "", usage_limit: "", expires_at: "", is_active: true });
};

const formatDate = (value) => value ? new Intl.DateTimeFormat("en", { dateStyle: "medium", timeStyle: "short" }).format(new Date(value)) : "-";

onMounted(() => {
    fetchCoupons(true);
});
</script>

<style scoped>
.coupon-form {
    display: grid;
    grid-template-columns: repeat(3, minmax(160px, 1fr));
    gap: 1rem;
    align-items: center;
}
.coupon-active {
    display: inline-flex;
    gap: 0.5rem;
    align-items: center;
    font-weight: 700;
}
.loading-overlay {
    position: absolute;
    inset: 0;
    background: rgba(255, 255, 255, 0.6);
    z-index: 10;
    backdrop-filter: blur(2px);
    border-radius: 0.75rem;
}
[data-theme="dark"] .loading-overlay {
    background: rgba(15, 23, 42, 0.6);
}
.opacity-50 {
    opacity: 0.5;
    pointer-events: none;
}
@media (max-width: 991.98px) {
    .coupon-form {
        grid-template-columns: 1fr;
    }
}
</style>
