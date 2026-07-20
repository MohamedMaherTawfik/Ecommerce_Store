<?php

namespace App\Services\Checkout;

use App\Models\TaxRule;

class TaxService
{
    public function calculate(array $address, float $subtotal, float $shippingAmount = 0): array
    {
        $rules = TaxRule::query()
            ->where('is_active', true)
            ->where(function ($query) use ($address) {
                $query->whereNull('country')->orWhere('country', $address['country'] ?? null);
            })
            ->where(function ($query) use ($address) {
                $query->whereNull('state')->orWhere('state', $address['state'] ?? null);
            })
            ->where(function ($query) use ($address) {
                $query->whereNull('city')->orWhere('city', $address['city'] ?? null);
            })
            ->orderByDesc('priority')
            ->get();

        $lines = [];
        $total = 0.0;
        $taxIncluded = false;

        foreach ($rules as $rule) {
            $base = $subtotal + ($rule->applies_to_shipping ? $shippingAmount : 0);
            $amount = $rule->type === 'fixed'
                ? (float) $rule->rate
                : round($base * ((float) $rule->rate / 100), 2);

            $total += $amount;
            $taxIncluded = $taxIncluded || $rule->price_includes_tax;
            $lines[] = [
                'tax_rule_id' => $rule->id,
                'name' => $rule->name,
                'rate' => (float) $rule->rate,
                'amount' => $amount,
                'price_includes_tax' => (bool) $rule->price_includes_tax,
            ];
        }

        return [
            'amount' => round($total, 2),
            'included' => $taxIncluded,
            'lines' => $lines,
        ];
    }
}
