<?php

namespace App\Http\Controllers\api\admin;

use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Services\Blog\BlogContentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BlogController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly BlogContentService $content) {}

    public function posts(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:draft,published'],
            'category_id' => ['nullable', 'integer', 'exists:blog_categories,id'],
            'tag_id' => ['nullable', 'integer', 'exists:blog_tags,id'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $posts = BlogPost::query()
            ->with(['category:id,name,slug', 'tags:id,name,slug', 'author:id,name'])
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(
                fn ($query) => $query->where('title', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%")
            ))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['category_id'] ?? null, fn ($query, $id) => $query->where('blog_category_id', $id))
            ->when($filters['tag_id'] ?? null, fn ($query, $id) => $query->whereHas('tags', fn ($query) => $query->whereKey($id)))
            ->latest()
            ->paginate($filters['per_page'] ?? 15);

        return $this->success($posts, 'Blog posts loaded.');
    }

    public function showPost(int $id)
    {
        return $this->success(
            BlogPost::with(['category', 'tags', 'author:id,name'])->findOrFail($id),
            'Blog post loaded.'
        );
    }

    public function storePost(Request $request)
    {
        $data = $this->postData($request);

        $post = DB::transaction(function () use ($request, $data) {
            $tags = $data['tag_ids'] ?? [];
            unset($data['tag_ids']);
            $data['user_id'] = $request->user()->id;
            $data['slug'] = $this->content->uniqueSlug($data['slug'] ?? $data['title']);
            $data['content'] = $this->content->sanitize($data['content']);
            $data['published_at'] = $data['status'] === 'published'
                ? ($data['published_at'] ?? now())
                : null;
            $this->storeImages($request, $data);

            $post = BlogPost::create($data);
            $post->tags()->sync($tags);

            return $post;
        });

        return $this->success($post->load(['category', 'tags']), 'Blog post created.');
    }

    public function updatePost(Request $request, int $id)
    {
        $post = BlogPost::findOrFail($id);
        $data = $this->postData($request, $post);

        DB::transaction(function () use ($request, $post, $data) {
            $tags = $data['tag_ids'] ?? [];
            unset($data['tag_ids']);
            $data['slug'] = $this->content->uniqueSlug($data['slug'] ?? $data['title'], $post->id);
            $data['content'] = $this->content->sanitize($data['content']);
            $data['published_at'] = $data['status'] === 'published'
                ? ($data['published_at'] ?? $post->published_at ?? now())
                : null;
            $this->storeImages($request, $data, $post);
            $post->update($data);
            $post->tags()->sync($tags);
        });

        return $this->success($post->fresh(['category', 'tags']), 'Blog post updated.');
    }

    public function destroyPost(int $id)
    {
        BlogPost::findOrFail($id)->delete();

        return $this->success([], 'Blog post deleted.');
    }

    public function categories()
    {
        return $this->success(BlogCategory::withCount('posts')->orderBy('name')->get(), 'Blog categories loaded.');
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:blog_categories,name'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:blog_categories,slug'],
            'description' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ]);
        $data['slug'] = $this->uniqueTaxonomySlug(BlogCategory::class, $data['slug'] ?? $data['name']);

        return $this->success(BlogCategory::create($data), 'Blog category created.');
    }

    public function updateCategory(Request $request, int $id)
    {
        $category = BlogCategory::findOrFail($id);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('blog_categories', 'name')->ignore($id)],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('blog_categories', 'slug')->ignore($id)],
            'description' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ]);
        $data['slug'] = $this->uniqueTaxonomySlug(BlogCategory::class, $data['slug'] ?? $data['name'], $id);
        $category->update($data);

        return $this->success($category, 'Blog category updated.');
    }

    public function destroyCategory(int $id)
    {
        BlogCategory::findOrFail($id)->delete();

        return $this->success([], 'Blog category deleted.');
    }

    public function tags()
    {
        return $this->success(BlogTag::withCount('posts')->orderBy('name')->get(), 'Blog tags loaded.');
    }

    public function storeTag(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255', 'unique:blog_tags,name']]);
        $data['slug'] = $this->uniqueTaxonomySlug(BlogTag::class, $data['name']);

        return $this->success(BlogTag::create($data), 'Blog tag created.');
    }

    public function updateTag(Request $request, int $id)
    {
        $tag = BlogTag::findOrFail($id);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('blog_tags', 'name')->ignore($id)],
        ]);
        $data['slug'] = $this->uniqueTaxonomySlug(BlogTag::class, $data['name'], $id);
        $tag->update($data);

        return $this->success($tag, 'Blog tag updated.');
    }

    public function destroyTag(int $id)
    {
        BlogTag::findOrFail($id)->delete();

        return $this->success([], 'Blog tag deleted.');
    }

    private function postData(Request $request, ?BlogPost $post = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'content' => ['required', 'string'],
            'blog_category_id' => ['nullable', 'integer', 'exists:blog_categories,id'],
            'tag_ids' => ['sometimes', 'array'],
            'tag_ids.*' => ['integer', 'exists:blog_tags,id'],
            'status' => ['required', 'in:draft,published'],
            'is_featured' => ['sometimes', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'featured_image' => ['nullable', 'image', 'extensions:jpg,jpeg,png,webp', 'mimes:jpg,jpeg,png,webp', 'dimensions:max_width=4096,max_height=4096', 'max:4096'],
            'og_image' => ['nullable', 'image', 'extensions:jpg,jpeg,png,webp', 'mimes:jpg,jpeg,png,webp', 'dimensions:max_width=4096,max_height=4096', 'max:4096'],
            'twitter_image' => ['nullable', 'image', 'extensions:jpg,jpeg,png,webp', 'mimes:jpg,jpeg,png,webp', 'dimensions:max_width=4096,max_height=4096', 'max:4096'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_keywords' => ['nullable', 'string', 'max:1000'],
            'canonical_url' => ['nullable', 'url', 'max:2048'],
            'og_title' => ['nullable', 'string', 'max:255'],
            'og_description' => ['nullable', 'string', 'max:500'],
            'twitter_title' => ['nullable', 'string', 'max:255'],
            'twitter_description' => ['nullable', 'string', 'max:500'],
        ]);
    }

    private function storeImages(Request $request, array &$data, ?BlogPost $post = null): void
    {
        foreach (['featured_image', 'og_image', 'twitter_image'] as $field) {
            if (! $request->hasFile($field)) {
                unset($data[$field]);

                continue;
            }

            if ($post?->{$field}) {
                Storage::disk('public')->delete($post->{$field});
            }
            $data[$field] = $request->file($field)->store('blog', 'public');
        }
    }

    private function uniqueTaxonomySlug(string $model, string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: Str::random(8);
        $slug = $base;
        $suffix = 2;
        while ($model::where('slug', $slug)->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
