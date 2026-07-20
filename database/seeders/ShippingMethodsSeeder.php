<?php

namespace Database\Seeders;

use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use Illuminate\Database\Seeder;

class ShippingMethodsSeeder extends Seeder
{
    public function run(): void
    {
        $flat = ShippingMethod::updateOrCreate(
            ['code' => 'flat_rate'],
            ['name' => 'Flat Rate', 'provider' => 'flat_rate', 'is_active' => true, 'is_default' => true, 'settings' => ['rate' => 10]]
        );

        ShippingRate::updateOrCreate(
            ['shipping_method_id' => $flat->id, 'name' => 'Standard Shipping'],
            ['rate' => 10, 'is_active' => true]
        );

        ShippingMethod::updateOrCreate(
            ['code' => 'free_shipping'],
            ['name' => 'Free Shipping', 'provider' => 'free_shipping', 'is_active' => true, 'settings' => ['min_order_amount' => 100]]
        );

        ShippingMethod::updateOrCreate(
            ['code' => 'local_pickup'],
            ['name' => 'Local Pickup', 'provider' => 'local_pickup', 'is_active' => true]
        );

        ShippingMethod::updateOrCreate(
            ['code' => 'easypost'],
            ['name' => 'EasyPost', 'provider' => 'easypost', 'is_active' => false]
        );
    }
}
