<?php

namespace App\Http\Controllers\api\admin;

use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CouponRequest;
use App\Models\Coupon;
use App\Support\TaggedCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CouponController extends Controller
{
    use ApiResponse;

    private $cacheTime = 600;

    // =========================
    // INDEX
    // =========================
    public function index(Request $request)
    {
        try {
            $page = $request->input('page', 1);
            $search = $request->query('search');

            $cacheKey = "coupons_page_{$page}_search_".md5($search);

            $coupons = TaggedCache::tags(['coupons'])->remember(
                $cacheKey,
                $this->cacheTime,
                function () use ($request, $search) {
                    return Coupon::query()
                        ->when($search, fn ($q) => $q->where('code', 'like', "%{$search}%"))
                        ->latest()
                        ->paginate($request->integer('per_page', 15));
                }
            );

            return $this->success($coupons, 'Coupons');
        } catch (\Throwable $e) {
            Log::error($e);

            return $this->error('Something went wrong');
        }
    }

    // =========================
    // STORE
    // =========================
    public function store(CouponRequest $request)
    {
        try {
            DB::beginTransaction();

            $coupon = Coupon::create($request->validated());

            TaggedCache::tags(['coupons'])->flush();

            DB::commit();

            return $this->success($coupon, 'Coupon created successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error($e);

            return $this->error('Something went wrong');
        }
    }

    // =========================
    // SHOW
    // =========================
    public function show(int $id)
    {
        try {
            $coupon = TaggedCache::tags(['coupons'])->remember(
                "coupon_$id",
                $this->cacheTime,
                fn () => Coupon::findOrFail($id)
            );

            return $this->success($coupon, 'Coupon details.');
        } catch (\Throwable $e) {
            Log::error($e);

            return $this->error('Something went wrong');
        }
    }

    // =========================
    // UPDATE
    // =========================
    public function update(CouponRequest $request, int $id)
    {
        try {
            DB::beginTransaction();

            $coupon = Coupon::findOrFail($id);

            $coupon->update($request->validated());

            TaggedCache::tags(['coupons'])->flush();

            DB::commit();

            return $this->success($coupon, 'Coupon updated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error($e);

            return $this->error('Something went wrong');
        }
    }

    // =========================
    // DELETE
    // =========================
    public function destroy(int $id)
    {
        try {
            DB::beginTransaction();

            $coupon = Coupon::findOrFail($id);

            $coupon->delete();

            TaggedCache::tags(['coupons'])->flush();

            DB::commit();

            return $this->success([], 'Coupon deleted successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error($e);

            return $this->error('Something went wrong');
        }
    }
}
