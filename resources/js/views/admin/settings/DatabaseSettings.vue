<template>
    <AdminLayout>
        <Transition appear name="page-fade">
            <div class="admin-page">
                <section class="admin-page-header">
                    <div class="admin-page-copy">
                        <div class="admin-page-kicker">
                            <i class="bi bi-database-gear"></i>
                            <span>Database Settings</span>
                        </div>
                        <h2 class="admin-page-title">Manage runtime database configuration</h2>
                        <p class="admin-page-description">
                            Switch between SQLite, MySQL, and PostgreSQL with a connection test before saving any change.
                        </p>
                    </div>
                    <div class="admin-page-actions">
                        <RouterLink to="/admin/settings" class="btn-admin btn-admin--soft">
                            <i class="bi bi-arrow-left"></i>
                            <span>Back to Settings</span>
                        </RouterLink>
                    </div>
                </section>

                <section class="admin-panel position-relative">
                    <div v-if="loading" class="database-settings__overlay">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>

                    <div class="admin-panel__header">
                        <div>
                            <h3 class="admin-panel__title">Connection Profile</h3>
                            <p class="admin-panel__meta">
                                The backend tests the connection first, then updates `.env`, refreshes runtime config, and clears caches safely.
                            </p>
                        </div>
                        <div v-if="connectionMessage" class="admin-pill admin-pill--success">
                            <i class="bi bi-check-circle"></i>
                            <span>{{ connectionMessage }}</span>
                        </div>
                    </div>

                    <form class="admin-panel__body" @submit.prevent="saveSettings">
                        <div class="admin-form-grid">
                            <div class="admin-field">
                                <label class="admin-label" for="driver">
                                    <i class="bi bi-hdd-network"></i>
                                    <span>Database Driver</span>
                                </label>
                                <select id="driver" v-model="form.driver" class="form-select admin-control" @change="handleDriverChange">
                                    <option value="sqlite">sqlite</option>
                                    <option value="mysql">mysql</option>
                                    <option value="pgsql">pgsql</option>
                                </select>
                            </div>

                            <div v-if="isSqlite" class="admin-field admin-field--full">
                                <label class="admin-label" for="sqlite_path">
                                    <i class="bi bi-file-earmark-lock"></i>
                                    <span>SQLite File Path</span>
                                </label>
                                <input
                                    id="sqlite_path"
                                    v-model="form.sqlite_path"
                                    type="text"
                                    class="form-control admin-control"
                                    placeholder="database/database.sqlite"
                                />
                                <p class="admin-helper-text">
                                    If the SQLite file does not exist, it will be created automatically during test and save.
                                </p>
                            </div>

                            <template v-else>
                                <div class="admin-field">
                                    <label class="admin-label" for="host">
                                        <i class="bi bi-router"></i>
                                        <span>DB Host</span>
                                    </label>
                                    <input id="host" v-model="form.host" type="text" class="form-control admin-control" placeholder="127.0.0.1" />
                                </div>

                                <div class="admin-field">
                                    <label class="admin-label" for="port">
                                        <i class="bi bi-plug"></i>
                                        <span>DB Port</span>
                                    </label>
                                    <input id="port" v-model="form.port" type="text" class="form-control admin-control" placeholder="3306" />
                                </div>

                                <div class="admin-field">
                                    <label class="admin-label" for="database">
                                        <i class="bi bi-server"></i>
                                        <span>DB Database</span>
                                    </label>
                                    <input id="database" v-model="form.database" type="text" class="form-control admin-control" placeholder="app_database" />
                                </div>

                                <div class="admin-field">
                                    <label class="admin-label" for="username">
                                        <i class="bi bi-person"></i>
                                        <span>DB Username</span>
                                    </label>
                                    <input id="username" v-model="form.username" type="text" class="form-control admin-control" placeholder="root" />
                                </div>

                                <div class="admin-field admin-field--full">
                                    <label class="admin-label" for="password">
                                        <i class="bi bi-shield-lock"></i>
                                        <span>DB Password</span>
                                    </label>
                                    <input id="password" v-model="form.password" type="password" class="form-control admin-control" placeholder="Enter database password" />
                                    <p v-if="passwordConfigured" class="admin-helper-text">
                                        A password is configured. Leave blank to keep it unchanged.
                                    </p>
                                </div>
                            </template>
                        </div>

                        <div class="database-settings__notes">
                            <div class="database-settings__note">
                                <h4>What happens on save</h4>
                                <p>
                                    Validation runs first, then the backend tests the connection, stores the last used settings snapshot, updates `.env`, and refreshes Laravel config.
                                </p>
                            </div>
                            <div class="database-settings__note">
                                <h4>Driver summary</h4>
                                <p>{{ form.driver.toUpperCase() }} is the active selection for the next save.</p>
                            </div>
                        </div>

                        <div class="database-settings__actions">
                            <button type="button" class="btn-admin btn-admin--soft" :disabled="submitting || testing" @click="testConnection">
                                <span v-if="testing" class="spinner-border spinner-border-sm"></span>
                                <i v-else class="bi bi-activity"></i>
                                <span>Test Connection</span>
                            </button>
                            <button type="submit" class="btn-admin btn-admin--primary" :disabled="submitting || testing">
                                <span v-if="submitting" class="spinner-border spinner-border-sm"></span>
                                <i v-else class="bi bi-save"></i>
                                <span>Save Database Settings</span>
                            </button>
                        </div>
                    </form>
                </section>
            </div>
        </Transition>
    </AdminLayout>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import { RouterLink } from "vue-router";
