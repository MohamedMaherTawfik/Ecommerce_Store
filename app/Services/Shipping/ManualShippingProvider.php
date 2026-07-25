<?php

namespace App\Services\Shipping;

use App\Interfaces\ShippingProviderInterface;

class ManualShippingProvider implements ShippingProviderInterface
{
    public function getRates(array $data): array
    {
        return [[
            'id' => 'manual',
            'provider' => 'manual',
            'carrier' => 'Manual',
            'service' => 'Manual Shipping',
            'amount' => (float) ($data['amount'] ?? 0),
            'currency' => $data['currency'] ?? config('checkout.currency', 'EGP'),
        ]];
    }

    public function createShipment(array $data): array
    {
        return ['provider' => 'manual', 'status' => 'pending', 'raw' => $data];
    }

    public function buyLabel(array $data): array
    {
        return ['provider' => 'manual', 'status' => 'label_not_required', 'raw' => $data];
    }

    public function track(string $trackingNumber): array
    {
        return ['provider' => 'manual', 'tracking_number' => $trackingNumber, 'status' => 'unknown'];
    }

    public function verifyAddress(array $address): array
    {
        return ['valid' => true, 'address' => $address];
    }
}
