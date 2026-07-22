<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Products extends Model
{
    /** @use HasFactory<\Database\Factories\ProductsFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'products';
    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'manage_stock' => 'boolean',
        'allow_backorder' => 'boolean',
    ];


    protected $fillable = [
        'categories_id',
        'brands_id',
        'name',
        'image',
        'description',
        'slug',
        'sku',
        'tax',
        'is_active',
        'price',
        'is_featured',
        'meta_title',
        'meta_description',
        'return_policy',
        'stock_quantity',
        'low_stock_threshold',
        'manage_stock',
        'stock_status',
        'allow_backorder',
        'meta_keywords',
        'og_title',
        'og_description',
        'og_image',
        'canonical_url',
    ];

    protected static function booted(): void
    {
        static::saving(function (Products $product) {
            if (! $product->slug || $product->isDirty('name')) {
                $product->slug = static::uniqueSlug($product->slug ?: $product->name, $product->id);
            }
        });
    }

    public static function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: Str::random(10);
        $slug = $base;
        $suffix = 2;

        while (static::withTrashed()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    public function category()
    {
        return $this->belongsTo(Categories::class, 'categorey_id');
    }

    public function brand()
    {
        return $this->belongsTo(brands::class, 'brand_id');
    }

    public function images()
    {
        return $this->hasMany(ProductImages::class, 'product_id');
    }

      public function firstImage()
    {
        return $this->hasOne(ProductImages::class, 'product_id');
    }

    public function sizes()
    {
        return $this->hasMany(ProductSizes::class, 'product_id');
    }

    public function colors()
    {
        return $this->hasMany(ProductColors::class, 'product_id');
    }

    public function reviews()
    {
        return $this->hasMany(Reviews::class, 'product_id');
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class, 'product_id');
    }

    public function stocks()
    {
        return $this->hasOne(Stock::class, 'product_id');
    }

    public function cartItems()
    {
        return $this->hasMany(CartItems::class, 'product_id');
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class, 'product_id');
    }
}
