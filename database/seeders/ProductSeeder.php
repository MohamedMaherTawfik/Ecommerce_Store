<?php

namespace Database\Seeders;

use App\Models\Brands;
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
        $products = [

            [
                'name' => 'Radiance Vitamin C Serum',
                'category' => 'Skincare',
                'brand' => 'The Ordinary',
                'price' => 24.99,
                'tax' => 5,
                'featured' => true,
                'description' => 'Vitamin C serum that brightens and evens skin tone.'
            ],

            [
                'name' => 'Hydra Moisturizing Cream',
                'category' => 'Skincare',
                'brand' => 'CeraVe',
                'price' => 19.99,
                'tax' => 5,
                'featured' => true,
                'description' => 'Deep hydration cream for dry and sensitive skin.'
            ],

            [
                'name' => 'Oil Control Face Wash',
                'category' => 'Skincare',
                'brand' => 'La Roche-Posay',
                'price' => 15.99,
                'tax' => 5,
                'featured' => false,
                'description' => 'Daily cleanser for oily and acne-prone skin.'
            ],

            [
                'name' => 'Perfect Matte Foundation',
                'category' => 'Makeup',
                'brand' => 'Maybelline',
                'price' => 18.50,
                'tax' => 8,
                'featured' => true,
                'description' => 'Long-lasting matte liquid foundation.'
            ],

            [
                'name' => 'Volume Lash Mascara',
                'category' => 'Makeup',
                'brand' => 'Maybelline',
                'price' => 14.25,
                'tax' => 8,
                'featured' => false,
                'description' => 'Waterproof mascara with intense volume.'
            ],

            [
                'name' => 'Luxury Velvet Lipstick',
                'category' => 'Makeup',
                'brand' => 'L\'Oréal Paris',
                'price' => 16.99,
                'tax' => 8,
                'featured' => true,
                'description' => 'Smooth matte lipstick with rich pigments.'
            ],

            [
                'name' => 'Silky Repair Shampoo',
                'category' => 'Hair Care',
                'brand' => 'Garnier',
                'price' => 11.99,
                'tax' => 5,
                'featured' => false,
                'description' => 'Repair shampoo for damaged hair.'
            ],

            [
                'name' => 'Keratin Hair Mask',
                'category' => 'Hair Care',
                'brand' => 'L\'Oréal Paris',
                'price' => 21.99,
                'tax' => 5,
                'featured' => true,
                'description' => 'Hair mask enriched with keratin.'
            ],

            [
                'name' => 'Fresh Breeze Body Lotion',
                'category' => 'Body Care',
                'brand' => 'Nivea',
                'price' => 13.99,
                'tax' => 5,
                'featured' => false,
                'description' => 'Lightweight body lotion for everyday hydration.'
            ],

            [
                'name' => 'Silk Touch Body Wash',
                'category' => 'Body Care',
                'brand' => 'Dove',
                'price' => 12.50,
                'tax' => 5,
                'featured' => true,
                'description' => 'Gentle moisturizing body wash for soft skin.'
            ],

            [
                'name' => 'Rose Bloom Eau De Parfum',
                'category' => 'Fragrances',
                'brand' => 'L\'Oréal Paris',
                'price' => 39.99,
                'tax' => 10,
                'featured' => true,
                'description' => 'Elegant floral perfume with long-lasting freshness.'
            ],

            [
                'name' => 'Ocean Mist Cologne',
                'category' => 'Fragrances',
                'brand' => 'Garnier',
                'price' => 29.99,
                'tax' => 10,
                'featured' => false,
                'description' => 'Refreshing aquatic fragrance for daily use.'
            ],

            [
                'name' => 'Professional Makeup Brush Set',
                'category' => 'Beauty Tools',
                'brand' => 'Maybelline',
                'price' => 22.99,
                'tax' => 8,
                'featured' => false,
                'description' => 'Premium makeup brush set for flawless application.'
            ],

            [
                'name' => 'Beauty Blender Sponge',
                'category' => 'Beauty Tools',
                'brand' => 'L\'Oréal Paris',
                'price' => 9.99,
                'tax' => 8,
                'featured' => true,
                'description' => 'Soft blending sponge for liquid and cream makeup.'
            ],

            [
                'name' => 'Glow Renewal Night Cream',
                'category' => 'Skincare',
                'brand' => 'CeraVe',
                'price' => 27.99,
                'tax' => 5,
                'featured' => true,
                'description' => 'Overnight nourishing cream that restores skin moisture.'
            ],

        ];

        foreach ($products as $item) {

            $category = Categories::where('name', $item['category'])->first();
            $brand = Brands::where('name', $item['brand'])->first();

            $product = Products::updateOrCreate(

                [
                    'slug' => Str::slug($item['name']),
                ],

                [
                    'categorey_id' => $category?->id,
                    'brand_id' => $brand?->id,
                    'name' => $item['name'],
                    'slug' => Str::slug($item['name']),
                    'image' => 'https://picsum.photos/seed/' . Str::slug($item['name']) . '/600/600',
                    'description' => $item['description'],
                    'sku' => 'SKU-' . strtoupper(Str::random(8)),
                    'tax' => $item['tax'],
                    'price' => $item['price'],
                    'is_active' => true,
                    'is_featured' => $item['featured'],
                    'meta_title' => $item['name'],
                    'meta_description' => $item['description'],
                    'return_policy' => '4 days',
                ]
            );

            Stock::updateOrCreate(
                ['product_id' => $product->id],
                [
                    'quantity' => rand(15, 120),
                ]
            );

            for ($i = 1; $i <= 3; $i++) {

                ProductImages::updateOrCreate(

                    [
                        'product_id' => $product->id,
                        'image' => "https://picsum.photos/seed/" . Str::slug($item['name']) . "-{$i}/600/600",
                    ]
                );
            }

            if (in_array($item['category'], ['Skincare', 'Hair Care', 'Body Care'])) {

                foreach (['50ml', '100ml', '200ml'] as $size) {

                    ProductSizes::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'size' => $size,
                        ]
                    );
                }
            }

            if ($item['category'] === 'Makeup') {

                foreach (['Ivory', 'Beige', 'Sand', 'Rose', 'Nude'] as $color) {

                    ProductColors::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'color' => $color,
                        ]
                    );
                }
            }
        }
    }
}
