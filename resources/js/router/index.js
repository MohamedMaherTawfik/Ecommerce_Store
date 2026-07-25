import axios from "axios";
import { createRouter, createWebHistory } from "vue-router";
import {
    canAccessAdminPath,
    getAdminHomePath,
} from "../config/adminAccess";
import { setInstallMode } from "../services/ApiClient";
import authService from "../services/auth/Authservice";
import { getUserData } from "../services/auth/authSession";

const Home = () => import("../views/home/home.vue");
const About = () => import("../views/home/About.vue");
const Register = () => import("../views/auth/register.vue");
const Profile = () => import("../views/home/user/profile.vue");

const GoogleSuccess = () => import("../views/auth/GoogleSuccess.vue");
const GoogleError = () => import("../views/auth/GoogleError.vue");
const GoogleLogin = () => import("../views/auth/GoogleLogin.vue");
const Contact = () => import("../views/home/contact.vue");
const Wallet = () => import("../views/home/user/wallet.vue");
const AdminLogin = () => import("../views/admin/auth/login.vue");
const AdminDashboard = () => import("../views/admin/index.vue");
const UserIndex = () => import("../views/admin/users/Index.vue");
const UserCreate = () => import("../views/admin/users/Create.vue");
const UserShow = () => import("../views/admin/users/Show.vue");
const UserEdit = () => import("../views/admin/users/Edit.vue");
const BrandIndex = () => import("../views/admin/brands/Index.vue");
const BrandCreate = () => import("../views/admin/brands/Create.vue");
const BrandShow = () => import("../views/admin/brands/Show.vue");
const BrandEdit = () => import("../views/admin/brands/Edit.vue");
const BrandTrashed = () => import("../views/admin/brands/Trashed.vue");
const CategoryIndex = () => import("../views/admin/categories/Index.vue");
const CategoryCreate = () => import("../views/admin/categories/Create.vue");
const CategoryShow = () => import("../views/admin/categories/Show.vue");
const CategoryEdit = () => import("../views/admin/categories/Edit.vue");
const CategoryTrashed = () => import("../views/admin/categories/Trashed.vue");
const ProductIndex = () => import("../views/admin/products/Index.vue");
const ProductCreate = () => import("../views/admin/products/Create.vue");
const ProductShow = () => import("../views/admin/products/Show.vue");
const ProductEdit = () => import("../views/admin/products/Edit.vue");
const ProductTrashed = () => import("../views/admin/products/Trashed.vue");
const HomeProductIndex = () => import("../views/home/products/Index.vue");
const HomeProductShow = () => import("../views/home/products/Show.vue");
const CartIndex = () => import("../views/home/cart/Index.vue");
const WishlistIndex = () => import("../views/home/wishlist/Index.vue");
const OrderTracking = () => import("../views/home/orders/Show.vue");
const OrdersList = () => import("../views/admin/payments/OrdersList.vue");
const OrderDetails = () => import("../views/admin/payments/OrderDetails.vue");
const CouponIndex = () => import("../views/admin/coupons/Index.vue");
const Settings = () => import("../views/admin/settings/settings.vue");
const DatabaseSettings = () =>
    import("../views/admin/settings/DatabaseSettings.vue");

const TrustItemIndex = () => import("../views/admin/trust_items/index.vue");
const TrustItemCreate = () => import("../views/admin/trust_items/create.vue");
const TrustItemEdit = () => import("../views/admin/trust_items/edit.vue");
const FeatureIndex = () => import("../views/admin/features/index.vue");
const FeatureCreate = () => import("../views/admin/features/create.vue");
const FeatureEdit = () => import("../views/admin/features/edit.vue");
const TestimonialIndex = () => import("../views/admin/testimonials/index.vue");
const TestimonialCreate = () =>
    import("../views/admin/testimonials/create.vue");
