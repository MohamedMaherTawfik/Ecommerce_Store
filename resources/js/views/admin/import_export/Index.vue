<template>
    <AdminLayout>
        <div class="admin-page">
            <section class="admin-page-header">
                <div class="admin-page-copy">
                    <div class="admin-page-kicker"><i class="bi bi-arrow-left-right"></i><span>Data tools</span></div>
                    <h2 class="admin-page-title">Import and Export</h2>
                    <p class="admin-page-description">Bulk manage products and categories, or download store data.</p>
                </div>
            </section>

            <section class="admin-grid">
                <article v-for="item in imports" :key="item.type" class="admin-grid__item--6 admin-panel">
                    <div class="admin-panel__body">
                        <h3>{{ item.label }} Import</h3>
                        <p class="text-muted">CSV or XLSX, maximum 10 MB.</p>
                        <input class="form-control admin-control mb-3" type="file" accept=".csv,.xlsx,.xls"
                            @change="setFile(item.type, $event)" />
                        <label class="d-flex gap-2 align-items-center mb-3">
                            <input v-model="updateExisting[item.type]" type="checkbox" />
                            Update rows with an existing SKU or name
                        </label>
                        <div class="admin-actions">
                            <button class="btn-admin btn-admin--primary" :disabled="busy || !files[item.type]"
                                @click="runImport(item.type)">Import</button>
                            <button class="btn-admin btn-admin--outline" :disabled="busy"
                                @click="downloadSample(item.type)">Download sample</button>
                        </div>
                    </div>
                </article>
            </section>

            <section v-if="report" class="admin-panel mt-4">
                <div class="admin-panel__body">
                    <h3>Last import report</h3>
                    <div class="admin-grid mt-3">
                        <div v-for="metric in reportMetrics" :key="metric.label" class="admin-grid__item--3 admin-stat-card">
                            <div class="admin-stat-card__label">{{ metric.label }}</div>
                            <div class="admin-stat-card__value">{{ metric.value }}</div>
                        </div>
                    </div>
                    <div v-if="report.failures?.length" class="admin-table-wrap mt-3">
                        <table class="admin-table">
                            <thead><tr><th>Row</th><th>Column</th><th>Error</th></tr></thead>
                            <tbody>
                                <tr v-for="failure in report.failures" :key="`${failure.row}-${failure.attribute}`">
                                    <td>{{ failure.row }}</td><td>{{ failure.attribute }}</td>
                                    <td>{{ failure.errors.join(", ") }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section class="admin-panel mt-4">
                <div class="admin-panel__body">
                    <h3>Exports</h3>
                    <div class="d-flex gap-3 align-items-center flex-wrap mt-3">
                        <select v-model="format" class="form-select admin-control export-format">
                            <option value="xlsx">Excel XLSX</option>
                            <option value="csv">CSV</option>
                        </select>
                        <button v-for="item in exports" :key="item.type" class="btn-admin btn-admin--outline"
                            :disabled="busy" @click="runExport(item.type)">{{ item.label }}</button>
                    </div>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>

<script setup>
import { computed, reactive, ref } from "vue";
import toastr from "toastr";
import AdminLayout from "@/views/admin/layout/AdminLayout.vue";
import service from "@/services/admin/importExportService";

const imports = [{ type: "products", label: "Products" }, { type: "categories", label: "Categories" }];
const exports = [...imports, { type: "orders", label: "Orders" }];
const files = reactive({ products: null, categories: null });
const updateExisting = reactive({ products: false, categories: false });
const busy = ref(false);
const report = ref(null);
const format = ref("xlsx");

const reportMetrics = computed(() => [
    { label: "Created", value: report.value?.created || 0 },
    { label: "Updated", value: report.value?.updated || 0 },
    { label: "Duplicates", value: report.value?.duplicates || 0 },
    { label: "Failed", value: report.value?.failed || 0 },
]);

const setFile = (type, event) => { files[type] = event.target.files?.[0] || null; };
const saveBlob = (blob, filename) => {
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = filename;
    link.click();
    URL.revokeObjectURL(url);
};

const runImport = async (type) => {
    busy.value = true;
    try {
        const response = type === "products"
            ? await service.importProducts(files[type], updateExisting[type])
            : await service.importCategories(files[type], updateExisting[type]);
        report.value = response.data;
        toastr.success(response.message || "Import completed.");
    } finally {
        busy.value = false;
    }
};

const runExport = async (type) => {
    busy.value = true;
    try {
        const blob = await service[`export${type[0].toUpperCase()}${type.slice(1)}`](format.value);
        saveBlob(blob, `${type}.${format.value}`);
    } finally {
        busy.value = false;
    }
};

const downloadSample = async (type) => {
    const blob = await service.sample(type, format.value);
    saveBlob(blob, `${type}-import-template.${format.value}`);
};
</script>

<style scoped>
.export-format { max-width: 180px; }
</style>
