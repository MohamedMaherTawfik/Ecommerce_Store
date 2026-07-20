<?php

namespace App\Interfaces;

use Illuminate\Http\Request;

interface HandlesPaymentWebhooks
{
    public function handleWebhook(Request $request): array;
}
