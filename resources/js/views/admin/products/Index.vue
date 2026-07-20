<template>
    <AdminLayout>
        <Transition appear name="page-fade">
            <div class="admin-page">
                <section class="admin-page-header">
                    <div class="admin-page-copy">
                        <div class="admin-page-kicker">
                            <i class="bi bi-box-seam"></i>
                            <span>Products</span>
                        </div>
                        <h2 class="admin-page-title">Browse products with a sharper, calmer layout</h2>
                        <p class="admin-page-description">
                            The table keeps all current actions intact while improving spacing, hierarchy, and hover
                            feedback.
                        </p>
                    </div>

                    <div class="admin-page-actions">
                        <RouterLink to="/admin/products/create" class="btn-admin btn-admin--primary">
                            <i class="bi bi-plus-lg"></i>
                            <span>Create Product</span>
                        </RouterLink>
                    </div>
                </section>

                <section class="admin-panel">
                    <div class="admin-panel__header">
                        <div>
                            <h3 class="admin-panel__title">Product Catalog</h3>
                            <p class="admin-panel__meta">Review pricing and relations in a more premium table
                                presentation.</p>
                        </div>
                        <span class="admin-pill">
                            <i class="bi bi-boxes"></i>
                            <span>{{ total }} total · {{ products.length }} visible</span>
                        </span>
                    </div>

                    <div class="admin-panel__body">
                        <div v-if="loading" class="admin-skeleton-panel">
                            <div class="admin-skeleton-line admin-skeleton-line--lg"></div>
                            <div class="admin-skeleton-line"></div>
                            <div class="admin-skeleton-line admin-skeleton-line--md"></div>
                        </div>

                        <div v-else-if="products.length === 0" class="admin-empty-state">
                            <div class="admin-empty-state__icon">
                                <i class="bi bi-bag-plus"></i>
                            </div>
                            <h3 class="admin-empty-state__title">No products available</h3>
                            <p class="admin-empty-state__text">
                                Add products to start populating the catalog and managing inventory details here.
                            </p>
                        </div>

                        <div v-else class="admin-table-wrap">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Category</th>
                                        <th>Brand</th>
                                        <th>qunatity</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="product in products" :key="product.id">
                                        <td class="admin-table__primary">#{{ product.id }}</td>
                                        <td>
                                            <div class="admin-table__primary">{{ product.name || "-" }}</div>
                                            <div class="admin-table__secondary">{{ product.sku || "No SKU" }}</div>
                                        </td>
                                        <td>{{ product.price ?? "-" }}</td>
                                        <td>{{ product.category?.name || product.categories_id || "-" }}</td>
                                        <td>{{ product.brand?.name || product.brands_id || "-" }}</td>
                                        <td>{{ product.stocks?.quantity || "0" }}</td>
                                        <td class="text-end">
                                            <div class="admin-actions">
                                                <RouterLink :to="`/admin/products/${product.id}`"
                                                    class="btn-admin btn-admin--soft btn-admin--sm">
                                                    <i class="bi bi-eye"></i>
                                                    <span>View</span>
                                                </RouterLink>
                                                <RouterLink :to="`/admin/products/${product.id}/edit`"
                                                    class="btn-admin btn-admin--outline btn-admin--sm">
                                                    <i class="bi bi-pencil-square"></i>
                                                    <span>Edit</span>
                                                </RouterLink>
                                                <button v-if="canDeleteProducts" type="button"
                                                    class="btn-admin btn-admin--danger btn-admin--sm"
                                                    :disabled="deletingId === product.id"
                                                    @click="handleDelete(product.id)">
                                                    <i class="bi bi-trash3"></i>
                                                    <span>{{ deletingId === product.id ? "Deleting..." : "Delete"
                                                    }}</span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div v-if="lastPage > 1"
                            class="flex items-center justify-between bg-white rounded-xl px-4 py-3 shadow-sm">

                            <!-- Prev -->
                            <button
                                class="w-10 h-10 flex items-center justify-center rounded-lg border border-blue-200 text-blue-500 hover:bg-blue-50 disabled:opacity-40"
                                :disabled="currentPage === 1" @click="goToPage(currentPage - 1)">
                                <i class="bi bi-chevron-left"></i>
                            </button>

                            <!-- Pages -->
                            <div class="flex items-center gap-2">
                                <template v-for="page in paginationPages" :key="page">

                                    <span v-if="page === '...'" class="px-2 text-gray-400">
                                        ...
                                    </span>

                                    <button v-else @click="goToPage(page)"
                                        class="w-10 h-10 rounded text-sm font-medium transition" :class="page === currentPage
                                            ? 'bg-blue-600 text-white shadow-md'
                                            : 'border border-blue-200 text-blue-600 hover:bg-blue-50'">
                                        {{ page }}
                                    </button>

                                </template>
                            </div>

                            <!-- Next -->
                            <button
                                class="w-10 h-10 flex items-center justify-center rounded-lg border border-blue-200 text-blue-500 hover:bg-blue-50 disabled:opacity-40"
                                :disabled="currentPage === lastPage" @click="goToPage(currentPage + 1)">
                                <i class="bi bi-chevron-right"></i>
                            </button>

                        </div>

                    </div>
                </section>
            </div>
        </Transition>
    </AdminLayout>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import { RouterLink } from "vue-router";
import { hasAdminPermission } from "@/config/adminAccess";
import { getUserData } from "@/services/auth/authSession";
import AdminLayout from "@/views/admin/layout/AdminLayout.vue";
import productService from "@/services/admin/products/productService";

const canDeleteProducts = hasAdminPermission(getUserData() || {}, "products.delete");
const products = ref([]);
const loading = ref(false);
const deletingId = ref(null);
const currentPage = ref(1);
const lastPage = ref(1);
const total = ref(0);

// Smart pagination: 1 … 4 5 6 … 20
const paginationPages = computed(() => {
    const pages = [];
    const current = currentPage.value;
    const last = lastPage.value;

    for (let i = 1; i <= last; i++) {
        if (i === 1 || i === last || (i >= current - 1 && i <= current + 1)) {
            pages.push(i);
        } else if (pages.at(-1) !== '...') {
            pages.push('...');
        }
    }

    return pages;
});

const fetchProducts = async (page = 1) => {
    loading.value = true;
    try {
        const response = await productService.getProducts({ page });
        const payload = response?.data;
        products.value = payload?.data ?? [];
        currentPage.value = payload?.current_page ?? 1;
        lastPage.value = payload?.last_page ?? 1;
        total.value = payload?.total ?? 0;
    } catch (error) {
        console.error("Failed to fetch products:", error);
    } finally {
        loading.value = false;
    }
};

const goToPage = (page) => {
    if (page === '...' || page < 1 || page > lastPage.value || page === currentPage.value) return;
    fetchProducts(page);
};

const handleDelete = async (id) => {
    if (!window.confirm("Are you sure you want to delete this product?")) return;

    deletingId.value = id;
    try {
        await productService.deleteProduct(id);
        await fetchProducts(currentPage.value);
    } catch (error) {
        console.error(`Failed to delete product ${id}:`, error);
    } finally {
        deletingId.value = null;
    }
};

onMounted(() => fetchProducts());
</script>

<style scoped>
.admin-pagination {
    display: flex;
    align-items: center;
    gap: 6px;
    /* المسافة بين الأزرار */
}

.admin-pagination button {
    margin: 0;
}
</style>
