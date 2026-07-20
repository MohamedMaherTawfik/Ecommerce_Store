<?php

namespace App\Services\Payment;

use App\Interfaces\PaymentInterface;
use App\Services\PayPalServices;
use InvalidArgumentException;

class PaymentGatewayManager
{
    public function resolve(string $code): PaymentInterface
    {
        return match ($code) {
            'paypal' => app(PayPalServices::class),
            'cod', 'cash_on_delivery' => app(CashOnDeliveryPaymentService::class),
            'stripe' => app(StripePaymentService::class),
            'paymob' => app(PaymobPaymentService::class),
            'myfatoorah' => app(MyFatoorahPaymentService::class),
            'bioneer', 'payoneer' => app(BioneerPaymentService::class),
            default => throw new InvalidArgumentException("Unsupported payment gateway [{$code}]."),
        };
    }
}
