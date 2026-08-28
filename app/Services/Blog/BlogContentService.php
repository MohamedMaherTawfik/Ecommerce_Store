<?php

namespace App\Services\Blog;

use App\Models\BlogPost;
use App\Security\HtmlSanitizer;
use Illuminate\Support\Str;

class BlogContentService
{
    public function __construct(private readonly HtmlSanitizer $sanitizer) {}

    public function sanitize(string $html): string
    {
        return $this->sanitizer->sanitize($html);
    }

    public function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: Str::random(10);
        $slug = $base;
        $suffix = 2;

        while (BlogPost::withTrashed()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
