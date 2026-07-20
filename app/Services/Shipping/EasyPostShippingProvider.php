<?php

namespace App\Services\Shipping;

use App\Interfaces\ShippingProviderInterface;
use RuntimeException;

class EasyPostShippingProvider implements ShippingProviderInterface
{
    public function getRates(array $data): array
    {
        if (!class_exists(\EasyPost\EasyPostClient::class)) {
            throw new RuntimeException('EasyPost SDK is not installed. Require easypost/easypost-php to enable live EasyPost rates.');
        }

        $shipment = $this->client()->shipment->create($this->shipmentPayload($data));

        return collect($shipment->rates ?? [])->map(fn ($rate) => [
            'id' => $rate->id,
            'provider' => 'easypost',
            'carrier' => $rate->carrier,
            'service' => $rate->service,
            'amount' => (float) $rate->rate,
            'currency' => $rate->currency,
            'raw' => $rate,
        ])->values()->all();
    }

    public function createShipment(array $data): array
    {
        if (!class_exists(\EasyPost\EasyPostClient::class)) {
            throw new RuntimeException('EasyPost SDK is not installed.');
        }

        $shipment = $this->client()->shipment->create($this->shipmentPayload($data));

        return ['provider' => 'easypost', 'shipment' => $shipment];
    }

    public function buyLabel(array $data): array
    {
        if (!class_exists(\EasyPost\EasyPostClient::class)) {
            throw new RuntimeException('EasyPost SDK is not installed.');
        }

        $shipment = $this->client()->shipment->retrieve($data['shipment_id']);
        $shipment = $this->client()->shipment->buy($shipment->id, ['rate' => ['id' => $data['rate_id']]]);

        return [
            'provider' => 'easypost',
            'tracking_number' => $shipment->tracking_code ?? null,
            'tracking_url' => $shipment->tracker->public_url ?? null,
            'label_url' => $shipment->postage_label->label_url ?? null,
            'raw' => $shipment,
        ];
    }

    public function track(string $trackingNumber): array
    {
        if (!class_exists(\EasyPost\EasyPostClient::class)) {
            throw new RuntimeException('EasyPost SDK is not installed.');
        }

        $tracker = $this->client()->tracker->create(['tracking_code' => $trackingNumber]);

        return [
            'provider' => 'easypost',
            'tracking_number' => $trackingNumber,
            'status' => $tracker->status ?? null,
            'tracking_url' => $tracker->public_url ?? null,
            'raw' => $tracker,
        ];
    }

    public function verifyAddress(array $address): array
    {
        if (!class_exists(\EasyPost\EasyPostClient::class)) {
            throw new RuntimeException('EasyPost SDK is not installed.');
        }

        $verified = $this->client()->address->createAndVerify($address);

        return ['valid' => true, 'address' => $verified];
    }

    private function client(): \EasyPost\EasyPostClient
    {
        return new \EasyPost\EasyPostClient(config('easypost.api_key'));
    }

    private function shipmentPayload(array $data): array
    {
        return [
            'from_address' => config('easypost.from'),
            'to_address' => $data['to_address'] ?? [],
            'parcel' => $data['parcel'] ?? ['length' => 10, 'width' => 8, 'height' => 4, 'weight' => 16],
        ];
    }
}
