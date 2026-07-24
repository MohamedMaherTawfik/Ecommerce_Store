<?php

namespace App\Services\Home;

use App\Http\Resources\Home\CategoryResource;
use App\Http\Resources\Home\ProductResource;
use App\Models\Banner;
use App\Models\Categories;
use App\Models\Deal;
use App\Models\Feature;
use App\Models\Products;
use App\Models\Testimonial;
use App\Models\TrustItem;
use App\Support\TaggedCache;
use Illuminate\Support\Facades\Schema;

class HomePageService
{
    public function get(): array
    {
        return TaggedCache::tags([
            'home_content',
            'banners',
            'trust_items',
            'features',
            'testimonials',
            'deals',
            'products',
            'categories',
        ])->remember(
            'home_page_data_merged_v2',
            3600,
            function (): array {
                $hero = Schema::hasTable('banners')
                    ? Banner::active()->ofType('hero')->ordered()->first()
                    : null;
                $promo = Schema::hasTable('banners')
                    ? Banner::active()->ofType('promo')->ordered()->first()
                    : null;
                $newsletter = Schema::hasTable('banners')
                    ? Banner::active()->ofType('newsletter')->ordered()->first()
                    : null;

                $featuredProducts = Schema::hasTable('products') ? Products::select(
                    'id',
                    'name',
                    'slug',
                    'price',
                    'image',
                    'categorey_id',
                    'brand_id',
                    'description'
                )
                    ->with(['category:id,name', 'brand:id,name', 'stocks:id,product_id,quantity'])
                    ->withCount('reviews')
                    ->withAvg('reviews', 'rating')
                    ->where('is_active', true)
                    ->latest()
                    ->limit(5)
                    ->get() : collect();

                $bestSellers = Schema::hasTable('products') ? Products::select(
                    'id',
                    'name',
                    'slug',
                    'price',
                    'image',
                    'categorey_id',
                    'brand_id'
                )
                    ->with(['category:id,name', 'brand:id,name', 'stocks:id,product_id,quantity'])
                    ->withCount('reviews')
                    ->withAvg('reviews', 'rating')
                    ->where('is_active', true)
                    ->orderByDesc('reviews_avg_rating')
                    ->limit(4)
                    ->get() : collect();

                return [
                    'hero' => $hero ? [
                        'eyebrow' => $hero->eyebrow,
                        'fallback' => $hero->title,
                        'image' => $hero->image,
                    ] : null,
                    'trust' => Schema::hasTable('trust_items')
                        ? TrustItem::active()->ordered()->get(['id', 'icon', 'label', 'sub'])->toArray()
                        : [],
                    'features' => Schema::hasTable('features')
                        ? Feature::active()->ordered()->get(['id', 'icon', 'label', 'text'])->toArray()
                        : [],
                    'testimonials' => Schema::hasTable('testimonials') ? Testimonial::active()->ordered()->get([
                        'id',
                        'name',
                        'role',
                        'text',
                        'avatar',
                        'rating',
                    ])->toArray() : [],
                    'deals' => Schema::hasTable('deals') ? Deal::active()->notExpired()->ordered()->get([
                        'id',
                        'name',
                        'category',
                        'icon',
                        'discount',
                        'sale_price',
                        'original_price',
                        'sold_percent',
                        'sold_label',
                    ])->toArray() : [],
                    'banner' => $promo ? [
                        'eyebrow' => $promo->eyebrow,
                        'title' => $promo->title,
                        'sub' => $promo->subtitle,
                        'cta' => $promo->cta_text,
                        'cta_link' => $promo->cta_link,
                        'image' => $promo->image,
                    ] : null,
                    'newsletter' => $newsletter ? [
                        'eyebrow' => $newsletter->eyebrow,
                        'title' => $newsletter->title,
                        'sub' => $newsletter->subtitle,
                        'cta' => $newsletter->cta_text,
                    ] : null,
                    'categories' => Schema::hasTable('categories') ? CategoryResource::collection(
                        Categories::select('id', 'name', 'slug', 'image')
                            ->withCount(['products' => fn ($query) => $query->where('is_active', true)])
                            ->orderBy('name')
                            ->limit(12)
                            ->get()
                    )->resolve() : [],
                    'featured_products' => ProductResource::collection($featuredProducts)->resolve(),
                    'best_sellers' => ProductResource::collection($bestSellers)->resolve(),
                ];
            }
        );
    }
}
