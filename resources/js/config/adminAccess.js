const SUPER_ADMIN_ROLES = new Set(["admin", "super_admin"]);

const ROLE_PERMISSIONS = Object.freeze({
    manager: [
        "dashboard.view",
        "products.view",
        "products.write",
        "products.delete",
        "categories.view",
        "categories.manage",
        "brands.view",
        "brands.manage",
        "orders.view",
        "orders.manage",
        "customers.view",
        "coupons.manage",
        "shipping.manage",
        "returns.manage",
        "reports.view",
        "inventory.manage",
        "blog.view",
        "blog.manage",
        "contact_messages.view",
        "contact_messages.reply",
    ],
    staff: [
        "dashboard.view",
        "products.view",
        "products.write",
        "categories.view",
        "brands.view",
        "orders.view",
        "tickets.view",
        "contact_messages.view",
    ],
    order_manager: [
        "orders.view",
        "orders.manage",
        "shipments.manage",
        "returns.manage",
        "invoices.manage",
    ],
});

const ADMIN_ROUTE_RULES = [
    { pattern: /^\/admin$/, permission: "dashboard.view" },
    { pattern: /^\/admin\/users\/create$/, permission: "users.manage" },
    { pattern: /^\/admin\/users\/[^/]+\/edit$/, permission: "users.manage" },
    { pattern: /^\/admin\/users(?:\/[^/]+)?$/, permission: "customers.view" },
    { pattern: /^\/admin\/brands(?:\/|$)/, permission: "brands.manage" },
    { pattern: /^\/admin\/categories(?:\/|$)/, permission: "categories.manage" },
    { pattern: /^\/admin\/products\/trashed$/, permission: "products.delete" },
    { pattern: /^\/admin\/products\/create$/, permission: "products.write" },
    { pattern: /^\/admin\/products\/[^/]+\/edit$/, permission: "products.write" },
    { pattern: /^\/admin\/products(?:\/|$)/, permission: "products.view" },
    { pattern: /^\/admin\/payments\/orders(?:\/|$)/, permission: "orders.view" },
    { pattern: /^\/admin\/coupons(?:\/|$)/, permission: "coupons.manage" },
    { pattern: /^\/admin\/contact-messages(?:\/|$)/, permission: "contact_messages.view" },
    { pattern: /^\/admin\/tickets(?:\/|$)/, permission: "tickets.view" },
    { pattern: /^\/admin\/blog(?:\/|$)/, permission: "blog.view" },
    { pattern: /^\/admin\/import-export$/, permission: "imports.manage" },
    { pattern: /^\/admin\/email-templates(?:\/|$)/, permission: "email_templates.manage" },
    { pattern: /^\/admin\/permissions(?:\/|$)/, permission: "permissions.manage" },
    { pattern: /^\/admin\/shipping(?:\/|$)/, permission: "shipping.manage" },
    { pattern: /^\/admin\/tax-rules(?:\/|$)/, permission: "shipping.manage" },
    { pattern: /^\/admin\/inventory(?:\/|$)/, permission: "inventory.manage" },
    { pattern: /^\/admin\/returns(?:\/|$)/, permission: "returns.manage" },
    { pattern: /^\/admin\/settings\/database(?:\/|$)/, permission: "database_settings.manage" },
    { pattern: /^\/admin\/settings(?:\/|$)/, permission: "settings.manage" },
    { pattern: /^\/admin\/site-settings(?:\/|$)/, permission: "site_settings.manage" },
    {
        pattern: /^\/admin\/(trust-items|features|testimonials|deals|banners|nav-links)(?:\/|$)/,
        permission: "site_content.manage",
    },
];

const normalizeAdminRole = (role) =>
    String(role || "")
        .trim()
        .toLowerCase();

const isSuperAdmin = (user = {}) =>
    SUPER_ADMIN_ROLES.has(normalizeAdminRole(user.role)) ||
    user.permissions?.includes("*");

const getRolePermissions = (user = {}) => {
    if (isSuperAdmin(user)) {
        return ["*"];
    }

    const role = normalizeAdminRole(user.role);

    if (Object.hasOwn(ROLE_PERMISSIONS, role)) {
        return ROLE_PERMISSIONS[role];
    }

    return Array.isArray(user.permissions) ? user.permissions : [];
};

const hasAdminPermission = (user, permission) => {
    if (!permission) {
        return true;
    }

    const permissions = getRolePermissions(user);
    return permissions.includes("*") || permissions.includes(permission);
};

const canAccessAdmin = (user = {}) =>
    isSuperAdmin(user) ||
    Object.hasOwn(ROLE_PERMISSIONS, normalizeAdminRole(user.role)) ||
    Boolean(user.can_access_admin) ||
    getRolePermissions(user).length > 0;

const permissionForAdminPath = (path) =>
    ADMIN_ROUTE_RULES.find((rule) => rule.pattern.test(path))?.permission ||
    "system.manage";

const canAccessAdminPath = (user, path) =>
    hasAdminPermission(user, permissionForAdminPath(path));

const getAdminHomePath = (user = {}) => {
    const candidates = [
        ["/admin", "dashboard.view"],
        ["/admin/payments/orders", "orders.view"],
        ["/admin/products", "products.view"],
        ["/admin/tickets", "tickets.view"],
        ["/admin/blog", "blog.view"],
        ["/admin/contact-messages", "contact_messages.view"],
        ["/admin/shipping", "shipping.manage"],
        ["/admin/inventory", "inventory.manage"],
        ["/admin/returns", "returns.manage"],
        ["/admin/import-export", "imports.manage"],
        ["/admin/email-templates", "email_templates.manage"],
        ["/admin/permissions", "permissions.manage"],
    ];

    return (
        candidates.find(([, permission]) =>
            hasAdminPermission(user, permission),
        )?.[0] || "/admin/auth"
    );
};

export {
    ROLE_PERMISSIONS,
    canAccessAdmin,
    canAccessAdminPath,
    getAdminHomePath,
    getRolePermissions,
    hasAdminPermission,
    isSuperAdmin,
    normalizeAdminRole,
    permissionForAdminPath,
};
