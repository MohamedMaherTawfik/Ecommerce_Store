<template>
    <aside class="admin-sidebar">
        <div class="admin-sidebar__brand">
            <div class="admin-sidebar__brand-mark">
                <i class="bi bi-grid-1x2-fill"></i>
            </div>
            <div>
                <p class="admin-sidebar__brand-label">Control Center</p>
                <RouterLink :to="homeRoute" class="admin-sidebar__brand-link">
                    Admin Panel
                </RouterLink>
            </div>
        </div>

        <nav class="admin-sidebar__nav">
            <template v-for="item in visibleMenuItems" :key="item.key">
                <RouterLink
                    v-if="item.type === 'link'"
                    :to="item.to"
                    class="admin-sidebar__link"
                    :class="{ 'is-active': isItemActive(item) }"
                >
                    <i class="bi" :class="item.icon"></i>
                    <span>{{ item.label }}</span>
                </RouterLink>

                <div v-else class="admin-sidebar__group">
                    <button type="button" class="admin-sidebar__toggle" @click="toggleSection(item.key)">
                        <span class="admin-sidebar__toggle-copy">
                            <i class="bi" :class="item.icon"></i>
                            <span>{{ item.label }}</span>
                        </span>
                        <i
                            class="bi bi-plus-lg admin-sidebar__toggle-icon"
                            :class="{ 'is-open': sections[item.key] }"
                        ></i>
                    </button>

                    <Transition name="page-fade">
                        <div v-show="sections[item.key]" class="admin-sidebar__submenu">
                            <RouterLink
                                v-for="child in item.children"
                                :key="child.to"
                                :to="child.to"
                                class="admin-sidebar__sublink"
                                :class="{ 'is-active': isItemActive(child) }"
                            >
                                {{ child.label }}
                            </RouterLink>
                        </div>
                    </Transition>
                </div>
            </template>
        </nav>


        <div class="admin-sidebar__footer">
            <hr class="h-1 bg-gray-800 border-0 w-full" />
            <RouterLink to="/" class="admin-sidebar__link">
                <i class="bi bi-globe"></i>
                <span>Visit Site</span>
            </RouterLink>

            <button type="button" class="admin-sidebar__link logout-btn" @click="logout">
                <i class="bi bi-box-arrow-right"></i>
                <span>Logout</span>
            </button>
        </div>
    </aside>
</template>

<script setup>
import { computed, reactive, watch } from "vue";
import { RouterLink, useRoute } from "vue-router";
import { hasAdminPermission } from "@/config/adminAccess";
import adminAuthService from "@/services/adminAuthService";
import { getUserData } from "@/services/auth/authSession";

const route = useRoute();
const currentAdmin = computed(() => getUserData() || {});
const can = (permission) => hasAdminPermission(currentAdmin.value, permission);
const homeRoute = computed(() => adminAuthService.dashboardPath());

