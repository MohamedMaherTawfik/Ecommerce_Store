<?php

namespace Tests\Feature;

use App\Models\brands;
use App\Models\Categories;
use App\Models\Products;
use App\Models\Stock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserCatalogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories_and_brands_are_publicly_listed(): void
    {
        Categories::factory()->create(['name' => 'Shirts']);
        brands::factory()->create(['name' => 'Urban Loom']);

        $this->getJson('/api/v1/categories')
            ->assertOk()
            ->assertJsonPath('data.data.0.name', 'Shirts');

        $this->getJson('/api/v1/brands')
            ->assertOk()
            ->assertJsonPath('data.data.0.name', 'Urban Loom');
    }

    public function test_products_can_be_filtered_and_sorted(): void
    {
        $category = Categories::factory()->create();
        $brand = brands::factory()->create();

        $low = Products::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'Cotton Tee',
            'price' => 25,
            'is_active' => true,
        ]);
        Products::factory()->create(['price' => 120, 'is_active' => true]);
        Stock::create(['product_id' => $low->id, 'quantity' => 10]);

        $this->getJson("/api/v1/products?category_id={$category->id}&brand_id={$brand->id}&sort=price_asc")
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Cotton Tee');
    }
}
