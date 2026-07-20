<?php

namespace App\Services\Seo;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Categories;
use App\Models\Products;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SeoPageService
{
    public function forRequest(Request $request): array
    {
        $segments = $request->segments();
        $locale = in_array($segments[0] ?? null, config('seo.supported_locales'), true)
            ? array_shift($segments)
            : config('seo.default_locale');
        $path = implode('/', $segments);
        $canonical = $this->localizedUrl($locale, $path);

        $seo = $this->defaults($locale, $canonical);

        if ($path === '') {
            return $this->merge($seo, [
                'title' => config('seo.default_title'),
                'description' => config('seo.default_description'),
                'schema' => $this->organizationSchema(),
            ]);
        }

        if ($path === 'products') {
            return $this->merge($seo, [
                'title' => 'Shop Products',
                'description' => 'Browse products by category, brand, price, and customer rating.',
            ]);
        }

        if (preg_match('#^products/category/([^/]+)$#', $path, $matches)) {
            return $this->categorySeo($matches[1], $locale, $seo);
        }

        if (preg_match('#^products/([^/]+)$#', $path, $matches)) {
            return $this->productSeo($matches[1], $locale, $canonical, $seo);
        }

        if ($path === 'blog') {
            return $this->merge($seo, [
                'title' => 'Blog',
                'description' => 'Read store news, product guides, and practical shopping advice.',
            ]);
        }

        if (preg_match('#^blog/category/([^/]+)$#', $path, $matches)) {
            return $this->blogCategorySeo($matches[1], $seo);
        }

        if (preg_match('#^blog/tag/([^/]+)$#', $path)) {
            return $this->merge($seo, [
                'title' => 'Blog Tag',
                'description' => 'Browse articles filed under this topic.',
            ]);
        }

        if (preg_match('#^blog/([^/]+)$#', $path, $matches)) {
            return $this->blogPostSeo($matches[1], $canonical, $seo);
        }

        $pages = [
            'about' => ['About Us', 'Learn about our store, values, and customer-first shopping experience.'],
            'contact' => ['Contact Us', 'Contact our team for product, order, and account support.'],
        ];

        if (isset($pages[$path])) {
            return $this->merge($seo, [
                'title' => $pages[$path][0],
                'description' => $pages[$path][1],
            ]);
        }

        return $this->merge($seo, ['robots' => 'noindex,nofollow']);
    }

    private function productSeo(string $identifier, string $locale, string $canonical, array $seo): array
    {
        if (! Schema::hasTable('products')) {
            return $seo;
        }

        $product = Products::query()
            ->with(['category:id,name,slug', 'brand:id,name', 'stocks:id,product_id,quantity'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->where('is_active', true)
            ->where(fn ($query) => $query->where('slug', $identifier)
                ->when(ctype_digit($identifier), fn ($query) => $query->orWhere('id', (int) $identifier)))
            ->first();

        if (! $product) {
            return $this->merge($seo, ['title' => 'Product Not Found', 'robots' => 'noindex,nofollow']);
        }

        $canonical = $product->canonical_url ?: $this->localizedUrl($locale, "products/{$product->slug}");
        $image = $this->assetUrl($product->og_image ?: $product->image);
        $description = $product->meta_description ?: Str::limit(strip_tags((string) $product->description), 160);

        return $this->merge($seo, [
            'title' => $product->meta_title ?: $product->name,
            'description' => $description,
            'keywords' => $product->meta_keywords,
            'canonical' => $canonical,
            'image' => $image,
            'type' => 'product',
            'og_title' => $product->og_title ?: $product->meta_title ?: $product->name,
            'og_description' => $product->og_description ?: $description,
            'schema' => [
                '@context' => 'https://schema.org',
                '@type' => 'Product',
                'name' => $product->name,
                'description' => $description,
                'sku' => $product->sku,
                'image' => array_values(array_filter([$image])),
                'brand' => $product->brand ? [
                    '@type' => 'Brand',
                    'name' => $product->brand->name,
                ] : null,
                'category' => $product->category?->name,
                'aggregateRating' => $product->reviews_count > 0 ? [
                    '@type' => 'AggregateRating',
                    'ratingValue' => round((float) $product->reviews_avg_rating, 1),
                    'reviewCount' => $product->reviews_count,
                ] : null,
                'offers' => [
                    '@type' => 'Offer',
                    'url' => $canonical,
                    'priceCurrency' => config('checkout.currency', 'USD'),
                    'price' => (string) $product->price,
                    'availability' => ($product->stocks?->quantity ?? 0) > 0
                        ? 'https://schema.org/InStock'
                        : 'https://schema.org/OutOfStock',
                ],
            ],
        ]);
    }

    private function categorySeo(string $slug, string $locale, array $seo): array
    {
        if (! Schema::hasTable('categories')) {
            return $seo;
        }

        $category = Categories::where('slug', $slug)->first();

        if (! $category) {
            return $this->merge($seo, ['title' => 'Category Not Found', 'robots' => 'noindex,nofollow']);
        }

        $description = $category->meta_description
            ?: $category->description
            ?: "Shop {$category->name} products.";
        $categoryCanonical = $category->canonical_url
            ?: $this->localizedUrl($locale, "products/category/{$category->slug}");

        return $this->merge($seo, [
            'title' => $category->meta_title ?: $category->name,
            'description' => $description,
            'keywords' => $category->meta_keywords,
            'canonical' => $categoryCanonical,
            'image' => $this->assetUrl($category->og_image ?: $category->image),
            'og_title' => $category->og_title ?: $category->meta_title ?: $category->name,
            'og_description' => $category->og_description ?: $description,
            'schema' => [
                '@context' => 'https://schema.org',
                '@type' => 'CollectionPage',
                'name' => $category->name,
                'description' => $description,
                'url' => $categoryCanonical,
            ],
        ]);
    }

    private function blogPostSeo(string $slug, string $canonical, array $seo): array
    {
        if (! Schema::hasTable('blog_posts')) {
            return $seo;
        }

        $post = BlogPost::published()->with(['author:id,name', 'category:id,name,slug'])->where('slug', $slug)->first();

        if (! $post) {
            return $this->merge($seo, ['title' => 'Article Not Found', 'robots' => 'noindex,nofollow']);
        }

        $canonical = $post->canonical_url ?: $canonical;
        $description = $post->meta_description ?: $post->excerpt ?: Str::limit(strip_tags($post->content), 160);
        $image = $this->assetUrl($post->og_image ?: $post->featured_image);

        return $this->merge($seo, [
            'title' => $post->meta_title ?: $post->title,
            'description' => $description,
            'keywords' => $post->meta_keywords,
            'canonical' => $canonical,
            'image' => $image,
            'type' => 'article',
            'og_title' => $post->og_title ?: $post->title,
            'og_description' => $post->og_description ?: $description,
            'twitter_title' => $post->twitter_title ?: $post->og_title ?: $post->title,
            'twitter_description' => $post->twitter_description ?: $description,
            'twitter_image' => $this->assetUrl($post->twitter_image ?: $post->og_image ?: $post->featured_image),
            'schema' => [
                '@context' => 'https://schema.org',
                '@type' => 'Article',
                'headline' => $post->title,
                'description' => $description,
                'image' => array_values(array_filter([$image])),
                'datePublished' => $post->published_at?->toIso8601String(),
                'dateModified' => $post->updated_at?->toIso8601String(),
                'author' => [
                    '@type' => 'Person',
                    'name' => $post->author?->name ?: config('seo.site_name'),
                ],
                'mainEntityOfPage' => $canonical,
            ],
        ]);
    }

    private function blogCategorySeo(string $slug, array $seo): array
    {
        if (! Schema::hasTable('blog_categories')) {
            return $seo;
        }

        $category = BlogCategory::where('slug', $slug)->first();

        return $category
            ? $this->merge($seo, [
                'title' => $category->meta_title ?: $category->name,
                'description' => $category->meta_description ?: $category->description ?: "Articles about {$category->name}.",
            ])
            : $this->merge($seo, ['robots' => 'noindex,nofollow']);
    }

    private function defaults(string $locale, string $canonical): array
    {
        return [
            'title' => config('seo.default_title'),
            'description' => config('seo.default_description'),
            'keywords' => null,
            'canonical' => $canonical,
            'image' => $this->assetUrl(config('seo.default_image')),
            'type' => 'website',
            'robots' => 'index,follow,max-image-preview:large',
            'locale' => $locale,
            'alternates' => collect(config('seo.supported_locales'))
                ->mapWithKeys(fn (string $language) => [
                    $language => preg_replace('#/('.implode('|', config('seo.supported_locales')).')(?=/|$)#', "/{$language}", $canonical, 1),
                ])
                ->all(),
            'schema' => null,
        ];
    }

    private function merge(array $seo, array $values): array
    {
        return array_replace($seo, array_filter($values, fn ($value) => $value !== null && $value !== ''));
    }

    private function localizedUrl(string $locale, string $path = ''): string
    {
        return rtrim(config('app.frontend_url'), '/').'/'.$locale.($path !== '' ? '/'.ltrim($path, '/') : '');
    }

    private function assetUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', 'data:'])) {
            return $path;
        }

        return Str::startsWith($path, ['/images/', 'images/', '/storage/', 'storage/'])
            ? asset(ltrim($path, '/'))
            : asset('storage/'.ltrim($path, '/'));
    }

    private function organizationSchema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => config('seo.site_name'),
            'url' => rtrim(config('app.frontend_url'), '/'),
            'logo' => $this->assetUrl(config('seo.default_image')),
        ];
    }
}
