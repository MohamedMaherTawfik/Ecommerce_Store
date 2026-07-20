<template>
    <AdminLayout>
        <Transition appear name="page-fade">
            <div class="admin-page">
                <section class="admin-page-header">
                    <div class="admin-page-copy">
                        <div class="admin-page-kicker">
                            <i class="bi bi-gear-wide-connected"></i>
                            <span>Environment Settings</span>
                        </div>
                        <h2 class="admin-page-title">Control environment-based app settings from one polished workspace</h2>
                        <p class="admin-page-description">
                            Edit sensitive runtime values safely, keep configurations aligned, and prepare the panel for future environment sections.
                        </p>
                    </div>

                    <div class="admin-page-actions">
                        <RouterLink to="/admin/settings/database" class="btn-admin btn-admin--outline">
                            <i class="bi bi-database-gear"></i>
                            <span>Database Settings</span>
                        </RouterLink>
                    </div>
                </section>

                <section class="admin-panel application-settings position-relative">
                    <div v-if="loading" class="application-settings__overlay">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>

                    <div class="admin-panel__header">
                        <div>
                            <h3 class="admin-panel__title">Environment Configuration</h3>
                            <p class="admin-panel__meta">
                                Saving updates the `.env` file safely, clears config and cache, and applies values to the database immediately.
                            </p>
                        </div>

                        <div class="application-settings__header-badges">
                            <span class="admin-pill">
                                <i class="bi bi-shield-lock"></i>
                                <span>{{ permissionLabel }}</span>
                            </span>
                            <span class="admin-pill admin-pill--success">
                                <i class="bi bi-lightning-charge"></i>
                                <span>Instant runtime refresh</span>
                            </span>
                        </div>
                    </div>

                    <div class="admin-panel__body">
                        <div class="application-settings__layout">
                            <aside class="application-settings__tabs" aria-label="Environment settings sections">
                                <SettingsTab
                                    v-for="tab in tabs"
                                    :key="tab.key"
                                    :title="tab.label"
                                    :description="tab.description"
                                    :active="activeTab === tab.key"
                                    @click="activeTab = tab.key"
                                />
                            </aside>

                            <div class="application-settings__content">
                                <div class="application-settings__hero">
                                    <div>
                                        <p class="application-settings__eyebrow">{{ currentTab.label }}</p>
                                        <h3 class="application-settings__section-title">{{ currentTabHeading }}</h3>
                                        <p class="application-settings__section-text">
                                            {{ currentTab.description }}
                                        </p>
                                    </div>

                                    <div class="application-settings__hero-badge">
                                        <i class="bi bi-stars"></i>
                                        <span>Dynamic Configuration</span>
                                    </div>
                                </div>

                                <form class="application-settings__form" @submit.prevent="saveSettings">
                                    <template v-for="(fields, sectionName) in groupedFields" :key="sectionName">
                                        <SettingsSectionCard :title="sectionName">
                                            <template v-for="field in fields" :key="field.key">
                                                <component
                                                    :is="getComponentForField(field)"
                                                    v-model="form[field.key]"
                                                    :id="field.key"
                                                    :label="field.label"
                                                    :placeholder="field.placeholder"
                                                    :help="field.help"
                                                    :options="field.options"
                                                    :disabled="submitting"
                                                    :fullWidth="['GOOGLE_CLIENT_SECRET', 'GOOGLE_REDIRECT_URL', 'STRIPE_SECRET_KEY', 'STRIPE_WEBHOOK_SECRET', 'PAYPAL_SANDBOX_CLIENT_SECRET', 'PAYPAL_LIVE_CLIENT_SECRET', 'MAIL_PASSWORD', 'AWS_SECRET_ACCESS_KEY'].includes(field.key)"
                                                >
                                                    <template v-if="field.key === 'GOOGLE_REDIRECT_URL'">
                                                        <label class="application-settings__switch" style="margin-top: 10px;">
                                                            <input v-model="manualRedirectUrl" type="checkbox" :disabled="submitting" />
                                                            <span>Set a custom redirect URL instead of mirroring the redirect URI.</span>
                                                        </label>
                                                    </template>
                                                </component>
                                            </template>
                                        </SettingsSectionCard>
                                    </template>

                                    <!-- Google OAuth Preview specific -->
                                    <div v-if="currentTab.key === 'google_oauth'" class="admin-field admin-field--full">
                                        <div class="application-settings__preview">
                                            <article class="card border-0 shadow-sm rounded-4" style="background: rgba(255, 255, 255, 0.72);">
                                                <div class="card-body p-3">
                                                    <p class="application-settings__preview-label">Effective callback URI</p>
                                                    <strong>{{ form.GOOGLE_REDIRECT_URI || "Not set yet" }}</strong>
                                                </div>
                                            </article>
                                            <article class="card border-0 shadow-sm rounded-4" style="background: rgba(255, 255, 255, 0.72);">
                                                <div class="card-body p-3">
                                                    <p class="application-settings__preview-label">Stored redirect URL</p>
                                                    <strong>{{ effectiveRedirectUrl || "Will follow redirect URI automatically" }}</strong>
                                                </div>
                                            </article>
                                        </div>
                                    </div>

                                    <div class="admin-field admin-field--full">
                                        <div class="application-settings__actions">
                                            <button v-if="currentTab.actions && currentTab.actions.find(a => a.key === 'send_test_email')" type="button" class="btn-admin btn-admin--soft" @click="sendTestEmail" :disabled="submitting || loading">
                                                <i class="bi bi-envelope"></i>
                                                <span>Send Test Email</span>
                                            </button>
                                            <button type="button" class="btn-admin btn-admin--soft" :disabled="submitting || loading" @click="resetForm">
                                                <i class="bi bi-arrow-counterclockwise"></i>
                                                <span>Reset</span>
                                            </button>
                                            <button type="submit" class="btn-admin btn-admin--primary" :disabled="submitting || loading">
                                                <span v-if="submitting" class="spinner-border spinner-border-sm"></span>
                                                <i v-else class="bi bi-save"></i>
                                                <span>{{ submitting ? "Saving..." : "Save Settings" }}</span>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </Transition>
    </AdminLayout>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { RouterLink } from "vue-router";
