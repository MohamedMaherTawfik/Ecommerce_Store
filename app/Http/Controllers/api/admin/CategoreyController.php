<?php

namespace App\Http\Controllers\api\admin;

use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\categoreyRequest;
use App\Models\Categories;
use App\Services\Media\OptimizedImageStorage;
use App\Support\TaggedCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CategoreyController extends Controller
{
    use ApiResponse;

    private $cacheTime = 600;

    // =========================
    // INDEX
    // =========================
    public function index()
    {
        try {
            $page = request('page', 1);

            $categories = TaggedCache::tags('categories')->remember(
                "page_$page",
                $this->cacheTime,
                fn () => Categories::paginate(10)
            );

            return $this->success($categories, 'All Categories');
        } catch (\Throwable $e) {
            return $this->error($e);
        }
    }

    // =========================
    // ALL
    // =========================
    public function all()
    {
        try {
            $categories = TaggedCache::tags('categories')->remember(
                'all',
                $this->cacheTime,
                fn () => Categories::all()
            );

            return $this->success($categories, 'All Categories');
        } catch (\Throwable $e) {
            return $this->error($e);
        }
    }

    // =========================
    // COUNT
    // =========================
    public function count()
    {
        try {
            $count = TaggedCache::tags('categories')->remember(
                'count',
                $this->cacheTime,
                fn () => Categories::count()
            );

            return $this->success($count, 'Categories Count');
        } catch (\Throwable $e) {
            return $this->error($e);
        }
    }

    // =========================
    // SHOW
    // =========================
    public function show(int $id)
    {
        try {
            $category = TaggedCache::tags('categories')->remember(
                "category_$id",
                $this->cacheTime,
                fn () => Categories::with('products')->find($id)
            );

            if (! $category) {
                return $this->notFound('Category Not Found');
            }

            return $this->success($category, 'Category Details');
        } catch (\Throwable $e) {
            return $this->error($e);
        }
    }

    // =========================
    // PRODUCTS
    // =========================
    public function products(int $id)
    {
        try {
            $category = Categories::find($id);

            if (! $category) {
                return $this->notFound('Category Not Found');
            }

            return $this->success($category->products, 'Category Products');
        } catch (\Throwable $e) {
            return $this->error($e);
        }
    }

    // =========================
    // CREATE
    // =========================
    public function create(categoreyRequest $request, OptimizedImageStorage $imageStorage)
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();

            // upload image
            if ($request->hasFile('image')) {
                $data['image'] = $imageStorage->store($request->file('image'), 'categories', 960, 960);
            }

            if ($request->hasFile('og_image')) {
                $data['og_image'] = $imageStorage->store($request->file('og_image'), 'categories/seo', 1200, 630);
            }

            $category = Categories::create($data);

            // clear cache
            TaggedCache::tags('categories')->flush();

            DB::commit();

            return $this->success($category, 'Category Created Successfully');
        } catch (\Throwable $e) {
            DB::rollBack();

            return $this->error($e);
        }
    }

    // =========================
    // UPDATE
    // =========================
    public function update(categoreyRequest $request, int $id, OptimizedImageStorage $imageStorage)
    {
        try {
            DB::beginTransaction();

            $category = Categories::find($id);

            if (! $category) {
                return $this->notFound('Category Not Found');
            }

            $data = $request->validated();

            // handle image update
            if ($request->hasFile('image')) {

                // delete old image
                if ($category->image && Storage::disk('public')->exists($category->image)) {
                    Storage::disk('public')->delete($category->image);
                }

                // store new image
                $data['image'] = $imageStorage->store($request->file('image'), 'categories', 960, 960);
            }

            if ($request->hasFile('og_image')) {
                if ($category->og_image && Storage::disk('public')->exists($category->og_image)) {
                    Storage::disk('public')->delete($category->og_image);
                }

                $data['og_image'] = $imageStorage->store($request->file('og_image'), 'categories/seo', 1200, 630);
            }

            $category->update($data);

            // clear cache
            TaggedCache::tags('categories')->flush();

            DB::commit();

            return $this->success($category, 'Category Updated Successfully');
        } catch (\Throwable $e) {
            DB::rollBack();

            return $this->error($e);
        }
    }

    // =========================
    // DELETE
    // =========================
    public function destroy(int $id)
    {
        try {
            DB::beginTransaction();

            $category = Categories::find($id);

            if (! $category) {
                return $this->notFound('Category Not Found');
            }

            // delete image
            if ($category->image && Storage::disk('public')->exists($category->image)) {
                Storage::disk('public')->delete($category->image);
            }

            if ($category->og_image && Storage::disk('public')->exists($category->og_image)) {
                Storage::disk('public')->delete($category->og_image);
            }

            $category->delete();

            // clear cache
            TaggedCache::tags('categories')->flush();

            DB::commit();

            return $this->success([], 'Category Deleted Successfully');
        } catch (\Throwable $e) {
            DB::rollBack();

            return $this->error($e);
        }
    }

    // =========================
    // TRASHED
    // =========================
    public function trashed()
    {
        try {
            $page = request('page', 1);

            $categories = Categories::onlyTrashed()->latest()->paginate(6);

            return $this->success($categories, 'Trashed Categories');
        } catch (\Throwable $e) {
            return $this->error($e);
        }
    }

    // =========================
    // RESTORE
    // =========================
    public function restore(int $id)
    {
        try {
            DB::beginTransaction();

            $category = Categories::onlyTrashed()->find($id);

            if (! $category) {
                return $this->notFound('Category Not Found');
            }

            $category->restore();

            TaggedCache::tags('categories')->flush();

            DB::commit();

            return $this->success([], 'Category restored successfully');
        } catch (\Throwable $e) {
            DB::rollBack();

            return $this->error($e);
        }
    }
}
