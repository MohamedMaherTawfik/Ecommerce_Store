# Production deployment runbook

This application must be deployed behind HTTPS with separate, explicitly named API and storefront origins. Do not copy development credentials or use the PHP development server in production.

## Required configuration

1. Copy `.env.example` to `.env`, replace every example hostname, generate `APP_KEY`, and set `APP_INSTALLED=true` only after migrations and the first administrator are ready.
2. Keep `APP_ENV=production`, `APP_DEBUG=false`, and `ALLOW_ADMIN_ENV_EDITOR=false`.
3. Set `APP_URL`, `FRONTEND_URL`, callback URLs, and webhook URLs to HTTPS. Configure the load balancer or web server to forward the original scheme and host, and limit trusted proxies at the infrastructure boundary.
4. Set `CORS_ALLOWED_ORIGINS` to the exact storefront origin(s). Do not use `*`, localhost, origin patterns, or unrelated administration origins when credentials are enabled.
5. Set `SANCTUM_STATEFUL_DOMAINS` to the exact storefront host(s), including a port only when the public origin uses one. Keep auth cookies secure, HttpOnly, and SameSite strict.
6. Use MySQL or PostgreSQL with a dedicated least-privilege account. Use persistent database or Redis drivers for sessions, cache, and queues.
7. Configure a real mail transport and verified sender. The `array` and `log` mailers are test/development transports only.
8. Leave Paymob disabled until its public key, secret key, HMAC secret, integration IDs, and HTTPS callback/webhook URLs are configured. Rotate any credential that has ever appeared in source control or browser code.
9. Configure Google OAuth only when required, using an exact HTTPS redirect URI registered with Google.

Run the fail-closed configuration check after caching configuration:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan app:production-preflight
```

## Release sequence

```bash
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
npm ci
npm run build
php artisan migrate --force
php artisan storage:link
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan app:production-preflight
```

Deploy immutable application files and write only to `storage` and `bootstrap/cache`. Protect `.env`, logs, backups, exports, and uploaded temporary spreadsheets from direct web access. Point the web root at `public/` only.

## Queue and scheduler

Run queue workers under Supervisor, systemd, or an equivalent process manager. A representative worker command is:

```bash
php artisan queue:work --sleep=3 --tries=3 --timeout=60 --max-time=3600
```

Restart workers after every deployment with `php artisan queue:restart`. Alert on `failed_jobs`, queue age, repeated payment/mail failures, and worker absence. Queued mail and notifications use bounded retries and exponential backoff.

Run the scheduler every minute from one scheduler host:

```cron
* * * * * cd /var/www/store && php artisan schedule:run >> /dev/null 2>&1
```

## Verification and operations

- Confirm `/up`, the storefront catalog, login, cart, checkout initiation, admin login, and a permission-denied admin route over HTTPS.
- Exercise a sandbox payment and signed webhook before enabling live payments. Verify duplicate webhook delivery does not duplicate state changes.
- Verify return receipt restores stock once and refund requests remain idempotent.
- Confirm CSP, HSTS, frame, content-type, referrer, and permissions headers at the public edge.
- Ship structured application logs to restricted centralized storage. Never log passwords, tokens, OAuth secrets, payment keys, full webhook signatures, or raw settings payloads.
- Back up the database and persistent uploads, encrypt backups, test restoration, define retention, and document rollback for both code and migrations.
- Monitor HTTP error rates, latency, database saturation/deadlocks, queue depth, failed jobs, disk capacity, mail delivery, authentication throttling, and payment webhook failures.

External Paymob, Google OAuth, mail, DNS, certificate, and production database credentials cannot be validated from source control. Their sandbox/live checks remain deployment responsibilities.
