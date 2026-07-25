<?php

namespace App\Services\Shipping;

class LocalPickupShippingProvider extends ManualShippingProvider
{
    public function getRates(array $data): array
    {
        return [[
            'id' => 'local_pickup',
            'provider' => 'local_pickup',
            'carrier' => 'Local Pickup',
            'service' => 'Pickup',
            'amount' => 0,
            'currency' => $data['currency'] ?? config('checkout.currency', 'EGP'),
        ]];
    }
}
