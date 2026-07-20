<?php

namespace App\Http\Controllers\api\home;

use App\Http\Controllers\Controller;
use App\Http\Requests\Home\ProductIndexRequest;
use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Resources\Home\ProductResource;
use App\Models\Products;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    use ApiResponse;

    public function index(ProductIndexRequest $request)
    {
        try {
            $filters = $request->validated();
            $userId = $request->user()?->id;
            
            $cacheKey = 'products_index_' . md5(json_encode($filters) . '_' . $userId);

            $products = \App\Support\TaggedCache::tags(['products'])->remember($cacheKey, 3600, function () use ($filters, $userId) {
                $query = Products::query()
                    ->select('id', 'name', 'slug', 'price', 'image', 'categories_id', 'brands_id', 'description')
                    ->with(['category:id,name,slug', 'brand:id,name', 'stocks:id,product_id,quantity'])
                    ->withAvg('reviews', 'rating')
                    ->withCount('reviews')
                    ->where('is_active', true);

                if ($userId) {
                    $query->withExists(['wishlists as is_wishlisted' => fn($q) => $q->where('user_id', $userId)]);
                }

                if (!empty($filters['search'])) {
                    $query->where(function ($q) use ($filters) {
                        $q->where('name', 'like', "%{$filters['search']}%")
                          ->orWhere('description', 'like', "%{$filters['search']}%");
                    });
                }

                if (!empty($filters['category_id'])) $query->where('categories_id', $filters['category_id']);
                if (!empty($filters['category_slug'])) {
                    $query->whereHas('category', fn ($category) => $category->where('slug', $filters['category_slug']));
                }
                if (!empty($filters['brand_id'])) $query->where('brands_id', $filters['brand_id']);
                if (!empty($filters['min_price'])) $query->where('price', '>=', $filters['min_price']);
                if (!empty($filters['max_price'])) $query->where('price', '<=', $filters['max_price']);

                $sort = $filters['sort'] ?? 'newest';
                if ($sort === 'price_asc') $query->orderBy('price');
                elseif ($sort === 'price_desc') $query->orderByDesc('price');
                elseif ($sort === 'rating') $query->orderByDesc('reviews_avg_rating');
                else $query->latest();

                $paginated = $query->paginate($filters['per_page'] ?? 12);
                
                return $paginated;
            });

            return $this->success(ProductResource::collection($products), 'Products retrieved successfully');
        } catch (ModelNotFoundException) {
            return $this->notFound('Product not found');
        } catch (\Throwable $e) {
            Log::info($e->getMessage());
            return $this->error('Something went wrong.', 500);
        }
    }

    public function show(string $product)
    {
        try {
            $userId = request()->user()?->id;
            
            $productModel = \App\Support\TaggedCache::tags(['products'])->remember("product_show_{$product}_{$userId}", 3600, function () use ($product, $userId) {
                $prod = Products::with([
                    'category:id,name,slug',
                    'brand:id,name',
                    'stocks:id,product_id,quantity',
                    'images:id,product_id,image',
                    'sizes:id,product_id,size',
                    'colors:id,product_id,color',
                    'reviews:id,product_id,user_id,rating,comment,created_at',
                    'reviews.user:id,name'
                ])
                ->withAvg('reviews', 'rating')
                ->withCount('reviews');

                if ($userId) {
                    $prod->withExists(['wishlists as is_wishlisted' => fn($q) => $q->where('user_id', $userId)]);
                }

                return $prod
                    ->where('is_active', true)
                    ->where(fn ($query) => $query->where('slug', $product)
                        ->when(ctype_digit($product), fn ($query) => $query->orWhere('id', (int) $product)))
                    ->firstOrFail();
            });

            return $this->success(new ProductResource($productModel), 'Product details');
        } catch (ModelNotFoundException) {
            return $this->notFound('Product not found');
        } catch (\Throwable $e) {
            Log::info($e->getMessage());
            return $this->error('Something went wrong.', 500);
        }
    }

    public function related(string $product)
    {
        try {
            $related = \App\Support\TaggedCache::tags(['products'])->remember("product_related_{$product}", 3600, function () use ($product) {
                $productModel = Products::query()
                    ->where(fn ($query) => $query->where('slug', $product)
                        ->when(ctype_digit($product), fn ($query) => $query->orWhere('id', (int) $product)))
                    ->firstOrFail();
                
                return Products::with(['category:id,name,slug', 'brand:id,name', 'stocks:id,product_id,quantity'])
                    ->select('id', 'name', 'slug', 'price', 'image', 'categories_id', 'brands_id')
                    ->withAvg('reviews', 'rating')
                    ->withCount('reviews')
                    ->whereKeyNot($productModel->id)
                    ->where('is_active', true)
                    ->where(function ($query) use ($productModel) {
                        $query->where('categories_id', $productModel->categories_id)
                              ->orWhere('brands_id', $productModel->brands_id);
                    })
                    ->limit(8)
                    ->get();
            });

            return $this->success(ProductResource::collection($related), 'Related products');
        } catch (ModelNotFoundException) {
            return $this->notFound('Product not found');
        } catch (\Throwable $e) {
            Log::info($e->getMessage());
            return $this->error('Something went wrong.', 500);
        }
    }

    public function clearCache()
    {
        \App\Support\TaggedCache::tags(['products'])->flush();
    }
}