const TestimonialEdit = () => import("../views/admin/testimonials/edit.vue");
const DealIndex = () => import("../views/admin/deals/index.vue");
const DealCreate = () => import("../views/admin/deals/create.vue");
const DealEdit = () => import("../views/admin/deals/edit.vue");
const BannerIndex = () => import("../views/admin/banners/index.vue");
const BannerCreate = () => import("../views/admin/banners/create.vue");
const BannerEdit = () => import("../views/admin/banners/edit.vue");
const NavLinkIndex = () => import("../views/admin/nav_links/index.vue");
const NavLinkCreate = () => import("../views/admin/nav_links/create.vue");
const NavLinkEdit = () => import("../views/admin/nav_links/edit.vue");
const SiteSettingIndex = () => import("../views/admin/site_settings/index.vue");
const SiteSettingCreate = () =>
    import("../views/admin/site_settings/create.vue");
const SiteSettingEdit = () => import("../views/admin/site_settings/edit.vue");
const InstallWizard = () => import("../views/installer/InstallWizard.vue");
const ImportExport = () => import("../views/admin/import_export/Index.vue");
const AdminTickets = () => import("../views/admin/tickets/Index.vue");
const AdminTicketShow = () => import("../views/admin/tickets/Show.vue");
const AdminBlog = () => import("../views/admin/blog/Index.vue");
const AdminEmailTemplates = () =>
    import("../views/admin/email_templates/Index.vue");
const BlogIndex = () => import("../views/home/blog/Index.vue");
const BlogShow = () => import("../views/home/blog/Show.vue");
const SupportIndex = () => import("../views/home/support/Index.vue");
const SupportShow = () => import("../views/home/support/Show.vue");
const AdminPermissions = () => import("../views/admin/permissions/Index.vue");
const AdminShipping = () => import("../views/admin/shipping/Index.vue");
const AdminTaxRules = () => import("../views/admin/tax_rules/Index.vue");
const AdminInventory = () => import("../views/admin/inventory/Index.vue");
const AdminReturns = () => import("../views/admin/returns/Index.vue");

const ContactMessageIndex = () =>
    import("../views/admin/contact_messages/Index.vue");
const ContactMessageShow = () =>
    import("../views/admin/contact_messages/Show.vue");

const INSTALL_ROUTES = ["/install", "/installer"];

let installStatusCache = null;
let installStatusPromise = null;

const fetchInstallStatus = async (force = false) => {
    if (!force && installStatusCache !== null) {
        return installStatusCache;
    }

    if (!force && installStatusPromise) {
        return installStatusPromise;
    }

    installStatusPromise = axios
        .get("/api/installer/status", {
            headers: { Accept: "application/json" },
            params: { t: Date.now() },
        })
        .then((response) => {
            installStatusCache = Boolean(response?.data?.data?.installed);

            return installStatusCache;
        })
        .catch((error) => {
            console.error("[InstallerGuard] status request failed", {
                path: window.location.pathname,
                status: error?.response?.status,
                message: error?.response?.data?.message || error.message,
            });

            throw error;
        })
        .finally(() => {
            installStatusPromise = null;
        });

    return installStatusPromise;
};

