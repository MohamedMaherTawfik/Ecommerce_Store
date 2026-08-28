<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Categories;
use App\Models\Products;
use App\Services\Home\HomePageService;
use App\Services\Home\LayoutContentService;
use App\Services\Seo\SeoPageService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Schema;

class SeoController extends Controller
{
    public function app(
        Request $request,
        SeoPageService $seo,
        HomePageService $homePage,
        LayoutContentService $layoutContent
    ) {
        if (preg_match('#^(en|ar)/products/(\d+)$#', $request->path(), $matches)
            && Schema::hasTable('products')) {
            $product = Products::whereKey((int) $matches[2])
                ->where('is_active', true)
                ->whereNotNull('slug')
                ->first(['slug']);

            if ($product) {
                return redirect()->to("/{$matches[1]}/products/{$product->slug}", 301);
            }
        }

        $path = $request->path();
        $isStorefront = $path === '/' || preg_match('#^(en|ar)(?:/|$)#', $path) === 1;
        $initialHomeData = null;
        $initialLayoutData = null;
        $heroImage = null;
        $heroSrcset = null;

        if ($isStorefront) {
            $initialLayoutData = $layoutContent->get();

            if (in_array($path, ['/', 'en', 'ar'], true)) {
                $initialHomeData = $homePage->get();
                $heroImage = $initialHomeData['featured_products'][0]['image'] ?? null;

                if (is_string($heroImage) && ! str_starts_with($heroImage, 'http')) {
                    $heroPath = ltrim($heroImage, '/');
                    $heroImage = asset(str_starts_with($heroPath, 'storage/')
                        ? $heroPath
                        : 'storage/'.$heroPath);
                }

                $heroSrcset = $this->responsiveImageSrcset($heroImage);
            }
        }

        return view('index', [
            'seo' => $seo->forRequest($request),
            'isStorefront' => $isStorefront,
            'initialHomeData' => $initialHomeData,
            'initialLayoutData' => $initialLayoutData,
            'heroImage' => $heroImage,
            'heroSrcset' => $heroSrcset,
        ]);
    }

    private function responsiveImageSrcset(?string $image): ?string
    {
        if (! $image || ! str_contains($image, 'images.unsplash.com')) {
            return null;
        }

        return collect([640, 960, 1440])
            ->map(function (int $width) use ($image): string {
                $parts = parse_url($image);
                parse_str($parts['query'] ?? '', $query);
                $query['w'] = $width;
                $query['auto'] = 'format';
                $query['fit'] = 'crop';
                $query['q'] = 80;

                $url = ($parts['scheme'] ?? 'https').'://'.$parts['host'].($parts['path'] ?? '');

                return $url.'?'.http_build_query($query)." {$width}w";
            })
            ->implode(', ');
    }

    public function robots(): Response
    {
        $base = rtrim(config('app.frontend_url'), '/');
        $content = implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /api',
            'Disallow: /install',
            'Disallow: /installer',
            'Disallow: /*/auth',
            'Disallow: /*/cart',
            'Disallow: /*/wishlist',
            'Disallow: /*/profile',
            'Disallow: /*/wallet',
            'Disallow: /*/orders',
            'Disallow: /*/support',
            '',
            "Sitemap: {$base}/sitemap.xml",
            '',
        ]);

        return response($content, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemap(): Response
    {
        $base = rtrim(config('app.frontend_url'), '/');
        $locales = config('seo.supported_locales');
        $urls = collect();

        foreach ($locales as $locale) {
            foreach (['', '/products', '/about', '/contact', '/blog'] as $path) {
                $urls->push(['loc' => "{$base}/{$locale}{$path}", 'lastmod' => null]);
            }
        }

        if (Schema::hasTable('products')) {
            Products::where('is_active', true)
                ->whereNotNull('slug')
                ->select(['slug', 'updated_at'])
                ->chunk(500, function ($products) use ($urls, $locales, $base) {
                    foreach ($products as $product) {
                        foreach ($locales as $locale) {
                            $urls->push([
                                'loc' => "{$base}/{$locale}/products/{$product->slug}",
                                'lastmod' => $product->updated_at?->toAtomString(),
                            ]);
                        }
                    }
                });
        }

        if (Schema::hasTable('categories')) {
            Categories::whereNotNull('slug')
                ->select(['slug', 'updated_at'])
                ->chunk(500, function ($categories) use ($urls, $locales, $base) {
                    foreach ($categories as $category) {
                        foreach ($locales as $locale) {
                            $urls->push([
                                'loc' => "{$base}/{$locale}/products/category/{$category->slug}",
                                'lastmod' => $category->updated_at?->toAtomString(),
                            ]);
                        }
                    }
                });
        }

        if (Schema::hasTable('blog_posts')) {
            BlogPost::published()
                ->select(['slug', 'updated_at'])
                ->chunk(500, function ($posts) use ($urls, $locales, $base) {
                    foreach ($posts as $post) {
                        foreach ($locales as $locale) {
                            $urls->push([
                                'loc' => "{$base}/{$locale}/blog/{$post->slug}",
                                'lastmod' => $post->updated_at?->toAtomString(),
                            ]);
                        }
                    }
                });
        }

        if (Schema::hasTable('blog_categories')) {
            BlogCategory::select(['slug', 'updated_at'])
                ->chunk(500, function ($categories) use ($urls, $locales, $base) {
                    foreach ($categories as $category) {
                        foreach ($locales as $locale) {
                            $urls->push([
                                'loc' => "{$base}/{$locale}/blog/category/{$category->slug}",
                                'lastmod' => $category->updated_at?->toAtomString(),
                            ]);
                        }
                    }
                });
        }

        return response()
            ->view('sitemap', ['urls' => $urls->unique('loc')->values()])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
