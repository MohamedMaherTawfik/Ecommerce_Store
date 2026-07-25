<?php

namespace App\Services\Shipping;

class FreeShippingProvider extends ManualShippingProvider
{
    public function getRates(array $data): array
    {
        return [[
            'id' => 'free_shipping',
            'provider' => 'free_shipping',
            'carrier' => 'Free Shipping',
            'service' => 'Free Shipping',
            'amount' => 0,
            'currency' => $data['currency'] ?? config('checkout.currency', 'EGP'),
        ]];
    }
}
