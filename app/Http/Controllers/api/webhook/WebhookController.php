<?php

namespace App\Http\Controllers\api\webhook;

use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Orders;
use App\Services\PayPalServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected PayPalServices $paypal,
    ) {}

    public function handle(Request $request)
    {
        $body = $request->getContent();
        $eventData = json_decode($body, true);

        if (!is_array($eventData)) {
            Log::error('WebhookController: Invalid JSON body');
            return $this->error('Invalid JSON payload.', 400);
        }
        $webhookId = config('paypal.webhook_id');
        if (empty($webhookId)) {
            Log::error('WebhookController: webhook_id not configured');
            return $this->error('Webhook verification is not configured.', 500);
        }

        try {
            $provider = new \Srmklive\PayPal\Services\PayPal;
            $provider->setApiCredentials(config('paypal'));
            $provider->getAccessToken();

            $verifyData = [
                'transmission_id' => $request->header('PAYPAL-TRANSMISSION-ID'),
                'transmission_time' => $request->header('PAYPAL-TRANSMISSION-TIME'),
                'cert_url' => $request->header('PAYPAL-CERT-URL'),
                'auth_algo' => $request->header('PAYPAL-AUTH-ALGO'),
                'transmission_sig' => $request->header('PAYPAL-TRANSMISSION-SIG'),
                'webhook_id' => $webhookId,
                'webhook_event' => json_decode($body),
            ];

            $verify = $provider->verifyWebHook($verifyData);

            if (($verify['verification_status'] ?? '') !== 'SUCCESS') {
                Log::warning('WebhookController: Signature verification FAILED', [
                    'ip' => $request->ip(),
                    'transmission_id' => $request->header('PAYPAL-TRANSMISSION-ID'),
                    'verification_status' => $verify['verification_status'] ?? 'UNKNOWN',
                ]);

                return $this->error('Webhook signature is invalid.', 400);
            }
        } catch (\Exception $e) {
            Log::error('WebhookController: Verification exception', [
                'message' => $e->getMessage(),
            ]);

            return $this->error('Webhook verification failed.', 500);
        }

        $paypalOrderId = $this->extractPaypalOrderId($eventData);

        if (!$paypalOrderId) {
            Log::warning('WebhookController: Could not resolve paypal_order_id', [
                'event_type' => $eventData['event_type'] ?? null,
            ]);

            return $this->success([], 'Webhook ignored.');
        }

        $order = Orders::where('paypal_order_id', $paypalOrderId)->first();

        if (!$order) {
            Log::warning('WebhookController: Order not found for webhook', [
                'paypal_order_id' => $paypalOrderId,
            ]);

            return $this->success([], 'Webhook ignored.');
        }

        Log::info('WebhookController: Dispatching webhook', [
            'event_type' => $eventData['event_type'] ?? null,
            'paypal_order_id' => $paypalOrderId,
            'order_id' => $order->id,
            'type' => $order->type,
        ]);

        $this->paypal->handleWebhook($eventData);

        return $this->success([
            'handled_by' => 'checkout',
        ], 'Webhook processed successfully.');
    }

    private function extractPaypalOrderId(array $eventData): ?string
    {
        $eventType = $eventData['event_type'] ?? null;
        $resource = $eventData['resource'] ?? [];

        return match ($eventType) {
            'CHECKOUT.ORDER.APPROVED' => $resource['id'] ?? null,
            'PAYMENT.CAPTURE.COMPLETED',
            'PAYMENT.CAPTURE.DECLINED' => $resource['supplementary_data']['related_ids']['order_id'] ?? null,
            default => $resource['id'] ?? null,
        };
    }
}
