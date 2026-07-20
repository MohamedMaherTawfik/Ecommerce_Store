import { describe, expect, it } from "vitest";
import {
    canAccessAdminPath,
    getAdminHomePath,
    hasAdminPermission,
    isSuperAdmin,
} from "../adminAccess";

describe("admin access matrix", () => {
    it("treats current and legacy super admin roles as unrestricted", () => {
        expect(isSuperAdmin({ role: "super_admin" })).toBe(true);
        expect(isSuperAdmin({ role: "admin" })).toBe(true);
        expect(canAccessAdminPath({ role: "super_admin" }, "/admin/settings")).toBe(true);
    });

    it("allows managers into store operations but not system settings", () => {
        const manager = { role: "manager" };

        expect(canAccessAdminPath(manager, "/admin/products")).toBe(true);
        expect(canAccessAdminPath(manager, "/admin/users/12")).toBe(true);
        expect(canAccessAdminPath(manager, "/admin/settings")).toBe(false);
        expect(canAccessAdminPath(manager, "/admin/permissions")).toBe(false);
    });

    it("allows staff to write products without delete access", () => {
        const staff = { role: "staff" };

        expect(canAccessAdminPath(staff, "/admin/products/12/edit")).toBe(true);
        expect(canAccessAdminPath(staff, "/admin/products/trashed")).toBe(false);
        expect(hasAdminPermission(staff, "products.delete")).toBe(false);
        expect(hasAdminPermission(staff, "orders.manage")).toBe(false);
    });

    it("sends order managers directly to orders", () => {
        const orderManager = { role: "order_manager" };

        expect(getAdminHomePath(orderManager)).toBe("/admin/payments/orders");
        expect(canAccessAdminPath(orderManager, "/admin/payments/orders/42")).toBe(true);
        expect(canAccessAdminPath(orderManager, "/admin/products")).toBe(false);
    });
});
