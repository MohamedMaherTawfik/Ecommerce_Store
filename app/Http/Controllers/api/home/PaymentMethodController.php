<?php

namespace App\Http\Controllers\api\home;

use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Controllers\Controller;

class PaymentMethodController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $channels = collect(config('payment.channels'))
            ->map(function (array $channel, string $code) {
                return [
                    'code' => $code,
                    ...$channel,
                    'available' => config('payment.gateways.paymob.enabled')
                        && filled(config("payment.gateways.paymob.integration_ids.{$code}")),
                ];
            })
            ->values();

        return $this->success([
            'gateway' => 'paymob',
            'channels' => $channels,
        ], 'Paymob payment methods loaded successfully.');
    }
}
