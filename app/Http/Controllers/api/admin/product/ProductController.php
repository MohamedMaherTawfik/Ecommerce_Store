<?php

namespace App\Http\Controllers\api\admin\product;

use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\ProductColors;
use App\Models\ProductImages;
use App\Models\Products;
use App\Models\ProductSizes;
use App\Models\Stock;
use App\Services\Media\OptimizedImageStorage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
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

            $products = \App\Support\TaggedCache::tags(['products'])->remember(
                "page_$page",
                $this->cacheTime,
                fn() => Products::with('category:id,name', 'brand:id,name', 'stocks')->paginate(10)
            );

            return $this->success($products, 'All Products');
        } catch (\Throwable $e) {
            Log::error($e);
            return $this->error('Something went wrong');
        }
    }

    // =========================
    // COUNT
    // =========================
    public function count()
    {
        try {
            $count = \App\Support\TaggedCache::tags(['products'])->remember(
                'count',
                $this->cacheTime,
                fn() => Products::count()
            );

            return $this->success($count, 'Products Count');
        } catch (\Throwable $e) {
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
            $product = \App\Support\TaggedCache::tags(['products'])->remember(
                "product_$id",
                $this->cacheTime,
                fn() => Products::with('category', 'brand', 'colors', 'sizes', 'stocks', 'images')->find($id)
            );

            if (!$product) {
                return $this->notFound('Product Not Found');
            }

            return $this->success($product, 'Product Details');
        } catch (\Throwable $e) {
            Log::error($e);
            return $this->error('Something went wrong');
        }
    }

    // =========================
    // CREATE
    // =========================
    public function create(ProductRequest $request, OptimizedImageStorage $imageStorage)
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();

            $sizes = $data['sizes'] ?? [];
            $colors = $data['colors'] ?? [];
            $quantity = $data['quantity'] ?? 0;

            unset($data['sizes'], $data['colors'], $data['images'], $data['quantity']);

            // main image
            if ($request->hasFile('image')) {
                $data['image'] = $imageStorage->store($request->file('image'), 'products', 1600, 1600);
            }

            if ($request->hasFile('og_image')) {
                $data['og_image'] = $imageStorage->store($request->file('og_image'), 'products/seo', 1200, 630);
            }

            $product = Products::create($data);

            // stock
            Stock::create([
                'product_id' => $product->id,
                'quantity' => $quantity
            ]);

            // sizes
            if (!empty($sizes)) {
                ProductSizes::insert(
                    collect($sizes)->map(fn($s) => [
                        'product_id' => $product->id,
                        'size' => $s,
                        'created_at' => now(),
                        'updated_at' => now()
                    ])->toArray()
                );
            }

            // colors
            if (!empty($colors)) {
                ProductColors::insert(
                    collect($colors)->map(fn($c) => [
                        'product_id' => $product->id,
                        'color' => $c,
                        'created_at' => now(),
                        'updated_at' => now()
                    ])->toArray()
                );
            }

            // gallery
            if ($request->hasFile('images')) {
                ProductImages::insert(
                    collect($request->file('images'))->map(fn($img) => [
                        'product_id' => $product->id,
                        'image' => $imageStorage->store($img, 'products', 1600, 1600),
                        'created_at' => now(),
                        'updated_at' => now()
                    ])->toArray()
                );
            }

            \App\Support\TaggedCache::tags(['products'])->flush();

            DB::commit();

            return $this->success($product->load(['sizes', 'colors', 'images']), 'Product Created Successfully');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error($e);
            return $this->error('Something went wrong');
        }
    }

    // =========================
    // UPDATE
    // =========================
    public function update(ProductRequest $request, int $id, OptimizedImageStorage $imageStorage)
    {
        try {
            DB::beginTransaction();

            $product = Products::find($id);

            if (!$product) {
                return $this->notFound('Product Not Found');
            }

            $data = $request->validated();

            $sizes = $data['sizes'] ?? [];
            $colors = $data['colors'] ?? [];
            $quantity = $data['quantity'] ?? 0;

            unset($data['sizes'], $data['colors'], $data['images'], $data['quantity']);

            // main image
            if ($request->hasFile('image')) {
                if ($product->image && Storage::disk('public')->exists($product->image)) {
                    Storage::disk('public')->delete($product->image);
                }

                $data['image'] = $imageStorage->store($request->file('image'), 'products', 1600, 1600);
            }

            if ($request->hasFile('og_image')) {
                if ($product->og_image && Storage::disk('public')->exists($product->og_image)) {
                    Storage::disk('public')->delete($product->og_image);
                }

                $data['og_image'] = $imageStorage->store($request->file('og_image'), 'products/seo', 1200, 630);
            }

            $product->update($data);

            // stock
            Stock::updateOrCreate(
                ['product_id' => $product->id],
                ['quantity' => $quantity]
            );

            // sizes
            ProductSizes::where('product_id', $product->id)->delete();
            if (!empty($sizes)) {
                ProductSizes::insert(
                    collect($sizes)->map(fn($s) => [
                        'product_id' => $product->id,
                        'size' => $s,
                        'created_at' => now(),
                        'updated_at' => now()
                    ])->toArray()
                );
            }

            // colors
            ProductColors::where('product_id', $product->id)->delete();
            if (!empty($colors)) {
                ProductColors::insert(
                    collect($colors)->map(fn($c) => [
                        'product_id' => $product->id,
                        'color' => $c,
                        'created_at' => now(),
                        'updated_at' => now()
                    ])->toArray()
                );
            }

            // gallery
            if ($request->hasFile('images')) {

                $oldImages = ProductImages::where('product_id', $product->id)->get();

                foreach ($oldImages as $img) {
                    if (Storage::disk('public')->exists($img->image)) {
                        Storage::disk('public')->delete($img->image);
                    }
                }

                ProductImages::where('product_id', $product->id)->delete();

                ProductImages::insert(
                    collect($request->file('images'))->map(fn($img) => [
                        'product_id' => $product->id,
                        'image' => $imageStorage->store($img, 'products', 1600, 1600),
                        'created_at' => now(),
                        'updated_at' => now()
                    ])->toArray()
                );
            }

            \App\Support\TaggedCache::tags(['products'])->flush();

            DB::commit();

            return $this->success($product->load(['sizes', 'colors', 'images']), 'Product Updated Successfully');
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

            $product = Products::find($id);

            if (!$product) {
                return $this->notFound('Product Not Found');
            }

            // delete main image
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }

            if ($product->og_image && Storage::disk('public')->exists($product->og_image)) {
                Storage::disk('public')->delete($product->og_image);
            }

            // delete gallery
            $images = ProductImages::where('product_id', $product->id)->get();

            foreach ($images as $img) {
                if (Storage::disk('public')->exists($img->image)) {
                    Storage::disk('public')->delete($img->image);
                }
            }
            ProductImages::where('product_id', $product->id)->delete();
            ProductSizes::where('product_id', $product->id)->delete();
            ProductColors::where('product_id', $product->id)->delete();
            Stock::where('product_id', $product->id)->delete();

            $product->delete();

            \App\Support\TaggedCache::tags(['products'])->flush();

            DB::commit();

            return $this->success([], 'Product Deleted Successfully');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error($e);
            return $this->error('Something went wrong');
        }
    }
    // =========================
    // TRASHED
    // =========================
    public function trashed()
    {
        try {
            $page = request('page', 1);

            $products = Products::onlyTrashed()->with('category:id,name', 'brand:id,name', 'stocks')->latest()->paginate(10);

            return $this->success($products, 'Trashed Products');
        } catch (\Throwable $e) {
            Log::error($e);
            return $this->error('Something went wrong');
        }
    }

    // =========================
    // RESTORE
    // =========================
    public function restore(int $id)
    {
        try {
            DB::beginTransaction();

            $product = Products::onlyTrashed()->find($id);

            if (!$product) {
                return $this->notFound('Product Not Found');
            }

            $product->restore();

            \App\Support\TaggedCache::tags(['products'])->flush();

            DB::commit();

            return $this->success([], 'Product restored successfully');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error($e);
            return $this->error('Something went wrong');
        }
    }
}