import toastr from "toastr";
import AdminLayout from "@/views/admin/layout/AdminLayout.vue";
import applicationSettingsService from "@/services/admin/applicationSettingsService";
import SettingsTab from "@/components/admin/settings/SettingsTab.vue";
import SettingsSectionCard from "@/components/admin/settings/SettingsSectionCard.vue";
import SettingsInput from "@/components/admin/settings/SettingsInput.vue";
import SettingsSecretInput from "@/components/admin/settings/SettingsSecretInput.vue";
import SettingsSelect from "@/components/admin/settings/SettingsSelect.vue";
import SettingsToggle from "@/components/admin/settings/SettingsToggle.vue";

const loading = ref(false);
const submitting = ref(false);
const activeTab = ref("google_oauth");
const manualRedirectUrl = ref(false);
const permission = ref("manage_application_settings");
const tabs = ref([]);
const form = ref({});
const initialForm = ref({});
const testEmailRecipient = ref("");

const currentTab = computed(() => tabs.value.find((tab) => tab.key === activeTab.value) || { fields: [] });
const currentTabHeading = computed(() => `${currentTab.value.label} Configuration`);

const effectiveRedirectUrl = computed(() => manualRedirectUrl.value
    ? form.value.GOOGLE_REDIRECT_URL
    : form.value.GOOGLE_REDIRECT_URI);

const permissionLabel = computed(() => `Permission: ${permission.value}`);

const groupedFields = computed(() => {
    const fields = currentTab.value.fields || [];
    return fields.reduce((groups, field) => {
        const section = field.section || "Settings";
        if (!groups[section]) {
            groups[section] = [];
        }
        groups[section].push(field);
        return groups;
    }, {});
});

watch(
    () => form.value.GOOGLE_REDIRECT_URI,
    (value) => {
        if (!manualRedirectUrl.value) {
            form.value.GOOGLE_REDIRECT_URL = value || "";
        }
    },
);

watch(manualRedirectUrl, (enabled) => {
    if (!enabled) {
        form.value.GOOGLE_REDIRECT_URL = form.value.GOOGLE_REDIRECT_URI || "";
    }
});

const getComponentForField = (field) => {
    if (field.type === 'password') return SettingsSecretInput;
    if (field.type === 'select') return SettingsSelect;
    if (field.type === 'toggle') return SettingsToggle;
    return SettingsInput;
};

const hydratePage = (payload = {}) => {
    const incomingTabs = payload.tabs ? Object.values(payload.tabs) : [];
    const incomingValues = payload.values || {};

    if (incomingTabs.length) {
        tabs.value = incomingTabs;
    }

    permission.value = payload.permission || "manage_application_settings";

    const newForm = {};
    tabs.value.forEach(tab => {
        tab.fields.forEach(field => {
            newForm[field.key] = incomingValues[field.key] !== undefined ? incomingValues[field.key] : "";
        });
    });

    form.value = { ...newForm };
    initialForm.value = { ...newForm };
    
    if (payload.meta?.test_mail_recipient) {
        testEmailRecipient.value = payload.meta.test_mail_recipient;
    }

    manualRedirectUrl.value = Boolean(
        form.value.GOOGLE_REDIRECT_URL
        && form.value.GOOGLE_REDIRECT_URI
        && form.value.GOOGLE_REDIRECT_URL !== form.value.GOOGLE_REDIRECT_URI,
    );
};