const routes = [
    {
        path: "/",
        redirect: () => {
            const lang = localStorage.getItem("language") || "ar";
            return `/${lang}`;
        },
    },
    {
        path: "/:lang/",
        component: Home,
        meta: { hideNavbar: false, hideFooter: false },
    },
    {
        path: "/:lang/profile",
        component: Profile,
    },
    {
        path: "/:lang/wallet",
        component: Wallet,
    },
    {
        path: "/:lang/contact",
        component: Contact,
    },
    {
        path: "/:lang/products",
        component: HomeProductIndex,
    },
    {
        path: "/:lang/products/category/:category",
        component: HomeProductIndex,
    },
    {
        path: "/:lang/products/:product",
        component: HomeProductShow,
    },
    {
        path: "/:lang/cart",
        component: CartIndex,
    },
    {
        path: "/:lang/wishlist",
        component: WishlistIndex,
    },
    { path: "/:lang/blog", component: BlogIndex },
    { path: "/:lang/blog/category/:category", component: BlogIndex },
    { path: "/:lang/blog/tag/:tag", component: BlogIndex },
    { path: "/:lang/blog/:slug", component: BlogShow },
    { path: "/:lang/support", component: SupportIndex },
    { path: "/:lang/support/:id", component: SupportShow },
    {
        path: "/:lang/orders/:id?",
        component: OrderTracking,
    },
    {
        path: "/:lang/auth",
        component: Register,
        meta: { hideNavbar: true, hideFooter: true },
    },

    {
        path: "/auth/google-success",
        name: "google-success",
        component: GoogleSuccess,
        meta: { hideNavbar: true, hideFooter: true },
    },
    {
        path: "/auth/google-login",
        name: "google-login",
        component: GoogleLogin,
        meta: { hideNavbar: true, hideFooter: true },
    },
    {
        path: "/auth/google-error",
        name: "google-error",
        component: GoogleError,
        meta: { hideNavbar: true, hideFooter: true },
    },
    {
        path: "/admin/auth",
        name: "admin-login",
        component: AdminLogin,
        meta: { hideNavbar: true, hideFooter: true, guestOnly: true },
    },
    {
        path: "/admin",
        name: "admin-dashboard",
        component: AdminDashboard,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/users",
        name: "admin-users-index",
        component: UserIndex,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/users/create",
        name: "admin-users-create",
        component: UserCreate,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/users/:id",
        name: "admin-users-show",
        component: UserShow,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/users/:id/edit",
        name: "admin-users-edit",
        component: UserEdit,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/brands",
        name: "admin-brands-index",
        component: BrandIndex,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/brands/trashed",
        name: "admin-brands-trashed",
        component: BrandTrashed,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/brands/create",
        name: "admin-brands-create",
        component: BrandCreate,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/brands/:id",
        name: "admin-brands-show",
        component: BrandShow,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/brands/:id/edit",
        name: "admin-brands-edit",
        component: BrandEdit,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/categories",
        name: "admin-categories-index",
        component: CategoryIndex,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/categories/trashed",
        name: "admin-categories-trashed",
        component: CategoryTrashed,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/categories/create",
        name: "admin-categories-create",
        component: CategoryCreate,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/categories/:id",
        name: "admin-categories-show",
        component: CategoryShow,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/categories/:id/edit",
        name: "admin-categories-edit",
        component: CategoryEdit,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/products",
        name: "admin-products-index",
        component: ProductIndex,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/products/trashed",
        name: "admin-products-trashed",
        component: ProductTrashed,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/products/create",
        name: "admin-products-create",
        component: ProductCreate,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/products/:id",
        name: "admin-products-show",
        component: ProductShow,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/products/:id/edit",
        name: "admin-products-edit",
        component: ProductEdit,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/payments/orders",
        name: "admin-payments-orders",
        component: OrdersList,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/payments/orders/:id",
        name: "admin-payments-orders-show",
        component: OrderDetails,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/coupons",
        name: "admin-coupons-index",
        component: CouponIndex,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/settings",
        name: "admin-settings",
        component: Settings,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/settings/database",
        name: "admin-database-settings",
        component: DatabaseSettings,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/trust-items",
        component: TrustItemIndex,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/trust-items/create",
        component: TrustItemCreate,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/trust-items/:id/edit",
        component: TrustItemEdit,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/features",
        component: FeatureIndex,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/features/create",
        component: FeatureCreate,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/features/:id/edit",
        component: FeatureEdit,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/testimonials",
        component: TestimonialIndex,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/testimonials/create",
        component: TestimonialCreate,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/testimonials/:id/edit",
        component: TestimonialEdit,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/deals",
        component: DealIndex,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/deals/create",
        component: DealCreate,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/deals/:id/edit",
        component: DealEdit,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/banners",
        component: BannerIndex,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/banners/create",
        component: BannerCreate,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/banners/:id/edit",
        component: BannerEdit,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/nav-links",
        component: NavLinkIndex,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/nav-links/create",
        component: NavLinkCreate,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/nav-links/:id/edit",
        component: NavLinkEdit,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/site-settings",
        component: SiteSettingIndex,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/site-settings/create",
        component: SiteSettingCreate,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/site-settings/:key/edit",
        component: SiteSettingEdit,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/contact-messages",
        name: "admin-contact-messages-index",
        component: ContactMessageIndex,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/contact-messages/:id",
        name: "admin-contact-messages-show",
        component: ContactMessageShow,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/import-export",
        name: "admin-import-export",
        component: ImportExport,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/tickets",
        component: AdminTickets,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/tickets/:id",
        component: AdminTicketShow,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/blog",
        component: AdminBlog,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/email-templates",
        component: AdminEmailTemplates,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/permissions",
        component: AdminPermissions,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/shipping",
        component: AdminShipping,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/tax-rules",
        component: AdminTaxRules,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/inventory",
        component: AdminInventory,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },
    {
        path: "/admin/returns",
        component: AdminReturns,
        meta: { hideNavbar: true, hideFooter: true, requiresAdmin: true },
    },

    {
        path: "/install",
        name: "install",
        alias: "/installer",
        component: InstallWizard,
        meta: { hideNavbar: true, hideFooter: true, isInstallRoute: true },
    },
    {
        path: "/:lang/about",
        alias: "/:lang/who",
        component: About,
        meta: { hideNavbar: false, hideFooter: false },
    },
    {
        path: "/:lang/:pathMatch(.*)*",
        redirect: (to) => `/${to.params.lang || "en"}`,
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach(async (to) => {
    const isLoggedIn = authService.isLoggedIn();
    const isAdmin = authService.isAdmin();
    const lang = localStorage.getItem("language") || "en";
    const isInstallRoute =
        INSTALL_ROUTES.includes(to.path) || to.meta.isInstallRoute;
    let installed = null;

    // Do not block rendering for install status. Trigger async check.
    if (installed === null) {
        fetchInstallStatus(isInstallRoute).then((status) => {
            if (status === false && !isInstallRoute) {
                setInstallMode(true);
                router.push({ name: "install" });
            } else if (status === true && isInstallRoute) {
                setInstallMode(false);
                router.push(`/${lang}/`);
            }
        }).catch(() => {
            if (isInstallRoute) setInstallMode(true);
        });
    } else {
        if (installed === false && !isInstallRoute) {
            setInstallMode(true);
            return { name: "install" };
        }
        if (installed === true && isInstallRoute) {
            setInstallMode(false);
            return `/${lang}/`;
        }
    }

    setInstallMode(installed === false);

    if (to.meta.guestOnly && isLoggedIn && isAdmin) {
        return { name: "admin-dashboard" };
    }

    if (to.meta.requiresAdmin) {
        if (!isLoggedIn) {
            return { name: "admin-login" };
        }

        if (!isAdmin) {
            return `/${lang}/auth`;
        }

        const admin = getUserData() || { role: authService.getRole() };

        if (!canAccessAdminPath(admin, to.path)) {
            const fallback = getAdminHomePath(admin);

            return fallback === to.path ? { name: "admin-login" } : fallback;
        }
    }

    // Don't intercept Google OAuth callback pages — they must process tokens first
    const isGoogleAuthRoute =
        to.path === "/auth/google-success" || to.path === "/auth/google-error";
    if (to.path.includes("/auth") && isLoggedIn && !isGoogleAuthRoute) {
        if (isAdmin) {
            return { name: "admin-dashboard" };
        }

        return `/${lang}/`;
    }

    if (
        (to.path.includes("profile") ||
            to.path.includes("wallet") ||
            to.path.includes("cart") ||
            to.path.includes("wishlist") ||
            to.path.includes("orders")) &&
        !isLoggedIn
    ) {
        return `/${lang}/auth`;
    }

    return true;
});

export default router;
