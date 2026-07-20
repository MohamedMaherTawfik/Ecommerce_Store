<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('permissions')->updateOrInsert(
            ['name' => 'permissions.manage'],
            [
                'module' => 'permissions',
                'label' => 'Manage role permissions',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        $id = DB::table('permissions')->where('name', 'permissions.manage')->value('id');

        DB::table('role_permissions')->updateOrInsert(
            ['role' => 'admin', 'permission_id' => $id],
            ['created_at' => now(), 'updated_at' => now()]
        );
    }

    public function down(): void
    {
        DB::table('permissions')->where('name', 'permissions.manage')->delete();
    }
};
