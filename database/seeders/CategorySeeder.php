<?php

namespace Database\Seeders;

use App\Models\Categories;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        collect([
            ['name' => 'T-Shirts', 'image' => 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=900&auto=format&fit=crop'],
            ['name' => 'Shirts', 'image' => 'https://images.unsplash.com/photo-1603252109303-2751441dd157?w=900&auto=format&fit=crop'],
            ['name' => 'Pants', 'image' => 'https://images.unsplash.com/photo-1542272604-787c3835535d?w=900&auto=format&fit=crop'],
            ['name' => 'Jackets', 'image' => 'https://images.unsplash.com/photo-1551028719-00167b16eac5?w=900&auto=format&fit=crop'],
            ['name' => 'Sweaters', 'image' => 'https://images.unsplash.com/photo-1576566588028-4147f3842f27?w=900&auto=format&fit=crop'],
            ['name' => 'Accessories', 'image' => 'https://images.unsplash.com/photo-1511556532299-8f662fc26c06?w=900&auto=format&fit=crop'],
        ])->each(fn ($category) => Categories::updateOrCreate(
            ['name' => $category['name']],
            ['slug' => Str::slug($category['name']), 'image' => $category['image']]
        ));
    }
}
