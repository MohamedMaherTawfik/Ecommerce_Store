<template>
    <AdminLayout>
        <Transition appear name="page-fade">
            <div class="admin-page">
                <section class="admin-page-header">
                    <div class="admin-page-copy">
                        <div class="admin-page-kicker">
                            <i class="bi bi-plus-circle"></i>
                            <span>Create Product</span>
                        </div>
                        <h2 class="admin-page-title">Create products in a more premium workspace</h2>
                        <p class="admin-page-description">
                            The logic stays exactly the same while the form becomes more readable and easier to
                            complete.
                        </p>
                    </div>

                    <div class="admin-page-actions">
                        <RouterLink to="/admin/products" class="btn-admin btn-admin--soft">
                            <i class="bi bi-arrow-left"></i>
                            <span>Back</span>
                        </RouterLink>
                    </div>
                </section>

                <section class="admin-panel">
                    <div class="admin-panel__header">
                        <div>
                            <h3 class="admin-panel__title">Product Information</h3>
                            <p class="admin-panel__meta">Cover core attributes, catalog relations, and supporting
                                metadata.</p>
                        </div>
                    </div>

                    <div class="admin-panel__body">
                        <form class="admin-form-grid" @submit.prevent="handleSubmit">
                            <!-- Name -->
                            <div class="admin-field">
                                <label for="name" class="admin-label">
                                    <i class="bi bi-tag"></i>
                                    <span>Name</span>
                                </label>
                                <input id="name" v-model="form.name" type="text" class="form-control admin-control"
                                    placeholder="e.g. Premium Cotton T-Shirt" />
                            </div>

                            <!-- Price -->
                            <div class="admin-field">
                                <label for="price" class="admin-label">
                                    <i class="bi bi-cash-stack"></i>
                                    <span>Price</span>
                                </label>
                                <input id="price" v-model="form.price" type="number" step="0.01"
                                    class="form-control admin-control" placeholder="0.00" />
                            </div>

                            <!-- Quantity -->
                            <div class="admin-field">
                                <label for="quantity" class="admin-label">
                                    <i class="bi bi-box-seam"></i>
                                    <span>Quantity</span>
                                </label>

                                <input id="quantity" v-model="form.quantity" type="number" min="0"
                                    class="form-control admin-control" placeholder="0" />
                            </div>

                            <!-- Category -->
                            <div class="admin-field">
                                <label for="category_id" class="admin-label">
                                    <i class="bi bi-diagram-3"></i>
                                    <span>Category</span>
                                </label>
                                <select id="category_id" v-model="form.category_id"
                                    class="form-select admin-control">
                                    <option value="">Select category</option>
                                    <option v-for="category in categories" :key="category.id" :value="category.id">
                                        {{ category.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- Brand -->
                            <div class="admin-field">
                                <label for="brand_id" class="admin-label">
                                    <i class="bi bi-award"></i>
                                    <span>Brand</span>
                                </label>
                                <select id="brand_id" v-model="form.brand_id" class="form-select admin-control">
                                    <option value="">Select brand</option>
                                    <option v-for="brand in brands" :key="brand.id" :value="brand.id">
                                        {{ brand.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- SKU -->
                            <div class="admin-field">
                                <label for="sku" class="admin-label">
                                    <i class="bi bi-upc-scan"></i>
                                    <span>SKU</span>
                                </label>
                                <input id="sku" v-model="form.sku" @input="isSkuEdited = true" type="text"
                                    class="form-control admin-control" />
                            </div>

                            <!-- Tax -->
                            <div class="admin-field">
                                <label for="tax" class="admin-label">
                                    <i class="bi bi-percent"></i>
                                    <span>Tax</span>
                                </label>
                                <input id="tax" v-model="form.tax" type="number" step="0.01"
                                    class="form-control admin-control" placeholder="0.00" />
                            </div>

                            <!-- Image -->
                            <div class="admin-field">
                                <label for="image" class="admin-label">
                                    <i class="bi bi-image"></i>
                                    <span>Image</span>
                                </label>
                                <input id="images" type="file" class="form-control admin-control"
                                    @change="handleFileChange" accept="image/*" multiple />
                                <small v-if="form.image" class="text-success mt-1 d-block">
                                    <i class="bi bi-check-circle"></i> {{ form.image.name }}
                                </small>
                            </div>

                            <!-- Description -->
                            <div class="admin-field admin-field--full">
                                <label for="description" class="admin-label">
                                    <i class="bi bi-card-text"></i>
                                    <span>Description</span>
                                </label>
                                <textarea id="description" v-model="form.description" class="form-control admin-control"
                                    rows="4" placeholder="Product detailed description..."></textarea>
                            </div>

                            <!-- Return Policy -->
                            <div class="admin-field admin-field--full">
                                <label for="return_policy" class="admin-label">
                                    <i class="bi bi-arrow-repeat"></i>
                                    <span>Return Policy</span>
                                </label>
                                <textarea id="return_policy" v-model="form.return_policy"
                                    class="form-control admin-control" rows="3"></textarea>
                            </div>

                            <SeoFields
                                v-model:slug="form.slug"
                                v-model:meta-title="form.meta_title"
                                v-model:meta-description="form.meta_description"
                                v-model:meta-keywords="form.meta_keywords"
                                v-model:og-title="form.og_title"
                                v-model:og-description="form.og_description"
                                v-model:og-image="form.og_image"
                                v-model:canonical-url="form.canonical_url"
                                :fallback-title="form.name"
                                :fallback-description="form.description"
                            />

                            <!-- ================= DYNAMIC FIELDS ================= -->

                            <!-- Sizes Array -->
                            <div class="admin-field admin-field--full">
                                <label class="admin-label">
                                    <i class="bi bi-rulers"></i>
                                    <span>Sizes</span>
                                    <button type="button" class="btn btn-sm btn-outline-primary ms-auto"
                                        @click="addSize">
                                        <i class="bi bi-plus-lg"></i> Add Size
                                    </button>
                                </label>

                                <TransitionGroup name="list" tag="div" class="dynamic-inputs">
                                    <div v-for="(size, index) in form.sizes" :key="size.id" class="dynamic-input-item">
                                        <input type="text" v-model="size.value" class="form-control admin-control"
                                            placeholder="e.g. M, L, XL, 42" />
                                        <button type="button" class="btn-remove" @click="removeSize(index)"
                                            title="Remove">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>
                                </TransitionGroup>

                                <div v-if="form.sizes.length === 0" class="text-muted small fst-italic ps-1">
                                    No sizes added yet. Click "Add Size" to include available sizes.
                                </div>
                            </div>

                            <!-- Colors Array -->
                            <div class="admin-field admin-field--full">
                                <label class="admin-label">
                                    <i class="bi bi-palette"></i>
                                    <span>Colors</span>
                                    <button type="button" class="btn btn-sm btn-outline-primary ms-auto"
                                        @click="addColor">
                                        <i class="bi bi-plus-lg"></i> Add Color
                                    </button>
                                </label>

                                <TransitionGroup name="list" tag="div" class="dynamic-inputs">
                                    <div v-for="(color, index) in form.colors" :key="color.id"
                                        class="dynamic-input-item">
                                        <input type="text" v-model="color.value" class="form-control admin-control"
                                            placeholder="e.g. Black, White, #FF5733" />
                                        <button type="button" class="btn-remove" @click="removeColor(index)"
                                            title="Remove">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>
                                </TransitionGroup>

                                <div v-if="form.colors.length === 0" class="text-muted small fst-italic ps-1">
                                    No colors added yet. Click "Add Color" to include available colors.
                                </div>
                            </div>

                            <!-- ================= END DYNAMIC FIELDS ================= -->

                            <!-- Switches: Active / Featured -->
                            <div class="admin-field admin-field--full">
                                <div class="admin-switch-grid">
                                    <label class="admin-switch">
                                        <input id="is_active" v-model="form.is_active" class="form-check-input"
                                            type="checkbox" />
                                        <span>
                                            <strong class="d-block">Active</strong>
                                            <small class="text-muted">Show this product as available.</small>
                                        </span>
                                    </label>

                                    <label class="admin-switch">
                                        <input id="is_featured" v-model="form.is_featured" class="form-check-input"
                                            type="checkbox" />
                                        <span>
                                            <strong class="d-block">Featured</strong>
                                            <small class="text-muted">Highlight this product where needed.</small>
                                        </span>
                                    </label>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="admin-field admin-field--full">
                                <div class="admin-actions">
                                    <RouterLink to="/admin/products" class="btn-admin btn-admin--soft">
                                        <i class="bi bi-x-lg"></i>
                                        <span>Cancel</span>
                                    </RouterLink>
                                    <button type="submit" class="btn-admin btn-admin--primary" :disabled="submitting">
                                        <i class="bi bi-check2-circle"></i>
                                        <span>{{ submitting ? "Saving..." : "Create Product" }}</span>
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
import { onMounted, reactive, ref, watch } from "vue";
import { RouterLink, useRouter } from "vue-router";
import SeoFields from "@/components/admin/SeoFields.vue";
import AdminLayout from "@/views/admin/layout/AdminLayout.vue";
import brandService from "@/services/admin/brands/brandService";
import categoryService from "@/services/admin/categories/categoryService";
import productService from "@/services/admin/products/productService";

const router = useRouter();
const submitting = ref(false);
const brands = ref([]);
const categories = ref([]);
const isSkuEdited = ref(false);
const isMetaTitleEdited = ref(false);
const isMetaDescriptionEdited = ref(false);

// Counter for unique IDs in dynamic arrays
let sizeIdCounter = 0;
let colorIdCounter = 0;

const form = reactive({
    name: "",
    description: "",
    price: "",
    category_id: "",
    brand_id: "",
    quantity: "",
    images: [],
    is_active: true,
    is_featured: false,
    return_policy: "",
    meta_title: "",
    meta_description: "",
    meta_keywords: "",
    slug: "",
    og_title: "",
    og_description: "",
    og_image: null,
    canonical_url: "",
    sku: "",
    tax: "",
    // Dynamic arrays for sizes and colors
    sizes: [],  // [{ id: 1, value: 'M' }, { id: 2, value: 'L' }]
    colors: [], // [{ id: 1, value: 'Black' }, { id: 2, value: 'White' }]
});

const getCollection = (payload) => {
    if (Array.isArray(payload?.data)) {
        return payload.data;
    }
    if (Array.isArray(payload?.data?.data)) {
        return payload.data.data;
    }
    return [];
};

// --- Dynamic Sizes Functions ---
const addSize = () => {
    form.sizes.push({ id: ++sizeIdCounter, value: "" });
};

const removeSize = (index) => {
    form.sizes.splice(index, 1);
};

// --- Dynamic Colors Functions ---
const addColor = () => {
    form.colors.push({ id: ++colorIdCounter, value: "" });
};

const removeColor = (index) => {
    form.colors.splice(index, 1);
};

const fetchOptions = async () => {
    try {
        const [brandsResponse, categoriesResponse] = await Promise.all([
            brandService.getAllBrands(),
            categoryService.getAllCategories(),
        ]);
        brands.value = getCollection(brandsResponse);
        categories.value = getCollection(categoriesResponse);
    } catch (error) {
        console.error("Failed to fetch product options:", error);
    }
};

const appendIfPresent = (payload, key, value) => {
    if (value !== "" && value !== null && value !== undefined) {
        payload.append(key, value);
    }
};

const buildPayload = () => {
    const payload = new FormData();

    // Basic fields
    appendIfPresent(payload, "name", form.name);
    appendIfPresent(payload, "description", form.description);
    appendIfPresent(payload, "price", form.price);
    appendIfPresent(payload, "category_id", form.category_id);
    appendIfPresent(payload, "brand_id", form.brand_id);
    appendIfPresent(payload, "return_policy", form.return_policy);
    appendIfPresent(payload, "meta_title", form.meta_title);
    appendIfPresent(payload, "meta_description", form.meta_description);
    appendIfPresent(payload, "meta_keywords", form.meta_keywords);
    appendIfPresent(payload, "slug", form.slug);
    appendIfPresent(payload, "og_title", form.og_title);
    appendIfPresent(payload, "og_description", form.og_description);
    appendIfPresent(payload, "canonical_url", form.canonical_url);
    if (form.og_image instanceof File) {
        payload.append("og_image", form.og_image);
    }
    appendIfPresent(payload, "sku", form.sku);
    appendIfPresent(payload, "tax", form.tax);
    appendIfPresent(payload, "quantity", form.quantity);

    // Boolean fields
    payload.append("is_active", form.is_active ? 1 : 0);
    payload.append("is_featured", form.is_featured ? 1 : 0);

    // Image
    form.images.forEach(file => {
        payload.append("images[]", file);
    });

    // Dynamic Arrays: Send as JSON strings
    // Filter out empty values before sending
    const validSizes = form.sizes.filter(s => s.value.trim() !== "").map(s => s.value.trim());
    const validColors = form.colors.filter(c => c.value.trim() !== "").map(c => c.value.trim());

    if (validSizes.length > 0) {
        payload.append("sizes", JSON.stringify(validSizes));
    }
    if (validColors.length > 0) {
        payload.append("colors", JSON.stringify(validColors));
    }

    return payload;
};
const handleFileChange = (event) => {
    form.images = Array.from(event.target.files);
};
const handleSubmit = async () => {
    // Simple validation for required fields
    if (!form.name || !form.price) {
        alert("Please fill in at least the product name and price.");
        return;
    }

    submitting.value = true;

    try {
        await productService.createProduct(buildPayload());
        await router.push("/admin/products");
    } catch (error) {
        console.error("Failed to create product:", error);
        // Optional: Show error notification to user
        alert("Failed to create product. Please check the console for details.");
    } finally {
        submitting.value = false;
    }
};
const generateSlug = (text) => {
    return text
        .toLowerCase()
        .trim()
        .replace(/[^\w\s-]/g, "")
        .replace(/\s+/g, "-");
};

const generateSKU = (name) => {
    const slug = generateSlug(name);
    const random = Math.floor(1000 + Math.random() * 9000);
    return `${slug}-${random}`;
};

const generateMetaDescription = (name) => {
    return `Buy ${name} at the best price with high quality and fast delivery.`;
};

watch(
    () => form.name,
    (newName) => {
        if (!newName) return;

        if (!isSkuEdited.value) {
            form.sku = generateSKU(newName);
        }

        if (!isMetaTitleEdited.value) {
            form.meta_title = newName;
        }

        if (!isMetaDescriptionEdited.value) {
            form.meta_description = generateMetaDescription(newName);
        }
    }
);
onMounted(() => {
    fetchOptions();
});
</script>

<style scoped>
/* --- Base Styles --- */
.admin-page {
    padding: 1.5rem;
}

.admin-page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1.5rem;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #eee;
}

.admin-page-kicker {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #6c757d;
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
}

.admin-page-title {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0 0 0.5rem 0;
    color: #212529;
}

.admin-page-description {
    color: #6c757d;
    margin: 0;
    max-width: 600px;
}

.admin-page-actions .btn-admin {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

/* --- Panel Styles --- */
.admin-panel {
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
    overflow: hidden;
}

.admin-panel__header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #dee2e6;
    background: #f8f9fa;
}

.admin-panel__title {
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0 0 0.25rem 0;
}

.admin-panel__meta {
    font-size: 0.875rem;
    color: #6c757d;
    margin: 0;
}

.admin-panel__body {
    padding: 1.5rem;
}

/* --- Form Grid --- */
.admin-form-grid {
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    gap: 1rem 1.5rem;
}

.admin-field {
    grid-column: span 6;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.admin-field--full {
    grid-column: span 12;
}

/* --- Labels & Controls --- */
.admin-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;
    font-size: 0.875rem;
    color: #343a40;
}

.admin-control {
    padding: 0.625rem 0.875rem;
    font-size: 0.95rem;
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.admin-control:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
    outline: none;
}

textarea.admin-control {
    resize: vertical;
    min-height: 80px;
}

/* --- Dynamic Inputs (Sizes/Colors) --- */
.dynamic-inputs {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.dynamic-input-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    animation: slideIn 0.2s ease-out;
}

.dynamic-input-item input {
    flex: 1;
}

.btn-remove {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 38px;
    padding: 0;
    background: #ffcdd2;
    color: #c62828;
    border: none;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: background 0.2s, transform 0.1s;
}

.btn-remove:hover {
    background: #ef9a9a;
    transform: scale(1.05);
}

.btn-remove:active {
    transform: scale(0.95);
}

/* List Transition Animation */
.list-enter-active,
.list-leave-active {
    transition: all 0.3s ease;
}

.list-enter-from,
.list-leave-to {
    opacity: 0;
    transform: translateX(-10px);
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-5px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* --- Switches --- */
.admin-switch-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.admin-switch {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: border-color 0.2s, background 0.2s;
}

.admin-switch:hover {
    border-color: #0d6efd;
    background: #f8f9fa;
}

.admin-switch input {
    width: 1.1rem;
    height: 1.1rem;
    cursor: pointer;
}

.admin-switch span {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.admin-switch strong {
    font-size: 0.9rem;
    color: #212529;
}

.admin-switch small {
    font-size: 0.75rem;
    color: #6c757d;
}

/* --- Actions --- */
.admin-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #eee;
}

/* --- Buttons --- */
.btn-admin {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1.25rem;
    font-size: 0.9rem;
    font-weight: 500;
    border-radius: 0.375rem;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s ease;
    border: none;
}

.btn-admin:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.btn-admin--soft {
    background: #e9ecef;
    color: #212529;
}

.btn-admin--soft:hover:not(:disabled) {
    background: #dee2e6;
}

.btn-admin--primary {
    background: dimgray;
    color: white;
}

.btn-admin--primary:hover:not(:disabled) {
    background: #555;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

/* --- Page Transition --- */
.page-fade-enter-active,
.page-fade-leave-active {
    transition: opacity 0.3s ease, transform 0.3s ease;
}

.page-fade-enter-from,
.page-fade-leave-to {
    opacity: 0;
    transform: translateY(10px);
}

/* --- Responsive --- */
@media (max-width: 991px) {
    .admin-form-grid {
        grid-template-columns: 1fr;
    }

    .admin-field {
        grid-column: span 1;
    }

    .admin-switch-grid {
        grid-template-columns: 1fr;
    }

    .admin-page-header {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>
