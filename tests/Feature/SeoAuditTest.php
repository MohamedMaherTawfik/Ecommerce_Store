<?php

namespace Tests\Feature;

use App\Models\Categories;
use App\Models\Products;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_page_renders_server_side_metadata_and_schema(): void
    {
        $category = Categories::factory()->create(['name' => 'Office']);
        $product = Products::factory()->create([
            'name' => 'Standing Desk',
            'slug' => 'standing-desk',
            'categories_id' => $category->id,
            'is_active' => true,
            'meta_title' => 'Ergonomic Standing Desk',
            'meta_description' => 'A height-adjustable standing desk for productive workspaces.',
        ]);

        $response = $this->get("/en/products/{$product->slug}");

        $response->assertOk()
            ->assertSee('<title>Ergonomic Standing Desk |', false)
            ->assertSee('name="description" content="A height-adjustable standing desk', false)
            ->assertSee('rel="canonical"', false)
            ->assertSee('"@type":"Product"', false)
            ->assertSee('hreflang="ar"', false);
    }

    public function test_numeric_product_url_permanently_redirects_to_slug(): void
    {
        $product = Products::factory()->create([
            'name' => 'Wireless Keyboard',
            'slug' => 'wireless-keyboard',
            'is_active' => true,
        ]);

        $this->get("/en/products/{$product->id}")
            ->assertRedirect("/en/products/{$product->slug}")
            ->assertStatus(301);
    }

    public function test_sitemap_and_robots_use_absolute_canonical_urls(): void
    {
        config()->set('app.frontend_url', 'https://shop.example.com');
        $category = Categories::factory()->create(['name' => 'Audio', 'slug' => 'audio']);
        $product = Products::factory()->create([
            'name' => 'Studio Headphones',
            'slug' => 'studio-headphones',
            'categories_id' => $category->id,
            'is_active' => true,
        ]);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee("https://shop.example.com/en/products/{$product->slug}", false)
            ->assertSee('https://shop.example.com/ar/products/category/audio', false);

        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Disallow: /admin', false)
            ->assertSee('Sitemap: https://shop.example.com/sitemap.xml', false);
    }

    public function test_catalog_slugs_are_generated_and_unique(): void
    {
        $first = Categories::factory()->create(['name' => 'Home Office', 'slug' => null]);
        $second = Categories::factory()->create(['name' => 'Home Office 2', 'slug' => 'home-office']);

        $this->assertSame('home-office', $first->slug);
        $this->assertSame('home-office-2', $second->slug);
    }
}
