<?php

namespace Database\Seeders;

use App\Models\brands;
use App\Models\Categories;
use Illuminate\Database\Seeder;

class BrandCategorySeeder extends Seeder
{
    public function run()
    {
        Categories::factory(5)->create();
        brands::factory(5)->create();
    }
}
