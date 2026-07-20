<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ROLE_PERMISSIONS = [
        'manager' => [
            'dashboard.view',
            'products.view',
            'products.write',
            'products.delete',
            'categories.view',
            'categories.manage',
            'brands.view',
            'brands.manage',
            'orders.view',
            'orders.manage',
            'customers.view',
            'coupons.manage',
            'shipping.manage',
            'returns.manage',
            'reports.view',
            'inventory.manage',
            'blog.view',
            'blog.manage',
            'contact_messages.view',
            'contact_messages.reply',
        ],
        'staff' => [
            'dashboard.view',
            'products.view',
            'products.write',
            'categories.view',
            'brands.view',
            'orders.view',
            'tickets.view',
            'contact_messages.view',
        ],
        'order_manager' => [
            'orders.view',
            'orders.manage',
            'shipments.manage',
            'returns.manage',
            'invoices.manage',
        ],
    ];

    private const PERMISSIONS = [
        'dashboard.view' => ['dashboard', 'View dashboard'],
        'products.view' => ['products', 'View products'],
        'products.write' => ['products', 'Create and edit products'],
        'products.delete' => ['products', 'Delete and restore products'],
        'categories.view' => ['categories', 'View categories'],
        'categories.manage' => ['categories', 'Manage categories'],
        'brands.view' => ['brands', 'View brands'],
        'brands.manage' => ['brands', 'Manage brands'],
        'orders.view' => ['orders', 'View orders'],
        'orders.manage' => ['orders', 'Manage order and shipping statuses'],
        'orders.delete' => ['orders', 'Delete orders'],
        'customers.view' => ['customers', 'View customers'],
        'users.manage' => ['users', 'Manage administrative users'],
        'coupons.manage' => ['coupons', 'Manage coupons'],
        'shipping.manage' => ['shipping', 'Manage shipping configuration'],
        'shipments.manage' => ['shipments', 'Create and manage shipments'],
        'returns.manage' => ['returns', 'Manage returns'],
        'invoices.manage' => ['invoices', 'View and download invoices'],
        'inventory.manage' => ['inventory', 'Manage inventory'],
        'contact_messages.view' => ['contact_messages', 'View contact messages'],
        'contact_messages.reply' => ['contact_messages', 'Reply to contact messages'],
        'site_content.manage' => ['site_content', 'Manage site content'],
        'site_settings.manage' => ['site_settings', 'Manage site settings'],
        'settings.manage' => ['settings', 'Manage application settings'],
        'database_settings.manage' => ['settings', 'Manage database settings'],
        'system.manage' => ['system', 'Manage system administration pages'],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('role_permissions')) {
            return;
        }

        $now = now();

        foreach (self::PERMISSIONS as $name => [$module, $label]) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $name],
                [
                    'module' => $module,
                    'label' => $label,
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('name', collect(self::ROLE_PERMISSIONS)->flatten()->unique())
            ->pluck('id', 'name');

        foreach (self::ROLE_PERMISSIONS as $role => $permissions) {
            DB::table('role_permissions')->where('role', $role)->delete();

            DB::table('role_permissions')->insert(
                collect($permissions)
                    ->map(fn (string $permission) => [
                        'role' => $role,
                        'permission_id' => $permissionIds[$permission],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])
                    ->all(),
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('role_permissions')) {
            return;
        }

        DB::table('role_permissions')
            ->whereIn('role', array_keys(self::ROLE_PERMISSIONS))
            ->delete();

        DB::table('permissions')
            ->whereIn('name', array_keys(self::PERMISSIONS))
            ->delete();
    }
};
