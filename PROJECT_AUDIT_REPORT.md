# Project Audit Report

Audit date: 2026-08-27  
Repository: `C:\xampp\htdocs\Ecommerce_Store`  
Audit type: evidence-based local functional, architecture, QA, and defensive security review

## 1. Executive Summary

**Overall status: NOT READY**

The application boots, discovers 256 routes, builds successfully, migrates and seeds from an empty database, rolls back and reapplies the latest migration, and passes most existing automated tests. The core catalog, checkout, Paymob signature verification, admin permissions, SEO, content, and support happy paths have meaningful automated coverage.

Production deployment is blocked by a broken browser/backend authentication contract, non-idempotent return/refund operations, browser exposure of privileged configuration, a stored-admin-XSS/token-theft chain, vulnerable locked dependencies, and clearly non-production runtime settings. The audit added focused regression tests rather than changing production behavior; five tests reproduce defects.

| Metric | Result |
| --- | ---: |
| Unique automated tests in final run | 60 |
| Passed | 55 |
| Failed | 5 |
| Skipped/blocked | 0 automated; several external/concurrency scenarios blocked |
| Critical findings | 0 |
| High findings | 6 |
| Medium findings | 6 |
| Low findings | 4 |

**Verdict: NOT READY**

## 2. Environment

| Component | Audited value |
| --- | --- |
| Operating environment | Windows / XAMPP / PowerShell, timezone Africa/Cairo |
| PHP | 8.3.30 |
| Composer | 2.9.7 |
| Laravel | 12.61.1 |
| Backend auth | Laravel Sanctum 4.x bearer tokens plus custom HttpOnly token cookie |
| Database used by application | SQLite |
| Destructive test database | Dedicated temporary SQLite file, deleted after testing |
| PHPUnit database | In-memory SQLite |
| Frontend | Vue 3.5, Vue Router 4.5, Pinia 3, Vite 8 |
| Node | 24.13.0 |
| npm | 11.6.2 |
| Mail during automated tests | Array/fake transports |
| Queue during automated tests | Synchronous/faked as applicable |

No credential values are included in this report.

## 3. Commands Executed

| Command | Exit | Evidence/result |
| --- | ---: | --- |
| `php --version` | 0 | PHP 8.3.30 |
| `composer --version` | 0 | Composer 2.9.7 |
| `node --version`; `npm --version` | 0 | Node 24.13.0; npm 11.6.2 |
| `php artisan --version` | 0 | Laravel 12.61.1 |
| `php artisan about --only=environment --only=drivers` | 0 | Application booted; drivers resolved |
| `php artisan route:list --json` | 0 | 256 routes; no duplicate method/URI pairs |
| `php artisan migrate:status` | 0 | Developer database connection readable |
| `php artisan migrate:fresh --force` against dedicated audit SQLite | 0 | All 55 migrations applied |
| `php artisan db:seed --force` against dedicated audit SQLite | 0 | All configured seeders completed |
| `php artisan migrate:rollback --step=1` then `migrate` | 0 | Latest migration rolled back and reapplied |
| `php artisan test` before audit tests | 0 | 45 passed, 320 assertions |
| `php artisan test` final | 1 | 46 passed, 5 failed, 333 assertions |
| `npm test` final | 0 | 9 passed in 4 files |
| `npm run build` | 0 | 384 modules transformed; production assets emitted |
| `composer validate --no-check-publish` | 0 | Manifest/lock validation passed |
| `composer audit --locked` | 1 | 21 advisories across 5 packages |
| `npm audit --omit=dev` | 1 | 2 high-severity advisories |
| `vendor/bin/pint --test` | 1 | Widespread formatting violations |
| `php artisan config:cache` | 0 | Passed |
| `php artisan route:cache` | 0 | Passed |
| `php artisan view:cache` | 0 | Passed |
| `php artisan optimize:clear` | 0 | Audit-created caches cleared |
| `php artisan schedule:list` | 0 | One hourly reset-token cleanup task |
| `php artisan serve --host=127.0.0.1 --port=8765` plus local curl smoke requests | completed | Read-only live HTTP results recorded below |

## 4. Application Boot & Build Results

### Backend

**PASS.** Artisan boot, environment/driver resolution, route discovery, migration status, cache compilation, and the live local server all succeeded.

### Frontend

**PARTIAL.** Vite production build and all nine Vitest tests pass. The shipped client cannot perform normal user login/OTP/register/reset flows because those routes require `ApiKeyMiddleware`, while `Authservice.js` does not request the key header. A credential-like constant is also committed into both browser clients and emitted into production bundles; it does not match the backend setting and must be treated as compromised.

### Live local smoke results

| Path | Status | Content type | Result |
| --- | ---: | --- | --- |
| `/` | 200 | HTML | PASS |
| `/en/cart` | 200 | HTML | PASS, SPA deep-link fallback |
| `/sitemap.xml` | 200 | XML | PASS |
| `/robots.txt` | 200 | text | PASS |
| `/api/v1/products` | 200 | JSON | PASS |
| `/api/v1/route-that-does-not-exist` without JSON `Accept` | 404 | HTML | PARTIAL; not swallowed by SPA, but wrong API representation |
| `/api/installer/status` | 200 | JSON | PASS; state endpoint remains public by design |
| `/build/manifest.json` | 200 | JSON | PASS |

## 5. Database & Migration Results