const fetchSettings = async () => {
    loading.value = true;
    try {
        const response = await applicationSettingsService.get();
        hydratePage(response.data?.data || {});
        // Fallback tab if not found
        if (!tabs.value.find(t => t.key === activeTab.value) && tabs.value.length > 0) {
            activeTab.value = tabs.value[0].key;
        }
    } catch (error) {
        toastr.error(error.response?.data?.message || "Failed to load environment settings.");
    } finally {
        loading.value = false;
    }
};

const saveSettings = async () => {
    submitting.value = true;
    try {
        const payload = {
            ...form.value,
        };

        if (currentTab.value.key === 'google_oauth') {
            payload.GOOGLE_REDIRECT_URL = manualRedirectUrl.value
                ? form.value.GOOGLE_REDIRECT_URL
                : form.value.GOOGLE_REDIRECT_URI;
        }

        const response = await applicationSettingsService.update(payload);
        hydratePage(response.data?.data || {});
        toastr.success(response.data?.message || "Environment settings saved successfully.");
    } catch (error) {
        toastr.error(error.response?.data?.message || "Failed to save environment settings.");
    } finally {
        submitting.value = false;
    }
};

const sendTestEmail = async () => {
    submitting.value = true;
    try {
        const email = prompt("Enter recipient email address:", testEmailRecipient.value);
        if (!email) {
            submitting.value = false;
            return;
        }
        await applicationSettingsService.sendTestMail(email);
        toastr.success(`Test email sent successfully to ${email}`);
    } catch (error) {
        toastr.error(error.response?.data?.message || "Failed to send test email.");
    } finally {
        submitting.value = false;
    }
};

const resetForm = () => {
    form.value = { ...initialForm.value };
    manualRedirectUrl.value = Boolean(
        form.value.GOOGLE_REDIRECT_URL
        && form.value.GOOGLE_REDIRECT_URI
        && form.value.GOOGLE_REDIRECT_URL !== form.value.GOOGLE_REDIRECT_URI,
    );
};

onMounted(fetchSettings);
</script>

<style scoped>
.application-settings__overlay {
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

.application-settings__layout {
    display: grid;
    grid-template-columns: minmax(260px, 320px) minmax(0, 1fr);
    gap: 1.3rem;
}

.application-settings__tabs {
    display: flex;
    flex-direction: column;
    gap: 0.9rem;
}

.application-settings__content {
    display: flex;
    flex-direction: column;
    gap: 1.2rem;
}

.application-settings__hero {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.25rem;
    border: 1px solid rgba(148, 163, 184, 0.14);
    border-radius: 24px;
    background:
        radial-gradient(circle at top right, rgba(37, 99, 235, 0.12), transparent 32%),
        rgba(255, 255, 255, 0.82);
}

.application-settings__eyebrow {
    margin: 0 0 0.45rem;
    color: var(--admin-primary);
    font-size: 0.8rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.application-settings__section-title {
    margin: 0;
    font-size: 1.35rem;
    font-weight: 800;
}

.application-settings__section-text {
    margin: 0.55rem 0 0;
    color: var(--admin-muted);
    line-height: 1.7;
}

.application-settings__hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    padding: 0.72rem 0.95rem;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.9);
    color: #1d4ed8;
    font-weight: 700;
    white-space: nowrap;
}

.application-settings__header-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 0.65rem;
    justify-content: flex-end;
}

.application-settings__switch {
    display: inline-flex;
    align-items: center;
    gap: 0.65rem;
    color: #475569;
    font-size: 0.9rem;
    font-weight: 600;
}

.application-settings__preview {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
}

.application-settings__preview-card {
    padding: 1rem 1.1rem;
    border: 1px solid rgba(148, 163, 184, 0.18);
    border-radius: 20px;
    background: rgba(255, 255, 255, 0.72);
}

.application-settings__preview-label {
    margin: 0 0 0.45rem;
    color: var(--admin-muted);
    font-size: 0.8rem;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.application-settings__actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
}

@media (max-width: 991.98px) {
    .application-settings__layout {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 767.98px) {
    .application-settings__hero,
    .application-settings__actions {
        flex-direction: column;
    }

    .application-settings__preview {
        grid-template-columns: 1fr;
    }
}
</style>
