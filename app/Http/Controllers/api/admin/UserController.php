<?php

namespace App\Http\Controllers\api\admin;

use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Support\TaggedCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    use ApiResponse;

    private $cacheTime = 1200;

    // =========================
    // INDEX (LATEST USERS)
    // =========================
    public function index(Request $request)
    {
        try {
            $customersOnly = ! $request->user()->isSuperAdmin();
            $cacheKey = $customersOnly ? 'customers_latest_10' : 'users_latest_10';

            $users = TaggedCache::tags('users')->remember(
                $cacheKey,
                $this->cacheTime,
                fn () => User::query()
                    ->when($customersOnly, fn ($query) => $query->where('role', 'user'))
                    ->latest()
                    ->where('is_active', 1)
                    ->take(10)
                    ->get()
            );

            return $this->success($users, 'Latest Users');
        } catch (\Throwable $e) {
            return $this->error('something went wrong');
        }
    }

    // =========================
    // ALL (PAGINATED)
    // =========================
    public function all(Request $request)
    {
        try {
            $page = request('page', 1);
            $customersOnly = ! $request->user()->isSuperAdmin();
            $cacheKey = ($customersOnly ? 'customers' : 'users')."_all_page_$page";

            $users = TaggedCache::tags('users')->remember(
                $cacheKey,
                $this->cacheTime,
                fn () => User::query()
                    ->when($customersOnly, fn ($query) => $query->where('role', 'user'))
                    ->latest()
                    ->paginate(20)
            );

            return $this->success($users, 'All Users');
        } catch (\Throwable $e) {
            return $this->error('something went wrong');
        }
    }

    // =========================
    // COUNT
    // =========================
    public function count(Request $request)
    {
        try {
            $customersOnly = ! $request->user()->isSuperAdmin();
            $cacheKey = $customersOnly ? 'customers_count' : 'users_count';

            $count = TaggedCache::tags('users')->remember(
                $cacheKey,
                $this->cacheTime,
                fn () => User::query()
                    ->when($customersOnly, fn ($query) => $query->where('role', 'user'))
                    ->count()
            );

            return $this->success($count, 'Users Count');
        } catch (\Throwable $e) {
            Log::error($e);

            return $this->error('something went wrong');
        }
    }

    // =========================
    // SHOW
    // =========================
    public function show(Request $request, int $id)
    {
        try {
            $user = TaggedCache::tags('users')->remember(
                "user_$id",
                $this->cacheTime,
                fn () => User::find($id)
            );

            if (! $user) {
                return $this->notFound('User Not Found');
            }

            if (! $request->user()->isSuperAdmin() && $user->role !== 'user') {
                return $this->forbidden('You can only view customer accounts.');
            }

            return $this->success($user, 'User Details');
        } catch (\Throwable $e) {
            Log::error($e);

            return $this->error('something went wrong');
        }
    }

    // =========================
    // PRODUCTS (placeholder)
    // =========================
    public function products()
    {
        return $this->success([], 'Products');
    }

    // =========================
    // CREATE
    // =========================
    public function create(UserRequest $request)
    {
        try {
            DB::beginTransaction();
            $data = $request->validated();
            $data['password'] = bcrypt($data['password']);
            $user = User::create($data);
            TaggedCache::tags('users')->flush();
            DB::commit();

            return $this->success($user, 'User Created Successfully');
        } catch (\Throwable $e) {
            Log::error($e);
            DB::rollBack();

            return $this->error('something went wrong');
        }
    }

    // =========================
    // UPDATE
    // =========================
    public function update(Request $request, int $id)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
            'phone' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:8'],
            'is_active' => ['sometimes', 'boolean'],
            'role' => ['sometimes', 'string', 'regex:/^[a-z][a-z0-9_-]{1,49}$/'],
        ]);

        try {
            $user = User::find($id);

            if (! $user) {
                return $this->notFound('User Not Found');
            }

            if (
                $request->user()?->is($user) &&
                isset($data['role']) &&
                ! in_array($data['role'], ['admin', 'super_admin'], true)
            ) {
                return $this->error('You cannot remove your own primary admin role.', 422);
            }

            if (! empty($data['password'])) {
                $data['password'] = bcrypt($data['password']);
            } else {
                unset($data['password']);
            }

            $user->update($data);

            TaggedCache::tags('users')->flush();

            return $this->success($user, 'User Updated Successfully');
        } catch (\Throwable $e) {
            Log::error($e);

            return $this->error('something went wrong');
        }
    }

    // =========================
    // DELETE
    // =========================
    public function destroy(int $id)
    {
        try {
            $user = User::find($id);
            if (! $user) {
                return $this->notFound('User Not Found');
            }
            $user->delete();
            TaggedCache::tags('users')->flush();

            return $this->success([], 'User Deleted Successfully');
        } catch (\Throwable $e) {
            Log::error($e);

            return $this->error('something went wrong');
        }
    }

    // =========================
    // CLEAR CACHE
    // =========================
    public function clearCache()
    {
        try {
            TaggedCache::tags('users')->flush();

            return $this->success([], 'Users Cache Cleared Successfully');
        } catch (\Throwable $e) {
            Log::error($e);

            return $this->error('something went wrong');
        }
    }
}
