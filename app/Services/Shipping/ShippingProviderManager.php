<?php

namespace App\Services\Shipping;

use App\Interfaces\ShippingProviderInterface;
use InvalidArgumentException;

class ShippingProviderManager
{
    public function resolve(string $code): ShippingProviderInterface
    {
        return match ($code) {
            'easypost' => app(EasyPostShippingProvider::class),
            'flat_rate' => app(FlatRateShippingProvider::class),
            'free_shipping' => app(FreeShippingProvider::class),
            'local_pickup' => app(LocalPickupShippingProvider::class),
            'manual' => app(ManualShippingProvider::class),
            default => throw new InvalidArgumentException("Unsupported shipping provider [{$code}]."),
        };
    }
}
