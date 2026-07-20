<?php

namespace App\Http\Controllers\api\home;

use App\Http\Controllers\Controller;
use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Resources\Home\CategoryResource;
use App\Models\Categories;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    use ApiResponse;

    public function index()
    {
        try {
            $categories = \App\Support\TaggedCache::tags(['categories'])->remember('home_categories_all', 3600, function () {
                return Categories::query()
                    ->select([
                        'id',
                        'name',
                        'slug',
                        'image',
                        'description',
                        'meta_title',
                        'meta_description',
                        'meta_keywords',
                        'og_title',
                        'og_description',
                        'og_image',
                        'canonical_url',
                    ])
                    ->withCount(['products' => fn ($query) => $query->where('is_active', true)])
                    ->orderBy('name')
                    ->paginate(20);
            });

            return $this->success($categories, 'Categories loaded');
        } catch (\Throwable $e) {
            Log::info($e->getMessage());
            return $this->error('something went wrong');
        }
    }

    public function show(string $slug)
    {
        try {
            $category = Categories::query()
                ->withCount(['products' => fn ($query) => $query->where('is_active', true)])
                ->where('slug', $slug)
                ->firstOrFail();

            return $this->success(new CategoryResource($category), 'Category loaded');
        } catch (\Throwable $e) {
            return $this->notFound('Category not found');
        }
    }

    public function clearCache()
    {
        \App\Support\TaggedCache::tags(['categories'])->flush();
    }
}


