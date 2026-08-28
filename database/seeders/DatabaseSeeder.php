<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(userSeeder::class);
        $this->call(CategorySeeder::class);
        $this->call(BrandSeeder::class);
        $this->call(ProductSeeder::class);
        $this->call(HomeContentSeeder::class);
        $this->call(LayoutSeeder::class);
        $this->call(PaymentMethodsSeeder::class);
        $this->call(ShippingMethodsSeeder::class);
        $this->call(TaxRulesSeeder::class);
        $this->call(PalleteSeeder::class);
    }
}
