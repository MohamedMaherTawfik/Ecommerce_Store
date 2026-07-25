# Ecommerce Store

A Laravel 12 and Vue 3 ecommerce application with catalog, cart, checkout, orders, inventory, shipping, tax, returns, invoices, notifications, and administration modules.

## Payments

Paymob is the application's only payment gateway. The existing payment architecture is retained:

- `PaymentGatewayManager`
- `PaymentInterface`
- `CheckoutService`
- `PaymobPaymentService`
- `PaymentStatusService`
- `Payment`, `PaymentMethod`, and `PaymentWebhookLog`
- the existing order, inventory, invoice, notification, and timeline lifecycle

Checkout supports Paymob Unified Checkout for cards, Apple Pay when configured and available, and mobile wallets.

See [PAYMOB_SETUP.md](PAYMOB_SETUP.md) for credentials, dashboard setup, callbacks, HMAC verification, testing, refunds, and production deployment.

## Local setup

```bash
composer install
npm install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

Configure Paymob before testing checkout.

## Tests

```bash
php artisan test
npm test
```
