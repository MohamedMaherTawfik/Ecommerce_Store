# Paymob Setup

This application uses Paymob as its only payment gateway. Cards, Apple Pay, and mobile wallets all use the existing Laravel payment lifecycle and Paymob Unified Checkout.

## Required environment variables

Copy these values into `.env`:

```dotenv
PAYMOB_ENABLED=true
PAYMOB_BASE_URL=https://accept.paymob.com
PAYMOB_PUBLIC_KEY=
PAYMOB_SECRET_KEY=
PAYMOB_HMAC_SECRET=
PAYMOB_INTEGRATION_ID_CARD=
PAYMOB_INTEGRATION_ID_WALLET=
PAYMOB_INTEGRATION_ID_APPLE_PAY=
PAYMOB_CURRENCY=EGP
PAYMOB_IFRAME_ID=
PAYMOB_CALLBACK_URL=https://store.example/api/v1/payment/paymob/callback
PAYMOB_WEBHOOK_URL=https://store.example/api/v1/webhooks/paymob

PAYMENT_SUCCESS_URL=https://store.example/en/checkout/success
PAYMENT_FAILED_URL=https://store.example/en/checkout/failed
PAYMENT_CANCEL_URL=https://store.example/en/checkout/cancel
CHECKOUT_CURRENCY=EGP
```

`PAYMOB_IFRAME_ID` is retained as an optional compatibility setting. Unified Checkout uses the public key and intention client secret and does not require an iframe ID.

Use the regional Paymob base URL assigned to the merchant account. Egypt normally uses `https://accept.paymob.com`.

## Dashboard configuration

1. Complete merchant verification in the Paymob dashboard.
2. Copy the API secret key, public key, and HMAC secret into `.env`.
3. Create or enable the required online payment integrations.
4. Copy each integration ID into its matching environment variable.
5. Set the Transaction Processed Callback to `PAYMOB_WEBHOOK_URL`.
6. Set the Transaction Response Callback to `PAYMOB_CALLBACK_URL`.
7. Run:

```bash
php artisan optimize:clear
php artisan db:seed --class=PaymentMethodsSeeder
```

Paymob describes the Transaction Processed Callback as the backend notification and the Transaction Response Callback as the customer redirect. The application verifies HMAC on both; the signed callback is the payment source of truth.

## Card setup

Enable an online card integration and set its ID as `PAYMOB_INTEGRATION_ID_CARD`.

Unified Checkout handles card entry and 3-D Secure outside the application. Visa and Mastercard availability depends on the Paymob account. Meeza must also be enabled for the merchant/integration before it will be offered.

## Mobile wallet setup

Enable the Paymob mobile-wallet integration and set its ID as `PAYMOB_INTEGRATION_ID_WALLET`.

The single wallet channel is used for all wallets enabled on that Paymob integration, including Vodafone Cash, Orange Cash, e& cash, WE Pay, and any other account-supported wallets. The application does not duplicate wallet-specific payment flows.

## Apple Pay setup

1. Ask Paymob to enable Apple Pay for the merchant account.
2. Create or obtain the Apple Pay integration ID.
3. Set `PAYMOB_INTEGRATION_ID_APPLE_PAY`.
4. Complete Apple merchant-domain verification for the production domain.
5. Serve Apple's verification file at:

```text
https://store.example/.well-known/apple-developer-merchantid-domain-association
```

Apple Pay is shown only when its integration ID is configured and the browser exposes Apple Pay support.

## Unified Checkout flow

1. The customer selects Cards, Apple Pay, or Mobile Wallets.
2. Laravel creates the order through the shared checkout service using `payment_method=paymob`.
3. Checkout persists the address snapshot, order items, tax, shipping, invoice, shipment, stock movement, and pending payment.
4. `PaymentGatewayManager` resolves `PaymobPaymentService`.
5. The service creates a Paymob payment intention with the selected channel's integration ID.
6. The pending payment and intention ID are stored in the existing `payments` table.
7. The browser is redirected to Paymob Unified Checkout.
8. Paymob sends a signed callback.
9. `PaymentWebhookController` logs, verifies, deduplicates, and applies the state through `PaymentStatusService`.

## Callback and webhook security

The endpoints are:

```text
POST     /api/v1/webhooks/paymob
GET|POST /api/v1/payment/paymob/callback
```

Both endpoints are public by necessity and are protected with Paymob SHA-512 HMAC verification. Do not place them behind session authentication. Always use HTTPS in production.

The application:

- concatenates Paymob's documented transaction callback fields in the required order;
- uses `PAYMOB_HMAC_SECRET` with HMAC SHA-512;
- rejects invalid signatures;
- logs callback payloads and safe headers;
- deduplicates transaction events;
- verifies paid and refunded amounts/currencies against the local order.

## Refunds

Refund requests created in the admin remain pending. Complete the refund in the Paymob dashboard for the original transaction. The signed Paymob refund callback changes the order/payment to `refunded` or `partially_refunded` and completes the pending local refund record.

This dashboard-led flow avoids claiming that funds were returned before Paymob confirms them. Card void availability and refund eligibility depend on payment method, settlement state, country, and account configuration.

## Testing

Use Paymob test credentials and test integration IDs. Do not mix live public/secret keys with test integration IDs.

Run the payment tests:

```bash
php artisan test --compact tests/Feature/PaymentGatewayIntegrationTest.php
php artisan test --compact tests/Feature/PaymentStatusServiceTest.php
php artisan test
npm run build
```

The automated coverage verifies:

- card, Apple Pay, and wallet integration-ID selection;
- Unified Checkout intention creation and persistence;
- invalid HMAC rejection;
- successful, failed, cancelled/voided, and refunded callbacks;
- duplicate webhook idempotency;
- amount mismatch rejection;
- signed response-callback redirects;
- order and payment status updates.

For an end-to-end sandbox check:

1. Place one order with each enabled channel.
2. Confirm the hosted page displays only methods attached to that intention.
3. Complete one successful and one declined payment.
4. Confirm the order, payment, webhook log, notification, and timeline records.
5. Repeat a callback with Paymob's webhook testing tool and confirm it is treated as a duplicate.

## Production deployment

1. Use production Paymob keys and production integration IDs.
2. Set `APP_URL`, callback URLs, webhook URL, and frontend result URLs to HTTPS production URLs.
3. Verify the Apple Pay production domain when Apple Pay is enabled.
4. Ensure Paymob can reach both callback endpoints without authentication, a VPN, or an IP-only local hostname.
5. Deploy and run:

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan db:seed --class=PaymentMethodsSeeder --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

6. Start the configured Laravel queue worker for payment emails and notifications.
7. Confirm production mail delivery, public uploaded-media storage, and database backups.
8. Perform a low-value live transaction for every enabled channel before opening checkout to customers.

## References

- [Paymob checkout experiences](https://developers.paymob.com/paymob-docs/developers/checkout-experiences)
- [Paymob API integration flow](https://developers.paymob.com/paymob-docs/integration-paths/apis)
- [Paymob webhook callbacks and HMAC](https://developers.paymob.com/paymob-docs/developers/webhook-callbacks-and-hmac)
- [Paymob Apple Pay domain verification](https://developers.paymob.com/paymob-docs/need-help/faq/apple-pay-domain-verification)
