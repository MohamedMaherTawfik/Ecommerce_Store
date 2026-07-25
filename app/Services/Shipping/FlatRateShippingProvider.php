<?php

namespace App\Services\Shipping;

class FlatRateShippingProvider extends ManualShippingProvider
{
    public function getRates(array $data): array
    {
        return [[
            'id' => 'flat_rate',
            'provider' => 'flat_rate',
            'carrier' => 'Flat Rate',
            'service' => 'Standard',
            'amount' => (float) ($data['rate'] ?? config('checkout.flat_rate', 10)),
            'currency' => $data['currency'] ?? config('checkout.currency', 'EGP'),
        ]];
    }
}
