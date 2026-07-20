<?php

namespace App\Services\Payment;

class BioneerPaymentService extends PlaceholderPaymentService
{
    protected string $gateway = 'bioneer';
}
