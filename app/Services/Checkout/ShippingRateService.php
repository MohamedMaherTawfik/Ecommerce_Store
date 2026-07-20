<?php

namespace App\Services\Checkout;

use App\Models\ShippingMethod;
use App\Services\Shipping\ShippingProviderManager;
use Throwable;

class ShippingRateService
{
    public function __construct(private readonly ShippingProviderManager $providers)
    {
    }

    public function rates(array $address, float $subtotal, string $currency): array
    {
        return ShippingMethod::query()
            ->where('is_active', true)
            ->with(['rates' => fn ($query) => $query->where('is_active', true)->with('zone')])
            ->orderByDesc('is_default')
            ->get()
            ->flatMap(function (ShippingMethod $method) use ($address, $subtotal, $currency) {
                if (in_array($method->code, ['manual', 'flat_rate', 'free_shipping', 'local_pickup'], true)) {
                    return $this->manualRates($method, $address, $subtotal, $currency);
                }

                try {
                    return $this->providers->resolve($method->code)->getRates([
                        'to_address' => $address,
                        'currency' => $currency,
                    ]);
                } catch (Throwable $e) {
                    return [[
                        'id' => $method->code,
                        'provider' => $method->code,
                        'carrier' => $method->name,
                        'service' => 'Unavailable',
                        'amount' => null,
                        'currency' => $currency,
                        'error' => $e->getMessage(),
                    ]];
                }
            })
            ->values()
            ->all();
    }

    private function manualRates(ShippingMethod $method, array $address, float $subtotal, string $currency): array
    {
        $matched = $method->rates->filter(function ($rate) use ($address, $subtotal) {
            $zone = $rate->zone;
            $zoneMatches = !$zone
                || (($zone->country === null || $zone->country === ($address['country'] ?? null))
                && ($zone->state === null || $zone->state === ($address['state'] ?? null))
                && ($zone->city === null || $zone->city === ($address['city'] ?? null)));

            $minMatches = $rate->min_order_amount === null || $subtotal >= (float) $rate->min_order_amount;
            $maxMatches = $rate->max_order_amount === null || $subtotal <= (float) $rate->max_order_amount;

            return $zoneMatches && $minMatches && $maxMatches;
        });

        if ($matched->isEmpty()) {
            return $this->providers->resolve($method->code)->getRates([
                'currency' => $currency,
                'rate' => data_get($method->settings, 'rate', 0),
            ]);
        }

        return $matched->map(fn ($rate) => [
            'id' => (string) $rate->id,
            'shipping_method_id' => $method->id,
            'provider' => $method->code,
            'carrier' => $method->name,
            'service' => $rate->name,
            'amount' => $rate->is_percentage ? round($subtotal * ((float) $rate->rate / 100), 2) : (float) $rate->rate,
            'currency' => $currency,
        ])->values()->all();
    }
}
