<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ModulePermissionAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_custom_dashboard_role_can_only_access_assigned_modules(): void
    {
        $manager = User::factory()->create([
            'role' => 'analyst',
            'is_active' => true,
        ]);
        $permission = Permission::where('name', 'reports.view')->firstOrFail();

        DB::table('role_permissions')->insert([
            'role' => 'analyst',
            'permission_id' => $permission->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($manager);

        $this->getJson('/api/admin/analytics/sales')->assertOk();
        $this->getJson('/api/admin/dashboard/statistics')->assertOk();
        $this->getJson('/api/admin/blog/posts')->assertForbidden();
        $this->getJson('/api/admin/users')->assertForbidden();
    }

    public function test_user_resource_exposes_effective_dashboard_permissions(): void
    {
        $manager = User::factory()->create(['role' => 'support_agent']);
        $permission = Permission::where('name', 'tickets.view')->firstOrFail();

        DB::table('role_permissions')->insert([
            'role' => 'support_agent',
            'permission_id' => $permission->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($manager);

        $this->getJson('/api/v1/users/profile')
            ->assertOk()
            ->assertJsonPath('data.user.can_access_admin', true)
            ->assertJsonPath('data.user.permissions.0', 'tickets.view');
    }
}