const menuItems = [
    {
        key: "dashboard",
        type: "link",
        label: "Dashboard",
        icon: "bi-house-door",
        to: "/admin",
        permission: "dashboard.view",
        exact: true,
    },
    {
        key: "customers",
        type: "group",
        label: "Customers",
        icon: "bi-people",
        children: [
            {
                label: "All Customers",
                to: "/admin/users",
                permission: "customers.view",
                exact: true,
            },
            {
                label: "Create User",
                to: "/admin/users/create",
                permission: "users.manage",
                exact: true,
            },
        ],
    },
    {
        key: "brands",
        type: "group",
        label: "Brands",
        icon: "bi-award",
        children: [
            { label: "All Brands", to: "/admin/brands", permission: "brands.manage", exact: true },
            { label: "Create Brand", to: "/admin/brands/create", permission: "brands.manage", exact: true },
            { label: "Trashed Brands", to: "/admin/brands/trashed", permission: "brands.manage", exact: true },
        ],
    },
    {
        key: "categories",
        type: "group",
        label: "Categories",
        icon: "bi-diagram-3",
        children: [
            { label: "All Categories", to: "/admin/categories", permission: "categories.manage", exact: true },
            { label: "Create Category", to: "/admin/categories/create", permission: "categories.manage", exact: true },
            { label: "Trashed Categories", to: "/admin/categories/trashed", permission: "categories.manage", exact: true },
        ],
    },
    {
        key: "products",
        type: "group",
        label: "Products",
        icon: "bi-box-seam",
        children: [
            { label: "All Products", to: "/admin/products", permission: "products.view", exact: true },
            { label: "Create Product", to: "/admin/products/create", permission: "products.write", exact: true },
            { label: "Trashed Products", to: "/admin/products/trashed", permission: "products.delete", exact: true },
        ],
    },
    {
        key: "orders",
        type: "group",
        label: "Orders",
        icon: "bi-credit-card",
        children: [
            { label: "All Orders", to: "/admin/payments/orders", permission: "orders.view" },
            { label: "Returns", to: "/admin/returns", permission: "returns.manage" },
        ],
    },
    {
        key: "operations",
        type: "group",
        label: "Operations",
        icon: "bi-sliders2",
        children: [
            { label: "Shipping", to: "/admin/shipping", permission: "shipping.manage" },
            { label: "Tax Rules", to: "/admin/tax-rules", permission: "shipping.manage" },
            { label: "Inventory", to: "/admin/inventory", permission: "inventory.manage" },
        ],
    },
    {
        key: "siteContent",
        type: "group",
        label: "Site Content",
        icon: "bi-window-stack",
        children: [
            { label: "Trust Items", to: "/admin/trust-items", permission: "site_content.manage" },
            { label: "Features", to: "/admin/features", permission: "site_content.manage" },
            { label: "Testimonials", to: "/admin/testimonials", permission: "site_content.manage" },
            { label: "Deals", to: "/admin/deals", permission: "site_content.manage" },
            { label: "Banners", to: "/admin/banners", permission: "site_content.manage" },
            { label: "Nav Links", to: "/admin/nav-links", permission: "site_content.manage" },
            { label: "Site Settings", to: "/admin/site-settings", permission: "site_settings.manage" },
        ],
    },
    {
        key: "contactMessages",
        type: "link",
        label: "Contact Messages",
        icon: "bi-envelope",
        to: "/admin/contact-messages",
        permission: "contact_messages.view",
    },
    {
        key: "coupons",
        type: "link",
        label: "Coupons",
        icon: "bi-ticket-perforated",
        to: "/admin/coupons",
        permission: "coupons.manage",
    },
    {
        key: "importExport",
        type: "link",
        label: "Import / Export",
        icon: "bi-arrow-left-right",
        to: "/admin/import-export",
        anyPermissions: ["imports.manage", "exports.manage"],
        exact: true,
    },
    {
        key: "tickets",
        type: "link",
        label: "Support Tickets",
        icon: "bi-life-preserver",
        to: "/admin/tickets",
        permission: "tickets.view",
    },
    {
        key: "blog",
        type: "link",
        label: "Blog",
        icon: "bi-journal-text",
        to: "/admin/blog",
        permission: "blog.view",
    },
    {
        key: "emailTemplates",
        type: "link",
        label: "Email Templates",
        icon: "bi-envelope",
        to: "/admin/email-templates",
        permission: "email_templates.manage",
    },
    {
        key: "permissions",
        type: "link",
        label: "Roles & Permissions",
        icon: "bi-shield-lock",
        to: "/admin/permissions",
        permission: "permissions.manage",
    },
    {
        key: "settings",
        type: "group",
        label: "Settings",
        icon: "bi-sliders",
        children: [
            { label: "Application Settings", to: "/admin/settings", permission: "settings.manage", exact: true },
            { label: "Database Settings", to: "/admin/settings/database", permission: "database_settings.manage" },
        ],
    },
];

const hasItemAccess = (item) => {
    if (item.anyPermissions) {
        return item.anyPermissions.some(can);
    }

    return can(item.permission);
};

const visibleMenuItems = computed(() =>
    menuItems.reduce((items, item) => {
        if (item.type === "link") {
            if (hasItemAccess(item)) items.push(item);
            return items;
        }

        const children = item.children.filter(hasItemAccess);
        if (children.length > 0) items.push({ ...item, children });
        return items;
    }, []),
);

const logout = () => {
    adminAuthService.logout();
};

const sections = reactive(
    Object.fromEntries(
        menuItems
            .filter((item) => item.type === "group")
            .map((item) => [item.key, false]),
    ),
);

const toggleSection = (sectionName) => {
    sections[sectionName] = !sections[sectionName];
};

const isItemActive = (item) =>
    item.exact ? route.path === item.to : route.path.startsWith(item.to);

