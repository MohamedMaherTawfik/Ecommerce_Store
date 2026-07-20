<?php

namespace Database\Seeders;

use App\Models\brands;
use App\Models\Categories;
use App\Models\ProductColors;
use App\Models\ProductImages;
use App\Models\Products;
use App\Models\ProductSizes;
use App\Models\Stock;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = [
            ['Relaxed Linen Shirt', 'Shirts', 'Aster & Co', 68, 'Breathable linen shirt with a relaxed resort fit.', 'https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?w=900&auto=format&fit=crop'],
            ['Essential Cotton Tee', 'T-Shirts', 'Urban Loom', 28, 'Soft heavyweight cotton tee designed for everyday layering.', 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=900&auto=format&fit=crop'],
            ['Tailored Chino Pants', 'Pants', 'Northline Studio', 76, 'Clean tapered chinos with stretch comfort and crisp structure.', 'https://images.unsplash.com/photo-1473966968600-fa801b869a1a?w=900&auto=format&fit=crop'],
            ['Cropped Denim Jacket', 'Jackets', 'Thread Theory', 112, 'Modern denim jacket with a structured cropped silhouette.', 'https://images.unsplash.com/photo-1551028719-00167b16eac5?w=900&auto=format&fit=crop'],
            ['Merino Crew Sweater', 'Sweaters', 'Maison Vale', 94, 'Fine merino knit with a soft hand feel and neat rib finish.', 'https://images.unsplash.com/photo-1576566588028-4147f3842f27?w=900&auto=format&fit=crop'],
            ['Leather Crossbody Bag', 'Accessories', 'Maison Vale', 128, 'Compact leather crossbody with polished hardware.', 'https://images.unsplash.com/photo-1590874103328-eac38a683ce7?w=900&auto=format&fit=crop'],
            ['Oxford Button Down', 'Shirts', 'Northline Studio', 72, 'Classic oxford shirt cut for workdays and weekends.', 'https://images.unsplash.com/photo-1598033129183-c4f50c736f10?w=900&auto=format&fit=crop'],
            ['Wide Leg Trousers', 'Pants', 'Aster & Co', 88, 'Fluid wide leg trousers with a clean front and easy drape.', 'https://images.unsplash.com/photo-1506629905607-d9d297d4499f?w=900&auto=format&fit=crop'],
            ['Quilted Utility Jacket', 'Jackets', 'Urban Loom', 138, 'Lightweight quilted jacket with utility pockets.', 'https://images.unsplash.com/photo-1543076447-215ad9ba6923?w=900&auto=format&fit=crop'],
            ['Ribbed Half Zip Knit', 'Sweaters', 'Thread Theory', 86, 'Textured half zip sweater for smart casual layering.', 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=900&auto=format&fit=crop'],
        ];

        foreach ($catalog as [$name, $categoryName, $brandName, $price, $description, $image]) {
            $category = Categories::where('name', $categoryName)->first();
            $brand = brands::where('name', $brandName)->first();

            $product = Products::updateOrCreate(
                ['name' => $name],
                [
                    'categories_id' => $category?->id,
                    'brands_id' => $brand?->id,
                    'slug' => Str::slug($name),
                    'sku' => 'SKU-' . Str::upper(Str::slug($name, '')),
                    'image' => $image,
                    'description' => $description,
                    'tax' => 0,
                    'is_active' => true,
                    'is_featured' => in_array($categoryName, ['Shirts', 'Jackets', 'Sweaters'], true),
                    'price' => $price,
                    'meta_title' => $name,
                    'meta_description' => $description,
                    'return_policy' => '14 days',
                ]
            );

            Stock::updateOrCreate(['product_id' => $product->id], ['quantity' => 24]);

            foreach (['S', 'M', 'L', 'XL'] as $size) {
                ProductSizes::updateOrCreate(['product_id' => $product->id, 'size' => $size], ['is_active' => true]);
            }

            foreach (['Black', 'White', 'Navy'] as $color) {
                ProductColors::updateOrCreate(['product_id' => $product->id, 'color' => $color], ['is_active' => true]);
            }

            ProductImages::updateOrCreate(['product_id' => $product->id, 'image' => $image], []);
        }
    }
}
