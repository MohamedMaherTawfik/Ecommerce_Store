<?php

namespace App\Http\Controllers\api\payment;

use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Services\Payment\PaymentGatewayManager;
use Illuminate\Http\Request;

class PaymentCallbackController extends Controller
{
    use ApiResponse;

    public function success(Request $request, PaymentGatewayManager $manager, string $gateway)
    {
        $token = (string) (
            $request->query('paymentId')
            ?? $request->query('PaymentId')
            ?? $request->query('session_id')
            ?? $request->query('id')
            ?? $request->query('token')
            ?? $request->query('reference')
            ?? ''
        );

        $manager->resolve($gateway)->success($token);

        $url = config('services.payment_urls.success', '/checkout/success');
        return redirect()->to($url . '?token=' . urlencode($token));
    }

    public function cancel(PaymentGatewayManager $manager, string $gateway)
    {
        $manager->resolve($gateway)->cancel();

        $url = config('services.payment_urls.cancel', '/checkout/cancel');
        return redirect()->to($url);
    }
}
