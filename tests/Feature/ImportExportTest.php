<?php

namespace Tests\Feature;

use App\Models\Products;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ImportExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
    }

    public function test_products_csv_import_reports_duplicates_and_can_update_existing_rows(): void
    {
        $csv = "name,sku,price,quantity,status\nProduct One,SKU-1,10.50,4,active\nDuplicate,SKU-1,20,2,active\n";

        $this->api()->post('/api/admin/import/products', [
            'file' => UploadedFile::fake()->createWithContent('products.csv', $csv),
        ])->assertOk()
            ->assertJsonPath('data.created', 1)
            ->assertJsonPath('data.duplicates', 1);

        $this->assertDatabaseHas('products', ['sku' => 'SKU-1', 'price' => 10.50]);
        $this->assertDatabaseHas('stocks', ['quantity' => 4]);

        $updateCsv = "name,sku,price,quantity,status\nProduct Updated,SKU-1,30,8,inactive\n";
        $this->api()->post('/api/admin/import/products', [
            'file' => UploadedFile::fake()->createWithContent('products.csv', $updateCsv),
            'update_existing' => true,
        ])->assertOk()->assertJsonPath('data.updated', 1);

        $this->assertDatabaseHas('products', ['sku' => 'SKU-1', 'name' => 'Product Updated', 'price' => 30]);
        $this->assertSame(1, Products::count());
    }

    public function test_import_validation_samples_and_exports_work(): void
    {
        $this->api()->postJson('/api/admin/import/categories', [])->assertStatus(422);
        $this->api()->get('/api/admin/import/sample/products?format=csv')->assertOk();
        $this->api()->get('/api/admin/export/products?format=csv')->assertOk();
        $this->api()->get('/api/admin/export/categories?format=xlsx')->assertOk();
        $this->api()->get('/api/admin/export/orders?format=csv')->assertOk();
    }
}