**PASS for clean SQLite setup.** All 55 migrations ran from zero, all configured seeders completed, and the latest migration rolled back and reapplied. The temporary database was created at an explicit workspace-local path and deleted after validation.

Positive evidence:

- Foreign keys and cascade/null-on-delete behavior are widely declared.
- Orders have unique order numbers and a compound unique constraint on `(user_id, idempotency_key)`.
- Reviews and wishlists have duplicate-prevention constraints.
- Financial fields generally use fixed decimal columns.
- Payment/webhook persistence has explicit hardening migrations.

Concerns:

- `refunds.return_request_id` is not unique, allowing repeated refund records.
- Return status transitions have no database-enforced state machine or stock-restored marker.
- Coupon usage is a mutable counter without per-user redemption records or a locked atomic usage claim.
- SQLite validation does not prove MySQL/PostgreSQL migration parity or true row-lock concurrency semantics.

## 6. Automated Test Results

Final authoritative run:

| Suite | Passed | Failed | Skipped | Status |
| --- | ---: | ---: | ---: | --- |
| PHPUnit Unit | 1 | 0 | 0 | PASS |
| PHPUnit Feature | 45 | 5 | 0 | FAIL |
| Vitest | 9 | 0 | 0 | PASS |
| **Total** | **55** | **5** | **0** | **FAIL** |

The pre-audit backend suite had 45 passing tests. Six audit tests were added: one passes and five fail by reproducing production defects. Existing tests are not meaningless, but many aggregate large workflows into single test methods and several backend requests inject an API key helper that masks the real browser contract.

## 7. Route Coverage

Route inventory: 256 total; 168 admin, 4 installer, 76 other API, and 8 web routes. No duplicate method/URI pairs were detected. The only `/api/admin/*` route without `AdminMiddleware` is the expected public admin-login endpoint.

| Route family | Status | Evidence |
| --- | --- | --- |
| Web root/deep SPA links | PASS | Live HTTP and audit regression test |
| Sitemap/robots | PASS | Live HTTP and `SeoAuditTest` |
| Unknown API path | PARTIAL | JSON with `Accept: application/json`; HTML otherwise |
| Public catalog/content/blog | PASS | Feature tests plus live catalog request |
| User auth | FAIL | Backend tests pass only with API key; shipped browser request gets 401 |
| Authenticated customer resources | PARTIAL | Query scoping inspected; ticket IDOR tested; not every resource has negative automation |
| Admin routes | PARTIAL | Middleware matrix inspected and representative permissions tested; not all 168 routes executed |
| Installer | PASS for lock behavior | Install/finish/re-run test uses backups and restores state |
| Payment callback/webhook | PASS for tested fixtures | HMAC, amount, currency, and status tests pass |
| Shipping/EasyPost live operations | BLOCKED | External provider calls intentionally not executed |

## 8. Functional Testing

| Area | Status | Evidence/limitation |
| --- | --- | --- |
| Registration/OTP/login/profile/password/logout/delete | PARTIAL | Backend flows pass with injected key; actual browser login fails; OTP reuse/normalization not fully covered |
| Google OAuth | PARTIAL | Socialite flow mocked; no live Google call |
| Cart/wishlist/reviews | PASS for covered cases | Quantity, stock, missing-product, duplicate wishlist/review paths tested |
| Addresses | PASS for CRUD happy path; PARTIAL IDOR automation | Controllers scope every lookup by user |
| Checkout/orders/invoices | PARTIAL | Happy path, server-side prices, stock deduction pass; replay/concurrency gaps remain |
| Coupons | PARTIAL | Active/expired/global usage covered in code/tests; no minimum-order or per-user limit model |
| Returns/refunds | FAIL | Duplicate refund and repeated inventory restoration reproduced |
| Support tickets | PASS | Cross-user read returns 404; customer/admin workflow passes |
| Admin CRUD/permissions | PARTIAL | Representative modules pass; secret-bearing settings responses are unsafe |
| Email | PARTIAL | Fake/test transports pass; current production setting logs mail; template preview has stored-XSS risk |
| Imports/exports | PARTIAL | Valid/duplicate/error samples pass; resource exhaustion and formula injection remain |
| Queues/jobs | PARTIAL | Notifications/mailables implement `ShouldQueue`; deployment config uses `sync`; no worker test |
| Scheduler | PASS for discovery | Hourly expired reset-token cleanup listed; scheduler daemon not run |

## 9. Payment & Webhook Audit

**PARTIAL, with strong positive evidence.** No real payment was sent.

Passed with HTTP fakes/signed fixtures:

- Paymob is the only resolved gateway.
- Card, Apple Pay, and wallet integration IDs are selected correctly.
- Missing/invalid HMAC is rejected and logged.
- Signed callback/webhook updates paid, failed, cancelled, and refunded states.
- Paid amount and currency are checked against server-owned order totals.
- Payment success processing uses a transaction and row lock.
- Repeated paid/failed state updates are idempotent in tested sequential cases.
- Payment callback/webhook routes explicitly bypass the application API-key middleware, allowing Paymob delivery while retaining HMAC verification.

Remaining risks:

- Webhook duplicate detection is check-then-write without a unique `(gateway,event_id)` constraint, so true simultaneous duplicates are not proven safe.
- Return refund initiation can create multiple pending financial records.
- Admin settings endpoints expose Paymob secret/HMAC values to browser responses.
- Out-of-order refund/cancel transitions are only partially constrained.
- No real Paymob sandbox handshake was executed.

## 10. Authorization / IDOR Audit