watch(
    () => route.path,
    (newPath) => {
        visibleMenuItems.value
            .filter((item) => item.type === "group")
            .forEach((item) => {
                if (item.children.some((child) => newPath.startsWith(child.to))) {
                    sections[item.key] = true;
                }
            });
    },
    { immediate: true },
);
</script>

<style scoped>
.admin-sidebar {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    padding: 1.4rem;
    border-right: 1px solid rgba(255, 255, 255, 0.38);
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.88), rgba(255, 255, 255, 0.66));
    backdrop-filter: blur(18px);
    box-shadow: inset -1px 0 0 rgba(255, 255, 255, 0.4);
}

.admin-sidebar__brand {
    display: flex;
    align-items: center;
    gap: 0.95rem;
    margin-bottom: 1.6rem;
    padding: 1rem;
    border: 1px solid rgba(148, 163, 184, 0.16);
    border-radius: 22px;
    background: rgba(255, 255, 255, 0.82);
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
}

.admin-sidebar__brand-mark {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 46px;
    height: 46px;
    border-radius: 16px;
    background: linear-gradient(135deg, #2563eb, #38bdf8);
    color: #ffffff;
    font-size: 1.1rem;
    box-shadow: 0 10px 24px rgba(37, 99, 235, 0.25);
}

.admin-sidebar__brand-label {
    margin: 0 0 0.2rem;
    color: #64748b;
    font-size: 0.74rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.admin-sidebar__brand-link {
    color: #0f172a;
    font-size: 1.05rem;
    font-weight: 800;
    text-decoration: none;
}

.admin-sidebar__nav {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.admin-sidebar__link,
.admin-sidebar__toggle,
.admin-sidebar__sublink {
    text-decoration: none;
    transition: transform 0.2s ease, background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
}

.admin-sidebar__link,
.admin-sidebar__toggle {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.8rem;
    width: 100%;
    padding: 0.95rem 1rem;
    border: 1px solid transparent;
    border-radius: 16px;
    background: transparent;
    color: #334155;
    font-weight: 700;
}

.admin-sidebar__link {
    justify-content: flex-start;
}

.admin-sidebar__link i,
.admin-sidebar__toggle-copy i {
    color: #2563eb;
}

.admin-sidebar__toggle-copy {
    display: inline-flex;
    align-items: center;
    gap: 0.7rem;
}

.admin-sidebar__link:hover,
.admin-sidebar__toggle:hover,
.admin-sidebar__sublink:hover {
    transform: translateX(3px);
}

.admin-sidebar__link:hover,
.admin-sidebar__toggle:hover {
    background: rgba(255, 255, 255, 0.9);
    border-color: rgba(148, 163, 184, 0.16);
    box-shadow: 0 10px 20px rgba(15, 23, 42, 0.05);
}

.admin-sidebar__link.is-active,
.admin-sidebar__sublink.is-active {
    background: linear-gradient(135deg, rgba(37, 99, 235, 0.14), rgba(14, 165, 233, 0.12));
    border-color: rgba(37, 99, 235, 0.16);
    color: #1d4ed8;
    box-shadow: 0 10px 24px rgba(37, 99, 235, 0.1);
}

.admin-sidebar__group {
    border-radius: 18px;
    padding: 0.2rem;
}

.admin-sidebar__submenu {
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
    margin-top: 0.55rem;
    padding-left: 0.8rem;
}

.admin-sidebar__sublink {
    padding: 0.78rem 0.95rem;
    border: 1px solid transparent;
    border-radius: 14px;
    color: #475569;
    font-size: 0.92rem;
    font-weight: 600;
}

.admin-sidebar__toggle-icon {
    color: #64748b;
    font-size: 0.9rem;
    transition: transform 0.2s ease;
}

.admin-sidebar__toggle-icon.is-open {
    transform: rotate(45deg);
}

.admin-sidebar__divider {
    margin: 0.5rem 0;
    border: 0;
    border-top: 1px solid rgba(148, 163, 184, 0.12);
}

.logout-btn {
    color: #dc2626 !important;
}

.logout-btn i {
    color: #dc2626 !important;
}

.logout-btn:hover {
    background: rgba(220, 38, 38, 0.05) !important;
    border-color: rgba(220, 38, 38, 0.1) !important;
}

.admin-sidebar__footer {
    margin-top: auto;
    padding-top: 1.4rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    border-top: 1px solid rgba(148, 163, 184, 0.12);
}

@media (max-width: 991.98px) {
    .admin-sidebar {
        min-height: auto;
        border-right: 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.35);
    }
}
</style>
