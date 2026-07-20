<?php

namespace App\Http\Resources\Home;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'image' => $this->image,
            'description' => $this->when($this->resource->hasAttribute('description'), fn () => $this->description),
            'meta_title' => $this->when($this->resource->hasAttribute('meta_title'), fn () => $this->meta_title),
            'meta_description' => $this->when($this->resource->hasAttribute('meta_description'), fn () => $this->meta_description),
            'meta_keywords' => $this->when($this->resource->hasAttribute('meta_keywords'), fn () => $this->meta_keywords),
            'og_title' => $this->when($this->resource->hasAttribute('og_title'), fn () => $this->og_title),
            'og_description' => $this->when($this->resource->hasAttribute('og_description'), fn () => $this->og_description),
            'og_image' => $this->when($this->resource->hasAttribute('og_image'), fn () => $this->og_image),
            'canonical_url' => $this->when($this->resource->hasAttribute('canonical_url'), fn () => $this->canonical_url),
            'sku' => $this->when($this->resource->hasAttribute('sku'), fn () => $this->sku),
            'price' => (float) $this->price,
            'stock' => (int) ($this->stocks?->quantity ?? 0),
            'category' => $this->whenLoaded('category', fn () => $this->category),
            'brand' => $this->whenLoaded('brand', fn () => $this->brand),
            'images' => $this->whenLoaded('images', fn () => $this->images),
            'sizes' => $this->whenLoaded('sizes', fn () => $this->sizes->where('is_active', true)->values()),
            'colors' => $this->whenLoaded('colors', fn () => $this->colors->where('is_active', true)->values()),
            'reviews' => $this->whenLoaded('reviews', fn () => $this->reviews->map(fn ($review) => [
                'id' => $review->id,
                'rating' => (int) $review->rating,
                'comment' => $review->comment,
                'user' => $review->relationLoaded('user') ? [
                    'id' => $review->user?->id,
                    'name' => $review->user?->name,
                ] : null,
                'created_at' => $review->created_at?->toISOString(),
            ])),
            'average_rating' => round((float) ($this->reviews_avg_rating ?? 0), 1),
            'reviews_count' => (int) ($this->reviews_count ?? 0),
            'is_wishlisted' => (bool) ($this->is_wishlisted ?? false),
            'created_at' => $this->when(
                $this->resource->hasAttribute('created_at'),
                fn () => $this->created_at?->toISOString()
            ),
        ];
    }
}
