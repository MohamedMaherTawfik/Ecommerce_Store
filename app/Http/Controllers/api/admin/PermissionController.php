<?php

namespace App\Http\Controllers\api\admin;

use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PermissionController extends Controller
{
    use ApiResponse;

    public function index()
    {
        return $this->success([
            'permissions' => Permission::orderBy('module')->orderBy('label')->get()->groupBy('module'),
            'roles' => DB::table('role_permissions')
                ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
                ->select('role_permissions.role', 'permissions.name')
                ->get()
                ->groupBy('role')
                ->map(fn ($items) => $items->pluck('name')->values()),
        ], 'Permissions loaded.');
    }

    public function updateRole(Request $request, string $role)
    {
        abort_if(in_array($role, ['admin', 'super_admin'], true), 422, 'Super admin roles always retain all permissions.');
        abort_unless(preg_match('/^[a-z][a-z0-9_-]{1,49}$/', $role), 422, 'Invalid role name.');

        $data = $request->validate([
            'permissions' => ['present', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);
        $ids = Permission::whereIn('name', $data['permissions'])->pluck('id');

        DB::transaction(function () use ($role, $ids) {
            DB::table('role_permissions')->where('role', $role)->delete();
            if ($ids->isNotEmpty()) {
                DB::table('role_permissions')->insert($ids->map(fn ($id) => [
                    'role' => $role,
                    'permission_id' => $id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->all());
            }
        });

        User::where('role', $role)->pluck('id')->each(
            fn ($userId) => Cache::forget("user_profile_{$userId}")
        );

        return $this->success([], "Permissions updated for {$role}.");
    }
}
