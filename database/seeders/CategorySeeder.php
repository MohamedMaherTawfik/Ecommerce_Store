<?php

namespace Database\Seeders;

use App\Models\Categories;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Skincare',
                'image' => 'https://picsum.photos/seed/skincare/600/600',
            ],
            [
                'name' => 'Makeup',
                'image' => 'https://picsum.photos/seed/makeup/600/600',
            ],
            [
                'name' => 'Hair Care',
                'image' => 'https://picsum.photos/seed/hair-care/600/600',
            ],
            [
                'name' => 'Fragrances',
                'image' => 'https://picsum.photos/seed/fragrances/600/600',
            ],
            [
                'name' => 'Body Care',
                'image' => 'https://picsum.photos/seed/body-care/600/600',
            ],
            [
                'name' => 'Beauty Tools',
                'image' => 'https://picsum.photos/seed/beauty-tools/600/600',
            ],
        ];

        foreach ($categories as $category) {
            Categories::updateOrCreate(
                ['name' => $category['name']],
                [
                    'image' => $category['image'],
                    'slug' => Str::slug($category['name']),
                ]
            );
        }
    }
}
