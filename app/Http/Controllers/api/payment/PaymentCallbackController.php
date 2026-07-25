<?php

namespace App\Http\Controllers\api\payment;

use App\Http\Controllers\api\webhook\PaymentWebhookController;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Payment\PaymentStatusService;
use App\Services\Payment\PaymobPaymentService;
use Illuminate\Http\Request;

class PaymentCallbackController extends Controller
{
    public function paymob(
        Request $request,
        PaymentWebhookController $webhooks,
        PaymobPaymentService $gateway,
        PaymentStatusService $payments
    ) {
        $response = $webhooks->paymob($request, $gateway, $payments);
        $payload = $response->getData(true);
        $status = (string) data_get($payload, 'data.status', 'failed');
        $transactionId = (string) $request->input('id', $request->query('id', ''));
        $payment = $transactionId === ''
            ? null
            : Payment::where('gateway', 'paymob')->where('transaction_id', $transactionId)->first();

        if (data_get($payload, 'data.duplicate') === true && $transactionId !== '') {
            $status = (string) ($payment?->status ?: 'pending');
        }

        $target = match ($status) {
            'paid' => config('payment.urls.success'),
            'cancelled' => config('payment.urls.cancel'),
            default => config('payment.urls.failed'),
        };
        $target = $target ?: ($payment?->order_id ? '/en/orders/'.$payment->order_id : '/en/cart');

        return redirect()->to($target.'?'.http_build_query([
            'status' => $status,
            'transaction_id' => $transactionId,
            'order_id' => $payment?->order_id,
        ]));
    }
}