import toastr from "toastr";
import AdminLayout from "@/views/admin/layout/AdminLayout.vue";
import databaseSettingsService from "@/services/admin/databaseSettingsService";

const loading = ref(false);
const testing = ref(false);
const submitting = ref(false);
const connectionMessage = ref("");
const passwordConfigured = ref(false);

const form = ref({
    driver: "sqlite",
    host: "",
    port: "",
    database: "",
    username: "",
    password: "",
    sqlite_path: "database/database.sqlite",
});

const isSqlite = computed(() => form.value.driver === "sqlite");

const applyDefaults = () => {
    if (isSqlite.value && !form.value.sqlite_path) {
        form.value.sqlite_path = "database/database.sqlite";
    }

    if (form.value.driver === "mysql" && !form.value.port) {
        form.value.port = "3306";
    }

    if (form.value.driver === "pgsql" && !form.value.port) {
        form.value.port = "5432";
    }
};

const normalizePayload = () => ({
    driver: form.value.driver,
    host: isSqlite.value ? null : form.value.host,
    port: isSqlite.value ? null : form.value.port,
    database: isSqlite.value ? null : form.value.database,
    username: isSqlite.value ? null : form.value.username,
    password: isSqlite.value ? null : form.value.password,
    sqlite_path: isSqlite.value ? form.value.sqlite_path : null,
});

const fetchSettings = async () => {
    loading.value = true;

    try {
        const response = await databaseSettingsService.get();
        const data = response.data?.data || {};

        form.value = {
            driver: data.driver || "sqlite",
            host: data.host || "",
            port: data.port || "",
            database: data.database || "",
            username: data.username || "",
            password: "",
            sqlite_path: data.sqlite_path || "database/database.sqlite",
        };

        passwordConfigured.value = Boolean(data.password?.configured);

        applyDefaults();
    } catch (error) {
        toastr.error(error.response?.data?.message || "Failed to load database settings.");
    } finally {
        loading.value = false;
    }
};

const handleDriverChange = () => {
    connectionMessage.value = "";
    applyDefaults();
};

const testConnection = async () => {
    testing.value = true;
    connectionMessage.value = "";

    try {
        const response = await databaseSettingsService.test(normalizePayload());
        const data = response.data?.data || {};
        connectionMessage.value = `${data.driver} connected${data.version ? ` · ${data.version}` : ""}`;
        toastr.success(response.data?.message || "Connection test passed.");
    } catch (error) {
        const message =
            error.response?.data?.errors?.connection?.[0] ||
            error.response?.data?.message ||
            "Connection test failed.";
        toastr.error(message);
    } finally {
        testing.value = false;
    }
};

const saveSettings = async () => {
    submitting.value = true;

    try {
        const response = await databaseSettingsService.update(normalizePayload());
        const data = response.data?.data || {};
        connectionMessage.value = `${data.driver} connected${data.version ? ` · ${data.version}` : ""}`;
        toastr.success(response.data?.message || "Database settings saved successfully.");
        await fetchSettings();
    } catch (error) {
        const message =
            error.response?.data?.errors?.connection?.[0] ||
            error.response?.data?.message ||
            "Unable to save database settings.";
        toastr.error(message);
    } finally {
        submitting.value = false;
    }
};

onMounted(fetchSettings);
</script>

<style scoped>
.database-settings__overlay {
    position: absolute;
    inset: 0;
    z-index: 10;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--admin-radius-xl);
    background: rgba(255, 255, 255, 0.72);
    backdrop-filter: blur(6px);
}

.database-settings__notes {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
    margin-top: 1.5rem;
}

.database-settings__note {
    padding: 1rem 1.1rem;
    border: 1px solid rgba(148, 163, 184, 0.18);
    border-radius: 18px;
    background: rgba(255, 255, 255, 0.7);
}

.database-settings__note h4 {
    margin: 0 0 0.45rem;
    font-size: 0.92rem;
    font-weight: 800;
}

.database-settings__note p {
    margin: 0;
    color: var(--admin-muted);
    line-height: 1.7;
}

.database-settings__actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    margin-top: 1.5rem;
}

@media (max-width: 767.98px) {
    .database-settings__notes {
        grid-template-columns: 1fr;
    }

    .database-settings__actions {
        flex-direction: column;
    }
}
</style>
