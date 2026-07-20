<?php

namespace App\Http\Controllers\api\home;

use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'tag' => ['nullable', 'string', 'max:255'],
            'featured' => ['nullable', 'boolean'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        $posts = BlogPost::published()
            ->with(['category:id,name,slug', 'tags:id,name,slug', 'author:id,name'])
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(
                fn ($query) => $query->where('title', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%")
            ))
            ->when($filters['category'] ?? null, fn ($query, $slug) => $query->whereHas('category', fn ($query) => $query->where('slug', $slug)))
            ->when($filters['tag'] ?? null, fn ($query, $slug) => $query->whereHas('tags', fn ($query) => $query->where('slug', $slug)))
            ->when(isset($filters['featured']), fn ($query) => $query->where('is_featured', $filters['featured']))
            ->latest('published_at')
            ->paginate($filters['per_page'] ?? 12);

        return $this->success($posts, 'Blog posts loaded.');
    }

    public function show(string $slug)
    {
        return $this->success(
            BlogPost::published()->with(['category', 'tags', 'author:id,name'])->where('slug', $slug)->firstOrFail(),
            'Blog post loaded.'
        );
    }

    public function categories()
    {
        return $this->success(
            BlogCategory::withCount(['posts' => fn ($query) => $query->published()])->orderBy('name')->get(),
            'Blog categories loaded.'
        );
    }

    public function tags()
    {
        return $this->success(
            BlogTag::withCount(['posts' => fn ($query) => $query->published()])->orderBy('name')->get(),
            'Blog tags loaded.'
        );
    }
}