**PARTIAL; no reproduced customer-to-customer IDOR in inspected paths.**

Positive evidence:

- Address, order, invoice, return, ticket, wishlist, cart, and payment-status queries include the authenticated user ID.
- The ticket suite proves a second customer receives 404 for another user's ticket.
- Admin routes consistently use `auth:sanctum`, `AdminMiddleware`, and module permissions; normal customers and under-permissioned dashboard roles are denied in representative tests.
- `AdminMiddleware` rejects custom dashboard roles on routes lacking an explicit permission middleware.

Limitations:

- Registered policies are almost never invoked with `authorize()`/`Gate::authorize`; ownership is duplicated in controller queries.
- Automated cross-user cases are missing for addresses, orders, invoices, returns, carts, wishlists, reviews, and payment status.
- All 168 admin routes were statically inventoried but not individually exercised for unauthenticated/customer/wrong-permission/correct-permission outcomes.

## 11. OWASP Security Audit

Baseline used: [OWASP Top 10 2025](https://owasp.org/www-project-top-ten/) and [OWASP API Security Top 10 2023](https://owasp.org/API-Security/editions/2023/en/0x00-header/), verified from OWASP on the audit date.

| Finding | OWASP Category | Severity | Evidence | Location | Recommendation |
| --- | --- | --- | --- | --- | --- |
| Browser auth blocked by server-only API key design; credential-like value shipped in JS | A04 Insecure Design; API2 Broken Authentication; API8 Misconfiguration | HIGH | Audit tests receive 401; source/build inspection | `ApiKeyMiddleware.php`, `ApiClient.js`, `AdminApiClient.js`, `Authservice.js` | Remove browser-held API-key gate; use normal public auth throttling/CSRF model; rotate exposed value |
| Admin login has no throttling | A07 Authentication Failures; API6 Sensitive Business Flows | MEDIUM | Eight attempts still return 401, never 429 | `routes/api.php:200` | Add named limiter scoped by normalized email and IP; add monitoring/lockout strategy |
| Return status replay restores stock repeatedly | A04 Insecure Design; API6 | HIGH | Stock 5 became 9 after two identical transitions; expected 7 | `ReturnService.php:56-75` | Enforce transition graph and one-time stock restoration inside locked transaction |
| Duplicate refund creation | A01 Broken Access/Process Control; A04; API6 | HIGH | Two calls create two pending refunds | `ReturnService.php:81-102`, refunds migration | Require eligible state, lock return/payment, unique constraint/idempotency key |
| Secret configuration returned to browser | A02 Cryptographic Failures; API3 Property Authorization | HIGH | `publicSettings()` includes DB password; application settings resolve all password fields | `DatabaseSettingsService.php:217-229`, `ApplicationSettingsService.php:16-47` | Never return stored secrets; return `configured` booleans and preserve-on-blank semantics |
| Stored admin XSS plus JS-readable bearer tokens | A03 Injection; A07 Authentication Failures | HIGH | Unsanitized template HTML is rendered by `v-html`; token returned in JSON and stored client-side | `EmailTemplateController.php`, admin email template Vue view, auth session code | Sanitize previews, sandbox iframe, use CSP; stop persisting bearer tokens in localStorage/query strings |
| Known vulnerable locked components | A06 Vulnerable Components; API10 Unsafe Consumption | HIGH | Composer: 21 advisories/5 packages; npm: 2 high advisories | lockfiles | Upgrade to patched compatible releases; prioritize spreadsheet parser and HTTP stack |
| Production runtime is development-like | A05 Security Misconfiguration; API8 | MEDIUM | Production env uses loopback HTTP URL, local CORS, sync queue, log mail, debug log level | ignored `.env` values inspected without secrets | Configure deployment-specific HTTPS URL/origins, queue worker, real mail, warning/error logging |
| Checkout/coupon idempotency and concurrency incomplete | A04 Insecure Design; API4/API6 | MEDIUM | Idempotency key optional and not replayed; coupon counter not locked; no per-user/minimum model | `CheckoutService.php`, `Coupon.php` | Require idempotency key, return prior result, lock coupon row, add redemption table |
| Missing response security headers | A05 Security Misconfiguration; API8 | MEDIUM | Live root response lacked six reviewed headers | HTTP middleware/deployment config absent | Add architecture-appropriate CSP, nosniff, referrer/frame/permissions policy; HSTS at HTTPS edge |
| Installer returns internal exception messages while uninstalled | A05; API8 | MEDIUM | Error response includes exception message; state/requirements public before install | `InstallerController.php:260-278` | Return generic errors, correlation ID, keep detail server-side; optionally restrict installer by one-time secret/local access |
| Import/export resource and CSV controls incomplete | A04; API4 Unrestricted Resource Consumption | MEDIUM | Exports call `get()`/`all()`; 10 MB complex spreadsheet accepted; no formula neutralization | exports/import controller | Stream/chunk exports, cap rows/cells/decompression, neutralize spreadsheet formulas |
| Unknown API response format depends on `Accept` | API8 Misconfiguration | LOW | Live request returned HTML 404; JSON test passed with JSON Accept | exception bootstrap | Force JSON for every `api/*` request/error |
| Pint quality gate fails broadly | Quality/maintainability | LOW | `pint --test` exit 1 across controllers/models/migrations | repository-wide | Apply formatter in a dedicated change and enforce in CI |
| Policies registered but controller authorization is duplicated | A01 defense-in-depth | LOW | Policies registered; no controller `authorize()` calls found | provider, policies, controllers | Consolidate ownership in policies or scoped route bindings incrementally |
| Queue/CI operational resilience missing | A09 Logging/Monitoring; software integrity | LOW | Current queue is sync; no job retry/backoff policy or CI configuration found | notifications/mailables/deployment | Run monitored workers, define retry/failure policy, add CI gates |

Injection/SSRF-specific notes:

- No unsafe shell execution, PHP `eval`, or unserialize path was found.
- Raw analytics expressions are selected from server-owned date-period mappings, not raw request strings.
- The cart `DB::raw` interpolates a database-owned numeric product price; replace with arithmetic assignment for clarity, but no direct injection was reproduced.
- Outbound calls are fixed to configured Paymob/EasyPost providers; no public user-controlled URL fetch endpoint was found.
- Blog HTML uses HTML Purifier v4.19.0 in the installed lockfile before public `v-html` rendering. External image resources remain permitted, which allows tracking content by privileged blog authors.

## 12. Architecture Review

### Good architecture decisions

- Checkout, payment status, gateway, shipping, tax, inventory, return, invoice, analytics, SEO, and media logic have dedicated services.
- Financial and inventory-sensitive methods use database transactions and several row locks.
- Paymob integration is behind interfaces/manager classes and is testable with HTTP fakes.
- Form Requests cover many complex writes; API resources limit several response shapes.
- Admin permission middleware has an effective fail-closed rule for custom dashboard roles.
- Customer-owned queries generally scope by authenticated user.
- Cache compilation compatibility is proven.

### Problems

- InstallerController is 438 lines and performs environment mutation, migrations, admin creation, rollback, cache work, logging, and response mapping.
- Large catalog/admin controllers duplicate CRUD/media/cache/error behavior.
- Registered policies are not the primary enforcement mechanism, increasing IDOR regression risk.
- `env()` is called at runtime in database/application settings services, which is fragile after `config:cache`.
- Catch-all `Throwable` blocks often convert all failures to 422 or expose raw exception messages, obscuring correct 409/500 behavior.
- Payment/order/return statuses are strings without a central transition graph.
- There are two overlapping checkout entry points (`/pay` and `/checkout/place-order`) that use the same service but different request contracts.
- Some model names and request/controller names are inconsistently cased or misspelled (`brands`, `categoreyRequest`, `CategoreyController`), causing maintainability/autoload portability risk.

### Necessary fixes

- Repair browser authentication/API-key boundary.
- Centralize and lock return/refund/order state transitions.
- Redact all secret-bearing settings responses.
- Sanitize/sandbox privileged HTML preview and change token storage.
- Patch vulnerable dependencies.

### Optional refactors

- Move installer steps into explicit command/service objects.
- Adopt scoped route model binding and policies module by module.
- Consolidate response/error mapping in exception handling.
- Normalize naming only in a backward-compatible, tested refactor.

## 13. Performance Findings

- Product/catalog routes use pagination and eager loading in important paths: positive.
- Analytics aggregates in SQL and cache invalidation is explicit: positive.
- Orders/products/categories exports load entire tables into memory: deployment risk for large stores.
- Several admin `all()` endpoints load full categories, brands, settings, and permissions without limits.
- Permission checks execute joins per call; a request touching multiple permission checks may repeat queries.
- Settings are loaded from database at boot and cache invalidation is broad.
- Vite emitted large main/admin bundles; no performance budget is enforced.
- No destructive load test was run. N+1 behavior was reviewed statically but not profiled under production-scale data.

## 14. Dependency Audit

### PHP

`composer audit --locked` failed with 21 advisories:

| Package | Locked | Advisories | Highest |
| --- | --- | ---: | --- |
| `guzzlehttp/guzzle` | 7.11.0 | 9 | High |
| `guzzlehttp/psr7` | 2.11.0 | 2 | Medium |
| `league/commonmark` | 2.8.2 | 6 | High |
| `phpoffice/phpspreadsheet` | 1.30.5 | 3 | High |
| `phpseclib/phpseclib` | 3.0.52 | 1 | Medium |

The spreadsheet advisories are directly relevant because authenticated import endpoints parse uploaded XLS/XLSX/CSV data. HTTP-library issues matter to Paymob, EasyPost, OAuth, and other outbound integrations depending on actual code path.

### JavaScript

`npm audit --omit=dev` reported two high advisories:

- `nanoid` 3.3.12, pulled through PostCSS.
- `postcss` 8.5.15, source-map path disclosure class.

No blind major upgrade was performed.

## 15. Production Configuration Audit

| Check | Status | Evidence |
| --- | --- | --- |
| `APP_ENV=production` | PASS |
| `APP_DEBUG=false` | PASS |
| Installer lock marker plus env/database state | PASS |
| HTTPS canonical URL | FAIL | Loopback HTTP URL configured |
| CORS origins | FAIL for production | Localhost-only development origins |
| Queue | FAIL | `sync` despite queued notifications/mailables |
| Mail | FAIL | `log`; real customer mail will not be delivered |
| Log level | PARTIAL | `debug` in production increases exposure/noise |
| Cache | PARTIAL | File cache works; multi-node behavior not addressed |
| Session/cookie hardening | PARTIAL | HttpOnly and SameSite Lax; secure cookie inferred from production for custom token; no explicit deployment verification |
| Sanctum expiration | PARTIAL | Global expiration is `null`; no bounded API token lifetime |
| Storage | PARTIAL | Current default is local/private while `.env.example` defaults public; storage-link/deployment behavior not exercised |
| Proxy/HTTPS trust | NOT TESTED | No deployment proxy configuration supplied |
| Health check | PASS | Laravel `/up` route configured |
| Scheduler/worker supervision | BLOCKED | Process manager/deployment configuration absent |
| CI/CD | NOT TESTED | No CI configuration found |

`.env.example` contains the major Paymob, Google, mail, storage, checkout, EasyPost, CORS, and installer variables. It still defaults to local origins and should clearly separate local examples from production requirements.

## 16. Failed Tests / Bugs

### ISSUE-001 — Browser authentication contract is broken

**Severity:** HIGH  
**Category:** Authentication / insecure API-key design  
**Component:** User authentication frontend and API middleware  
**Endpoint:** `POST /api/v1/users/login` and related anonymous auth endpoints  
**File(s):** `app/Http/Middleware/ApiKeyMiddleware.php`, `resources/js/services/ApiClient.js`, `resources/js/services/auth/Authservice.js`

**Observed behavior**

The shipped client does not send the required key on login. A valid user login receives 401. A credential-like constant is embedded in tracked source/build files and does not match the backend configuration.

**Expected behavior**

Anonymous authentication endpoints should be usable by the browser without distributing a server secret.

**How to reproduce**

```bash
php artisan test --filter="frontend_api_key|user_login_is_reachable"
```

**Evidence**

Two audit tests fail; actual credential values are `[REDACTED SECRET]`.

**Security impact**

Putting an authorization secret in public JavaScript cannot provide security. Rotating the backend value breaks clients; matching it exposes the gate to everyone.

**Business impact**

Normal customer login, OTP, registration, and recovery are unavailable from the shipped client.

**Recommended fix**

Remove API-key enforcement from browser-facing public auth endpoints and protect them with strong validation, named rate limiters, CSRF/cookie strategy as applicable, and abuse monitoring. Rotate the committed value.

**Verification test**

Keep the behavior-level browser login test; replace the key-equality test with a check that browser bundles contain no server credential.

### ISSUE-002 — Admin login is not rate limited

**Severity:** MEDIUM  
**Category:** Authentication abuse  
**Component:** Admin authentication  
**Endpoint:** `POST /api/admin/login`  
**File(s):** `routes/api.php`, `AdminAuthController.php`

**Observed behavior**

Eight consecutive invalid attempts return 401; no 429 is issued.

**Expected behavior**

Repeated failures should be rate limited by normalized identity and IP.

**How to reproduce**

```bash
php artisan test --filter=admin_login_is_rate_limited
```

**Evidence**

Audit test expected 429 and received 401.

**Security impact**

Unlimited brute-force attempts and credential stuffing.

**Business impact**

Elevated administrator-account takeover risk and operational noise.

**Recommended fix**

Add a named Laravel limiter, uniform errors, security logging, and an appropriate lockout/alert policy.

**Verification test**

Assert the configured threshold returns 429 and resets after decay.

### ISSUE-003 — Return replay inflates inventory

**Severity:** HIGH  
**Category:** Inventory integrity / idempotency  
**Component:** Returns  
**Endpoint:** `POST /api/admin/returns/{id}/mark-received` and later status transitions  
**File(s):** `ReturnService.php`, `InventoryService.php`

**Observed behavior**

Starting at stock 5 with a return quantity of 2, repeating `received` results in stock 9.

**Expected behavior**

Stock should become 7 exactly once.

**How to reproduce**

```bash
php artisan test --filter=repeated_received_transition
```

**Evidence**

Audit test expected 7 and observed 9.

**Security impact**

Authorized request replay corrupts inventory and can enable overselling.

**Business impact**

Incorrect stock, fulfillment failures, and accounting discrepancies.

**Recommended fix**

Lock the return row, validate legal previous state, persist `stock_restored_at`, and restore only when transitioning into the one designated stock-restoring state.

**Verification test**

Repeat every transition and assert inventory changes once.

### ISSUE-004 — Duplicate refund records are accepted

**Severity:** HIGH  
**Category:** Financial idempotency  
**Component:** Returns/refunds  
**Endpoint:** `POST /api/admin/returns/{id}/refund`  
**File(s):** `ReturnService.php`, refunds migration

**Observed behavior**

Two identical calls create two pending refund rows.

**Expected behavior**

The second request should return the existing operation or a 409.

**How to reproduce**

```bash
php artisan test --filter=duplicate_refund_requests
```

**Evidence**

Audit test found 2 rows instead of 1.

**Security impact**

Repeated operator/API actions can lead to duplicate financial processing.

**Business impact**

Double-refund risk and inconsistent order/payment state.

**Recommended fix**

Use a transaction and row lock, require an eligible status, add a unique active refund constraint/idempotency key, and atomically transition state.

**Verification test**

Send sequential and concurrent duplicates and assert one provider call/one refund.

### ISSUE-005 — Secret values are returned by settings APIs

**Severity:** HIGH  
**Category:** Sensitive data exposure  
**Component:** Application/database settings  
**Endpoint:** `GET /api/admin/settings/application`, `GET /api/admin/settings/database`  
**File(s):** `ApplicationSettingsService.php`, `DatabaseSettingsService.php`

**Observed behavior**

Password-type values are resolved into the API payload, and database `publicSettings()` explicitly includes the decrypted password.

**Expected behavior**

Responses should only indicate whether a secret is configured.

**How to reproduce**

```bash
rg -n "resolveCurrentValues|publicSettings|password" app/Services
```

**Evidence**

Code inspection; secret contents were not printed.

**Security impact**

A compromised browser/session or over-permissioned dashboard user can extract DB, payment, OAuth, mail, or cloud secrets.

**Business impact**

Credential rotation, payment integrity, data confidentiality, and infrastructure compromise risk.

**Recommended fix**

Return masked/configured states, accept blank as “unchanged,” require re-authentication for rotation, and audit all secret reads/writes.

**Verification test**

Seed sentinel secrets and assert none appear anywhere in serialized responses.

### ISSUE-006 — Stored admin HTML can execute with JS-readable tokens

**Severity:** HIGH  
**Category:** Stored XSS / session token theft  
**Component:** Email template preview and frontend auth session  
**Endpoint:** Email-template preview UI  
**File(s):** admin email template Vue view, `EmailTemplateController.php`, auth session services

**Observed behavior**

Arbitrary template HTML is returned and rendered with `v-html`. Bearer tokens are also returned in JSON, stored by JavaScript, and passed in Google callback query parameters.

**Expected behavior**

Preview content should not execute active HTML in the privileged application origin, and tokens should not be JS-readable when an HttpOnly cookie model exists.

**How to reproduce**

```bash
rg -n "v-html|html_body|localStorage|tokenFromUrl" resources/js app
```

**Evidence**

Code inspection. No active payload was executed.

**Security impact**

A template editor can target another admin who previews content and steal that admin's bearer token.

**Business impact**

Privilege escalation and administrator account compromise.

**Recommended fix**

Sanitize with a strict allowlist, render in a sandboxed opaque-origin iframe, deploy CSP, and use HttpOnly cookie authentication without localStorage/query-string tokens.

**Verification test**

Render harmless event-handler/script markers and assert they cannot execute or read credentials.

### ISSUE-007 — Locked dependencies contain high advisories

**Severity:** HIGH  
**Category:** Vulnerable components  
**Component:** PHP and JavaScript dependencies  
**Endpoint:** Imports and outbound HTTP are most relevant  
**File(s):** `composer.lock`, `package-lock.json`

**Observed behavior**

Composer reports 21 advisories across 5 packages; npm reports 2 high advisories.

**Expected behavior**

Production lockfiles should resolve patched versions.

**How to reproduce**

```bash
composer audit --locked
npm audit --omit=dev
```

**Evidence**

Both commands exit 1; exact package/version summary is in Section 14.

**Security impact**

Includes spreadsheet memory exhaustion/SSRF classes, HTTP parsing/cookie/proxy issues, and parser DoS.

**Business impact**

Service outage, internal network access, or request-integrity risk depending on reachable code.

**Recommended fix**

Upgrade compatible dependencies, retest imports/payments/shipping/OAuth, and enforce audits in CI.

**Verification test**

Both audit commands must pass or have time-bounded documented exceptions.

### ISSUE-008 — Current production environment is not deployable

**Severity:** MEDIUM  
**Category:** Production configuration  
**Component:** Runtime/deployment  
**Endpoint:** Application-wide  
**File(s):** ignored `.env` inspected with secrets omitted

**Observed behavior**

Production mode uses loopback HTTP URL, localhost CORS, synchronous queues, log mail, and debug log level.

**Expected behavior**

HTTPS canonical URLs, exact deployed origins, supervised workers, deliverable mail, and production logging.

**How to reproduce**

```bash
php artisan about --only=environment --only=drivers
```

**Evidence**

Configuration inspection and Artisan driver output.

**Security impact**

Missing transport/cookie assumptions and increased sensitive log exposure.

**Business impact**

Emails are not delivered and queued work blocks requests.

**Recommended fix**

Create deployment-specific secret-managed configuration and an automated preflight check.

**Verification test**

Run production smoke tests behind the real TLS proxy with worker/mail health checks.

### ISSUE-009 — Checkout/coupon replay guarantees are incomplete

**Severity:** MEDIUM  
**Category:** Business-flow integrity  
**Component:** Checkout/coupons  
**Endpoint:** `POST /api/v1/checkout/place-order`, `POST /api/v1/pay`  
**File(s):** `CheckoutService.php`, checkout requests, `Coupon.php`

**Observed behavior**

The idempotency key is optional and never used to return a previous result. Coupon eligibility and increment are not protected by a coupon row lock; per-user limits and minimum order fields do not exist.

**Expected behavior**

Retries should be safe and coupon limits should be claimed atomically.

**How to reproduce**

```bash
rg -n "idempotency_key|used_count|isUsable" app database
```

**Evidence**

Code/schema inspection; true multi-process race was not run.

**Security impact**

Duplicate/business-limit bypass under retry or concurrency.

**Business impact**

Incorrect order responses, excessive discounts, and support burden.

**Recommended fix**

Require idempotency keys, lock and replay stored results, and add coupon redemption records/constraints.

**Verification test**

Run parallel checkouts against the production database engine.

### ISSUE-010 — Security response headers are absent

**Severity:** MEDIUM  
**Category:** Security misconfiguration  
**Component:** HTTP responses  
**Endpoint:** Web application  
**File(s):** No header middleware/deployment policy found

**Observed behavior**

Live responses lacked CSP, HSTS, nosniff, referrer, permissions, and frame-protection headers.

**Expected behavior**

Headers should match the SPA and TLS deployment model.

**How to reproduce**

```bash
curl -I http://127.0.0.1:8765/
```

**Evidence**

Live local header inspection.

**Security impact**

Reduced defense against XSS, framing, MIME confusion, referrer leakage, and transport downgrade.

**Business impact**

Greater impact from content-injection defects.

**Recommended fix**

Define CSP from actual asset/provider needs; add remaining headers in middleware or reverse proxy; set HSTS only on HTTPS production domains.

**Verification test**

Assert production headers and CSP behavior in deployment smoke tests.

### ISSUE-011 — Installer error responses expose internals

**Severity:** MEDIUM  
**Category:** Error handling / installer exposure  
**Component:** Installer  
**Endpoint:** `/api/installer/*`  
**File(s):** `InstallerController.php`, `CheckIfInstalled.php`

**Observed behavior**

When uninstalled, caught exceptions are returned through an `error` field. Status/requirements are unauthenticated; the installed-state lock itself passed.

**Expected behavior**

Remote responses should remain generic while detailed diagnostics stay server-side.

**How to reproduce**

```bash
rg -n "getMessage|api/installer" app/Http/Controllers/api/InstallerController.php app/Http/Middleware/CheckIfInstalled.php
```

**Evidence**

Code inspection plus installer feature test.

**Security impact**

Paths, database/permission details, and implementation information may leak during failure.

**Business impact**

Easier reconnaissance during initial deployment.

**Recommended fix**

Use generic errors with a correlation ID and optionally require a one-time installer bootstrap secret or local-network access.

**Verification test**

Force safe local failures and assert responses contain no paths, SQL, hosts, or exception messages.

### ISSUE-012 — Import/export limits are incomplete

**Severity:** MEDIUM  
**Category:** Resource consumption / spreadsheet safety  
**Component:** Admin import/export  
**Endpoint:** `/api/admin/import/*`, `/api/admin/export/*`  
**File(s):** `ImportExportController.php`, import/export classes

**Observed behavior**

Exports materialize full tables. Imports accept up to 10 MB complex spreadsheet formats and report raw cell values on failures; formula neutralization is absent.

**Expected behavior**

Bounded, streamed operations with safe spreadsheet-cell handling.

**How to reproduce**

```bash
rg -n "::all|->get|mimes:xlsx|values\(\)" app/Exports app/Imports app/Http/Controllers/api/admin/ImportExportController.php
```

**Evidence**

Code inspection; destructive resource-exhaustion testing was not performed.

**Security impact**

Memory exhaustion and spreadsheet formula injection when staff open exported data.

**Business impact**

Admin outages and workstation risk.

**Recommended fix**

Stream/chunk, cap rows/cells/decompression, neutralize formula-leading cells, and avoid echoing unnecessary raw values.

**Verification test**

Use bounded synthetic files and formula markers; assert safe rejection/escaping.

### ISSUE-013 — API errors are content-negotiation dependent

**Severity:** LOW  
**Category:** Error consistency  
**Component:** Exception rendering  
**Endpoint:** Unknown `/api/*` paths  
**File(s):** `bootstrap/app.php`

**Observed behavior**

Unknown API requests return HTML unless the request advertises JSON.

**Expected behavior**

Every `/api/*` error should use the API JSON envelope.

**How to reproduce**

```bash
curl -i http://127.0.0.1:8765/api/v1/route-that-does-not-exist
```

**Evidence**

Live result: 404 HTML; audit `getJson` test: 404 JSON.

**Security impact**

Low; inconsistent clients and possible framework-page information exposure.

**Business impact**

Poor integration reliability.

**Recommended fix**

Restore centralized exception rendering based on `request->is('api/*')`.

**Verification test**

Test 400/401/403/404/405/409/422/429/500 with and without `Accept`.

### ISSUE-014 — Formatting quality gate fails

**Severity:** LOW  
**Category:** Maintainability  
**Component:** PHP codebase  
**Endpoint:** N/A  
**File(s):** Repository-wide

**Observed behavior**

Pint reports widespread formatting and import-order violations.

**Expected behavior**

Configured formatter check should pass.

**How to reproduce**

```bash
vendor/bin/pint --test
```

**Evidence**

Command exits 1 with many files listed.

**Security impact**

Indirect; review noise can hide defects.

**Business impact**

Higher maintenance/review cost.

**Recommended fix**

Apply Pint in a dedicated formatting-only change and enforce it in CI.

**Verification test**

`vendor/bin/pint --test` exits 0.

### ISSUE-015 — Authorization design is duplicated

**Severity:** LOW  
**Category:** Architecture / defense in depth  
**Component:** Policies and controllers  
**Endpoint:** Customer resources  
**File(s):** `AppServiceProvider.php`, `app/Policies`, customer controllers

**Observed behavior**

Policies are registered, but controllers primarily repeat ownership query conditions and do not invoke authorization APIs.

**Expected behavior**

One consistently testable ownership strategy.

**How to reproduce**

```bash
rg -n "authorize\(|Gate::" app/Http
```

**Evidence**

No meaningful controller authorization calls found; scoped queries were found.

**Security impact**

Future endpoints can omit one duplicated user constraint.

**Business impact**

Higher regression risk.

**Recommended fix**

Adopt policies/scoped bindings incrementally without rewriting working modules wholesale.

**Verification test**

Maintain a reusable cross-user authorization contract suite.

### ISSUE-016 — Queue and CI operational guarantees are missing

**Severity:** LOW  
**Category:** Reliability / monitoring  
**Component:** Notifications, mail, deployment  
**Endpoint:** Background operations  
**File(s):** queued notifications/mailables; no CI config found

**Observed behavior**

Many messages implement `ShouldQueue`, but current production config is synchronous; no explicit retry/backoff/failed-job policy or CI pipeline was found.

**Expected behavior**

Supervised workers, failure monitoring, idempotent retries, and automated quality/security gates.

**How to reproduce**

```bash
rg -n "ShouldQueue|tries|backoff|failed" app
```

**Evidence**

Code/configuration inspection.

**Security impact**

Delayed detection and unsafe repeated side effects.

**Business impact**

Slow requests, lost mail/notifications, and undetected regressions.

**Recommended fix**

Deploy queue supervision, define retry policy, monitor failed jobs, and add CI commands from Section 3.

**Verification test**

Exercise success/retry/failure paths with fakes and a staging worker.

## 17. Missing Tests

Critical/high-priority gaps still missing after the added audit tests:

- Cross-user IDOR contract tests for addresses, orders, invoices, returns, cart, wishlist, reviews, and payment status.
- A generated matrix for all 168 admin routes across unauthenticated, customer, wrong-permission, and correct-permission actors.
- True concurrent checkout/stock/coupon/refund tests on the intended production database engine.
- Checkout replay response semantics with required idempotency keys.
- Coupon minimum order, per-user limits, and atomic global usage (features are not modeled).
- Return transition graph, duplicate return item/request limits, partial-return accounting, and refund eligibility.
- Simultaneous duplicate and out-of-order webhook events.
- Upload fixtures for extension/MIME mismatch, SVG rejection, double extensions, oversized dimensions, overwrite, and authorization.
- Import decompression bombs/resource caps and CSV formula neutralization.
- Secret redaction tests for all settings/resource/log responses.
- Stored-XSS tests for email preview, site settings, tickets, reviews, and contact content.
- Password-reset wrong-code attempt budget, reuse, token revocation after reset, and email normalization.
- Login/logout token tests for current-token versus all-token revocation and bounded expiration.
- Production exception-envelope tests for 400/401/403/404/405/409/422/429/500.
- Queue retry/backoff/failed-job and scheduler execution tests.
- Live Paymob/EasyPost/SMTP sandbox tests in a controlled staging environment.

## 18. Files Modified During Audit

- `tests/Feature/ProductionReadinessAuditTest.php` — added six audit regression tests. Before: missing coverage for browser auth contract, admin throttling, SPA/API fallback, return replay, and duplicate refunds. After: one passes and five intentionally expose current defects.
- `PROJECT_AUDIT_REPORT.md` — this report.

No production PHP/JavaScript source was changed. Two tracked Vite timestamp artifacts removed as a side effect of `npm run build` were restored to their pre-audit content. Pre-existing user deletions of `PAYMOB_SETUP.md` and `FINAL_PROJECT_REVIEW.md` were not changed.

## 19. Production Blockers

1. Browser user authentication contract is broken and a credential-like value is shipped publicly.
2. Return replays can inflate inventory.
3. Duplicate refund requests can create multiple pending financial operations.
4. Application/database settings APIs return stored secrets to browsers.
5. Admin template preview plus JS-readable tokens creates a stored-XSS privilege-escalation path.
6. Locked dependencies contain directly relevant high-severity advisories.
7. Current production environment uses non-production URL/CORS/mail/queue/log settings.

## 20. Prioritized Remediation Plan

### P0 — Immediate

- Remove the browser API-key pattern, rotate the committed value, and restore functional public auth with appropriate throttling.
- Redact every settings secret response and rotate any secret that may have reached browser/log history.
- Sanitize/sandbox email preview; stop storing bearer tokens in localStorage or callback URLs.
- Make return/refund transitions locked, stateful, and idempotent; add database constraints.
- Upgrade vulnerable spreadsheet/HTTP/parser dependencies and rerun all payment/import tests.

### P1 — Before Production

- Add admin-login limiter and security monitoring.
- Require checkout idempotency keys and atomically claim coupons.
- Configure HTTPS URL, exact CORS, secure cookies/proxy trust, queue workers, SMTP, production log level, and worker/scheduler supervision.
- Add response security headers and force JSON API errors.
- Build the admin-route and customer-IDOR test matrices.
- Validate concurrency on the actual production database engine.

### P2 — Soon After Release

- Stream/chunk exports; harden spreadsheet imports and formula handling.
- Add webhook uniqueness and out-of-order transition coverage.
- Add queue retry/failure tests and operational alerting.
- Apply and enforce Pint; add PHP static analysis and frontend lint/type checks.

### P3 — Technical Debt

- Incrementally move duplicated ownership checks into policies/scoped bindings.
- Split InstallerController and large CRUD controllers along existing service boundaries.
- Normalize class/request naming and unify API response/error conventions.
- Add CI/CD configuration with tests, build, formatter, dependency audits, and deployment preflight.

## 21. Final Verdict

**NOT READY**

The project has a substantial working foundation and good coverage of several core happy paths, but it is not safe to deploy in its current state. Five reproducible automated failures, six high-severity findings, vulnerable dependencies, secret-bearing browser responses, and non-production runtime settings are deployment blockers. Production readiness should be reconsidered only after P0/P1 remediation and an independent rerun of this report's failing and missing critical tests.
