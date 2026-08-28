<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodsSeeder extends Seeder
{
    public function run(): void
    {
        PaymentMethod::where('code', '!=', 'paymob')->update([
            'is_active' => false,
            'is_default' => false,
        ]);

        PaymentMethod::updateOrCreate(
            ['code' => 'paymob'],
            [
                'name' => 'Paymob Unified Checkout',
                'provider' => 'paymob',
                'is_active' => (bool) config('payment.gateways.paymob.enabled', true),
                'is_default' => true,
                'mode' => app()->environment('production') ? 'live' : 'test',
                'settings' => ['channels' => ['card', 'apple_pay', 'mobile_wallet']],
                'sort_order' => 1,
            ]
        );
    }
}
