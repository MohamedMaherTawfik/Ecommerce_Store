<?php

namespace App\Services\Payment;

use App\Interfaces\PaymentInterface;
use InvalidArgumentException;

class PaymentGatewayManager
{
    public function resolve(string $code): PaymentInterface
    {
        return match ($code) {
            'paymob' => app(PaymobPaymentService::class),
            default => throw new InvalidArgumentException("Unsupported payment gateway [{$code}]."),
        };
    }
}
