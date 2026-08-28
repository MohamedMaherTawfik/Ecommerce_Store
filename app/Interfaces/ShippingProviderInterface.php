<?php

namespace App\Interfaces;

interface ShippingProviderInterface
{
    public function getRates(array $data): array;

    public function createShipment(array $data): array;

    public function buyLabel(array $data): array;

    public function track(string $trackingNumber): array;

    public function verifyAddress(array $address): array;
}
