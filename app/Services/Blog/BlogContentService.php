<?php

namespace App\Services\Blog;

use App\Models\BlogPost;
use Illuminate\Support\Str;

class BlogContentService
{
    public function sanitize(string $html): string
    {
        if (! class_exists(\HTMLPurifier::class)) {
            return strip_tags($html, '<p><br><h2><h3><h4><strong><em><ul><ol><li><a><blockquote><img><code><pre>');
        }

        $config = \HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'p,br,h2,h3,h4,strong,em,ul,ol,li,a[href|title|target],blockquote,img[src|alt|title],code,pre');
        $config->set('URI.DisableExternalResources', false);
        $config->set('Attr.AllowedFrameTargets', ['_blank']);

        return (new \HTMLPurifier($config))->purify($html);
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
