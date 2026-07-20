<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodsSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            ['name' => 'PayPal', 'code' => 'paypal', 'provider' => 'paypal', 'is_active' => true, 'is_default' => true, 'mode' => env('PAYPAL_MODE', 'sandbox'), 'sort_order' => 1],
            ['name' => 'Cash on Delivery', 'code' => 'cod', 'provider' => 'manual', 'is_active' => true, 'mode' => 'live', 'sort_order' => 2],
            ['name' => 'Stripe', 'code' => 'stripe', 'provider' => 'stripe', 'is_active' => filter_var(env('STRIPE_ENABLED', false), FILTER_VALIDATE_BOOLEAN), 'mode' => 'test', 'sort_order' => 3],
            ['name' => 'Paymob', 'code' => 'paymob', 'provider' => 'paymob', 'is_active' => filter_var(env('PAYMOB_ENABLED', false), FILTER_VALIDATE_BOOLEAN), 'mode' => 'test', 'sort_order' => 4],
            ['name' => 'MyFatoorah', 'code' => 'myfatoorah', 'provider' => 'myfatoorah', 'is_active' => filter_var(env('MYFATOORAH_ENABLED', false), FILTER_VALIDATE_BOOLEAN), 'mode' => 'test', 'sort_order' => 5],
            ['name' => 'Bioneer', 'code' => 'bioneer', 'provider' => 'bioneer', 'is_active' => false, 'mode' => 'test', 'sort_order' => 6],
        ])->each(fn ($method) => PaymentMethod::updateOrCreate(['code' => $method['code']], $method));
    }
}
