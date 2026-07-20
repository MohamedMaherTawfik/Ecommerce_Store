<?php

namespace App\Services\Payment;

use App\Interfaces\PaymentInterface;
use RuntimeException;

abstract class PlaceholderPaymentService implements PaymentInterface
{
    protected string $gateway = 'placeholder';

    public function pay(array $data): array
    {
        throw new RuntimeException("{$this->gateway} payment is configured as a skeleton. Add official credentials/API implementation before enabling it.");
    }

    public function success(string $token): array
    {
        return ['success' => true, 'gateway' => $this->gateway, 'token' => $token];
    }

    public function cancel(): array
    {
        return ['success' => false, 'gateway' => $this->gateway];
    }
}
