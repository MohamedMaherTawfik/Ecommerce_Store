<?php

namespace App\Http\Controllers\api\admin;

use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\BrandRequest;
use App\Models\brands;
use App\Services\Media\OptimizedImageStorage;
use App\Support\TaggedCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BrandController extends Controller
{
    use ApiResponse;

    private $cacheTime = 3600;

    // =========================
    // INDEX
    // =========================
    public function index()
    {
        try {
            $page = request('page', 1);

            $brands = TaggedCache::tags(['brands'])->remember(
                "index_page_$page",
                $this->cacheTime,
                fn () => brands::latest()->paginate(6)
            );

            return $this->success($brands, 'All Brands');
        } catch (\Throwable $e) {
            Log::error($e->getMessage());

            return $this->error('something went wrong');
        }
    }

    // =========================
    // ALL
    // =========================
    public function all()
    {
        try {
            $brands = TaggedCache::tags(['brands'])->remember(
                'all',
                $this->cacheTime,
                fn () => brands::all()
            );

            return $this->success($brands, 'All Brands');
        } catch (\Throwable $e) {
            Log::error($e->getMessage());

            return $this->error('something went wrong');
        }
    }

    // =========================
    // COUNT
    // =========================
    public function count()
    {
        try {
            $count = TaggedCache::tags(['brands'])->remember(
                'count',
                $this->cacheTime,
                fn () => brands::count()
            );

            return $this->success($count, 'Brands Count');
        } catch (\Throwable $e) {
            Log::error($e->getMessage());

            return $this->error('something went wrong');
        }
    }

    // =========================
    // SHOW
    // =========================
    public function show(int $id)
    {
        try {
            $brand = TaggedCache::tags(['brands'])->remember(
                "brand_$id",
                $this->cacheTime,
                fn () => brands::find($id)
            );

            if (! $brand) {
                return $this->notFound('Brand Not Found');
            }

            return $this->success($brand, 'Brand Details');
        } catch (\Throwable $e) {
            Log::error($e->getMessage());

            return $this->error('something went wrong');
        }
    }

    // =========================
    // PRODUCTS
    // =========================
    public function products(int $id)
    {
        try {
            $brand = brands::with('products')->find($id);

            if (! $brand) {
                return $this->notFound('Brand Not Found');
            }

            return $this->success($brand->products, 'Brand Products');
        } catch (\Throwable $e) {
            Log::error($e->getMessage());

            return $this->error('something went wrong');
        }
    }

    // =========================
    // CREATE
    // =========================
    public function create(BrandRequest $request, OptimizedImageStorage $imageStorage)
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();

            if ($request->hasFile('image')) {
                $data['image'] = $imageStorage->store($request->file('image'), 'brands', 960, 960);
            }

            $data['slug'] = $data['name'].'-'.time();

            $brand = brands::create($data);

            $this->clearBrandCache();

            DB::commit();

            return $this->success($brand, 'Brand Created Successfully');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error($e->getMessage());

            return $this->error('something went wrong');
        }
    }

    // =========================
    // UPDATE
    // =========================
    public function update(BrandRequest $request, int $id, OptimizedImageStorage $imageStorage)
    {
        try {
            DB::beginTransaction();

            $brand = brands::find($id);

            if (! $brand) {
                return $this->notFound('Brand Not Found');
            }

            $data = $request->validated();

            // 🔥 image handling
            if ($request->hasFile('image')) {

                // delete old image
                if ($brand->image && Storage::disk('public')->exists($brand->image)) {
                    Storage::disk('public')->delete($brand->image);
                }

                // store new
                $data['image'] = $imageStorage->store($request->file('image'), 'brands', 960, 960);
            }

            $brand->update($data);

            $this->clearBrandCache($id);

            DB::commit();

            return $this->success($brand, 'Brand Updated Successfully');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error($e->getMessage());

            return $this->error('something went wrong');
        }
    }

    // =========================
    // DELETE
    // =========================
    public function destroy(int $id)
    {
        try {
            DB::beginTransaction();

            $brand = brands::find($id);

            if (! $brand) {
                return $this->notFound('Brand Not Found');
            }

            // 🔥 delete image
            if ($brand->image && Storage::disk('public')->exists($brand->image)) {
                Storage::disk('public')->delete($brand->image);
            }

            $brand->delete();

            $this->clearBrandCache($id);

            DB::commit();

            return $this->success([], 'Brand Deleted Successfully');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error($e->getMessage());

            return $this->error('something went wrong');
        }
    }

    // =========================
    // TRASHED
    // =========================
    public function trashed()
    {
        try {
            $page = request('page', 1);

            $brands = brands::onlyTrashed()->latest()->paginate(6);

            return $this->success($brands, 'Trashed Brands');
        } catch (\Throwable $e) {
            Log::error($e->getMessage());

            return $this->error('something went wrong');
        }
    }

    // =========================
    // RESTORE
    // =========================
    public function restore(int $id)
    {
        try {
            DB::beginTransaction();

            $brand = brands::onlyTrashed()->find($id);

            if (! $brand) {
                return $this->notFound('Brand Not Found');
            }

            $brand->restore();

            $this->clearBrandCache();

            DB::commit();

            return $this->success([], 'Brand restored successfully');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error($e->getMessage());

            return $this->error('something went wrong');
        }
    }

    // =========================
    // CACHE CLEAR
    // =========================
    private function clearBrandCache($id = null)
    {
        try {
            TaggedCache::tags(['brands'])->flush();

            if ($id) {
                Cache::forget("brand_$id");
            }
        } catch (\Throwable $e) {
            Log::error($e->getMessage());
        }
    }
}
