<?php

namespace App\Services\Payment;

use App\Interfaces\PaymentInterface;
use App\Models\OrderStatusLog;
use App\Models\Payment;
use App\Models\PaymentMethod;

class CashOnDeliveryPaymentService implements PaymentInterface
{
    public function pay(array $data): array
    {
        $order = $data['order'];
        $method = PaymentMethod::firstOrCreate(
            ['code' => 'cod'],
            ['name' => 'Cash on Delivery', 'provider' => 'manual', 'mode' => 'live']
        );

        $payment = Payment::updateOrCreate(
            ['order_id' => $order->id, 'gateway' => 'cod'],
            [
                'user_id' => $order->user_id,
                'payment_method_id' => $method->id,
                'amount' => $order->total,
                'currency' => $order->currency ?? config('paypal.currency', 'USD'),
                'status' => 'pending',
            ]
        );

        $order->update([
            'payment_method' => 'cash_on_delivery',
            'payment_status' => 'pending',
            'order_status' => config('checkout.cod_confirms_order', false) ? 'confirmed' : 'pending',
            'status' => config('checkout.cod_confirms_order', false) ? 'confirmed' : 'pending',
        ]);

        OrderStatusLog::create([
            'order_id' => $order->id,
            'from_status' => null,
            'to_status' => $order->status,
            'changed_by' => $order->user_id,
            'note' => 'Cash on delivery payment created.',
        ]);

        return ['order' => $order->fresh(), 'payment' => $payment, 'approval_url' => null];
    }

    public function success(string $token): array
    {
        return ['success' => true, 'message' => 'Cash on delivery does not require callback.', 'token' => $token];
    }

    public function cancel(): array
    {
        return ['success' => false, 'message' => 'Cash on delivery cancelled.'];
    }
}
