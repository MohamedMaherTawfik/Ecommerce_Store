<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\brands;
use App\Models\Categories;

class BrandCategorySeeder extends Seeder
{
    public function run()
    {
        Categories::factory(5)->create();
        brands::factory(5)->create();
    }
}