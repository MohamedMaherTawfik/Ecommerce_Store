# Post-Remediation Audit

Audit date: 2026-08-29  
Repository: `C:\xampp\htdocs\Ecommerce_Store`  
Audit type: independent post-remediation verification

## Executive Summary

* Previous verdict: **NOT READY**
* Current verdict: **CRITICAL SECURITY RISK**
* Backend tests: **65 passed / 3 failed** (aggregate lockfile-aligned result; 507 assertions in the full run before the third focused audit regression was added)
* Frontend tests: **9 passed / 0 failed**
* Critical: **1**
* High: **2**
* Medium: **3**
* Low: **2**
* Production blockers: **5**

The remediation fixed all five previously failing regression tests and substantially improved browser authentication, throttling, return replay protection, same-return refund idempotency, secret redaction, stored-XSS handling, dependency locks, headers, error envelopes, imports, queues, and CI. Those improvements are real and supported by tests.

The project is nevertheless unsafe to ship. The installer feature test mutates the real configured SQLite database despite PHPUnit declaring an in-memory database. A before/after SHA-256 comparison changed after running that single test, and a read-only database query confirmed that it persisted an active administrator whose password matches the known value in the test fixture. The record was not removed during this audit because the brief prohibits silently changing real data.

Three new audit regressions also fail: Google OAuth reactivates a disabled account, two legitimate return requests can create refunds totaling twice the order value, and checkout exposes raw internal exception text while incorrectly returning 422.

## Git / Merge Integrity

* Initial worktree: clean, branch `main`, aligned with `origin/main`.
* Latest commit: `793e84a fixing a lot of` (227 files changed).
* `git diff --check`: passed before audit changes and after the audit test was added.
* Duplicate method + URI routes: none.
* Unresolved conflict markers: none. Matches containing only `====` were decorative comments in `resources/js/views/home/products/Index.vue`.
* `PROJECT_AUDIT_REPORT.md`: found and read completely.
* `PROJECT_REMEDIATION_REPORT.md`: not present in the worktree or history inspected.
* Audit-created files: `tests/Feature/PostRemediationVerificationAuditTest.php` and this report.
* No production source was modified.

The recent commit consistently removed the browser API-key middleware and client key, added hardening migrations, security headers, safe settings representations, queue policy, deployment preflight, CI, and focused tests. No duplicated route or lockfile conflict was found.

## Build and Boot

| Check | Result | Evidence |
| --- | --- | --- |
| PHP | PASS | 8.3.30 |
| Composer | PASS | 2.9.7 |
| Laravel | PASS | 12.61.1 |
| Node | PASS | 24.13.0 |
| npm | PASS | 11.6.2 |
| `composer validate --no-check-publish` | PASS | Manifest/lock valid |
| `php artisan about` | PASS | Application boots; production mode, debug off |
| `php artisan route:list` | PASS | 256 routes |
| `config:cache` | PASS | Exit 0 |
| `route:cache` | PASS | Exit 0 |
| `view:cache` | PASS | Exit 0 |
| `event:cache` | PASS | Exit 0 |

Runtime identification: Vue 3/Pinia/Vue Router/Vite frontend; Laravel Sanctum personal access tokens transported in a custom HttpOnly cookie; SQLite locally; file cache/session; synchronous queue; log mail transport. `.env.example` targets MySQL and production-grade database-backed state.

Route inventory found 168 `/api/admin/*` routes. Excluding the intentional login endpoint, zero admin routes lacked both Sanctum authentication and `AdminMiddleware`. Paymob callback/webhook and Google callback are intentionally public; Paymob paths use HMAC verification.

## Database and Migrations

### Disposable migration verification

A dedicated file at `storage/framework/testing/post_remediation_audit.sqlite` was explicitly created for this check and removed afterward.

* `migrate:fresh --seed --force`: PASS; all 57 migrations ran from zero and all configured seeders completed.
* `migrate:rollback --step=1 --force`: PASS; coupon redemptions migration rolled back.
* `migrate --force`: PASS; migration reapplied.
* Final disposable `migrate:status`: every migration ran.
* Return hardening adds `stock_restored_at` and a unique refund idempotency key.
* Orders retain unique `(user_id, idempotency_key)`.
* Webhook logs retain unique `(gateway, event_id)`.
* Coupon redemptions have unique order constraints and coupon/user indexing.

This proves clean SQLite setup, seeding, rollback, foreign-key/index creation, and sequential behavior. It does **not** prove MySQL/PostgreSQL syntax parity, deadlock behavior, or real row-lock concurrency. Inventory/coupon/checkout concurrency remains **PARTIAL** because only SQLite and sequential tests were available.

### Critical isolation failure

The real configured database initially showed the two new remediation migrations as pending and later showed them in batch 4. A controlled isolation experiment then proved real database mutation:

1. Hash and timestamp `database/database.sqlite`.
2. Run only `php artisan test tests\Feature\InstallerFlowTest.php`.
3. Hash and timestamp the same file again.
4. Both changed.
5. A temporary read-only PDO query confirmed the real database contains the installer test email.
6. Read-only checks confirmed the record is active, has role `admin`, and its stored hash matches `[REDACTED KNOWN TEST PASSWORD]` from the committed fixture.

`phpunit.xml` does declare `DB_DATABASE=:memory:`. The likely escape occurs during installer environment/config cache rebuilding, but the exact call boundary still needs tracing. `InstallerFlowTest` backs up `.env` and `storage/installed.json`; it does not back up or restore the configured database.

**Do not run the installer test or full suite against a machine that can reach sensitive data until this is fixed.** The persisted fixture account remains in the local database and requires owner-approved removal/rotation.

## Backend Tests

The first post-remediation run passed all 65 existing tests with 501 assertions. After adding independent regressions and synchronizing `vendor/` to `composer.lock`, the authoritative full run produced 65 passes and two failures (507 assertions). A third focused regression was then added and run against the same locked dependency set; it also failed. The full suite was not repeated after proving `InstallerFlowTest` contaminates the real database because doing so would knowingly repeat a destructive side effect.

Final aggregate:

* Passed: 65
* Failed: 3
* Skipped: 0

Failed audit contracts:

1. Disabled Google-linked accounts must remain disabled and receive no token.
2. Aggregate refunds for distinct returns must not exceed the paid order total.
3. Internal checkout exceptions must be sanitized and return a server-error status.

All previously added remediation tests pass, including the original five failures.

## Frontend Tests

* `npm test`: PASS — 4 files, 9 tests.
* `npm run build`: PASS — 387 modules transformed after `npm ci`.
* No lint or typecheck script exists in `package.json`; none was invented.
* Source and production bundle scans found zero privileged API-key header/constants, zero hardcoded bearer values, and zero authentication tokens in query strings.
* UI role/profile state remains in local/session storage, but the bearer token is kept in an HttpOnly cookie and removed from browser JavaScript.

## Previous Findings Verification

| Previous Issue | Old Severity | Current Status | Evidence |
| --- | ---: | --- | --- |
| Browser API-key/authentication contract | High | FIXED | Browser login succeeds; middleware/key removed; source and bundle scans clean |
| Admin login not throttled | Medium | FIXED | Automated normalized-identity test and live sixth-attempt 429 |
| Return `received` replay restores stock twice | High | FIXED | Stock stays 7 after replay; base and variant tests pass |
| Duplicate refund for the same return | High | FIXED | Same-return replay creates one idempotent refund; unique idempotency key exists |
| Settings APIs expose secrets | High | FIXED | Sentinel API tests return only `configured` state |
| Stored admin XSS plus JS-readable token | High | FIXED | Sanitizer removes scripts/handlers/`javascript:`; sandboxed iframe; no JS token |
| Vulnerable locked dependencies | High | FIXED | Locked and installed Composer audits pass after lock install; npm audit passes |
| Development-like production runtime | Medium | NOT FIXED | Production preflight has 12 failures in current environment |
| Checkout/coupon replay and concurrency | Medium | PARTIALLY FIXED | Required scoped key, replay, locks, redemption schema pass; production-engine concurrency unproven |
| Missing security headers | Medium | FIXED | Live CSP, nosniff, frame, referrer, permissions headers; conditional HSTS in code |
| Installer exposes internal errors | Medium | FIXED | Generic request-ID errors and installed lock; new critical test isolation regression exists separately |
| Import/export limits and formula handling | Medium | FIXED | 5 MB file cap, XLSX expansion caps, chunk/row/column limits, formula rejection/neutralization, query exports |
| API error format depends on `Accept` | Low | FIXED | Live unknown API route is JSON with and without `Accept` |
| Pint fails broadly | Low | PARTIALLY FIXED | Now only `app/Helpers/EnvHelper.php` line ending fails |
| Duplicated controller authorization | Low | PARTIALLY FIXED | Cross-user matrix passes and policies exist; scoped checks remain duplicated |
| Queue and CI guarantees absent | Low | PARTIALLY FIXED | CI, retry trait, runbook added; local production queue still `sync` |

All five previously failed regression tests are fixed. Six of the seven prior production blockers are fully remediated; deployment configuration remains outstanding. The new findings below prevent release independently.

## Authentication

Positive evidence:

* Registration/OTP/login/profile/password/logout/delete happy paths pass.
* Registration OTP is generated with `random_int`, expires, and is consumed on verification.
* Browser tokens are absent from JSON and placed in an HttpOnly cookie.
* Login and registration normalize email.
* Logout/password reset/password change revoke personal access tokens.
* Forgot-password responses do not enumerate existing accounts.
* Expired reset codes are rejected and deleted.

Negative evidence:

* Google callback calls `updateOrCreate` with `is_active=true`, `role=user`, and a new password before testing `is_active`. A disabled admin fixture is reactivated and redirected to success.
* Google OAuth uses Socialite `stateless()`, so standard OAuth state protection is absent.
* Password-reset OTP uses `rand()` rather than `random_int()` and reset throttling is IP-only; there is no account-scoped failed-attempt budget. This is weaker than the registration OTP design.

## Authorization / IDOR

Cross-user automated cases pass for address read/update/delete, cart update/delete, order read/status, return read/cancel/create, and support tickets. Direct ownership scoping was also inspected for invoices, wishlist, checkout addresses, and payment status. Representative admin tests cover unauthenticated, customer, under-permissioned staff, and full admin actors across orders, returns, users, products, templates, and both settings modules.

No customer-to-customer IDOR or admin BFLA was reproduced. Coverage is representative rather than an execution of every one of the 168 admin routes. Policies exist but controllers still primarily enforce ownership through repeated scoped queries.

## Admin Security

* Admin login threshold: 5/minute by SHA-256 of normalized email and IP.
* Automated wrong attempts return 401 then 429; a successful login works after decay.
* Live isolated identity attempts returned 429 on attempts 6 and 7.
* Missing account and wrong password use the same `Invalid credentials` response.
* All protected admin routes inventoried have Sanctum and admin middleware; module permissions are present for sensitive modules.
* Critical exception: the installer test persisted a known active admin fixture in the configured database.

## Checkout / Inventory

* Server owns product prices, totals, tax, shipping, and currency.
* Idempotency key is required and unique per user.
* Sequential replay returns the prior order/payment and produces one order, payment, stock deduction, coupon increment, redemption, and provider initialization.
* Different users are database-scoped by `(user_id, idempotency_key)`.
* Product/variant/stock and coupon rows use `lockForUpdate`; deductions occur in a transaction.
* Coupon active, expiry, and global usage checks exist. Minimum-order/per-user limits are not modeled, so they are not claimed.

Inventory oversell resistance is sound by inspection but **PARTIAL** in evidence because SQLite does not provide production row-lock/concurrency parity. A real MySQL/PostgreSQL parallel test is still required.

## Payments / Webhooks

No real payment was sent. Provider calls were faked.

Passed evidence includes Paymob-only gateway resolution, card/wallet/Apple Pay integration selection, server-owned amount/currency validation, paid/failed/cancelled/refunded transitions, invalid/missing signature rejection, duplicate signed webhook acknowledgement, and database uniqueness on `(gateway,event_id)`.

Out-of-order state protection is present for several transitions. True simultaneous duplicate delivery was not tested on the intended production database. Checkout currently holds its database transaction through payment initialization, which increases lock duration and should be load-tested.

## Returns / Refunds

The transition graph and one-time inventory restoration are fixed. Same-return refund replay is idempotent and database-protected.

The broader financial invariant is not fixed: `ReturnService::create` does not subtract quantities already returned for the same order item, and `refund` validates each refund only against the full order total. Two customer-created returns for the same purchased item were approved/received and each accepted a 100 refund request against a 100 order, leaving 200 in pending refunds. No provider call was made.

`PaymentStatusService::markRefunded` also updates every pending Paymob refund on an order when one refund webhook arrives, which can incorrectly settle unrelated return requests.

## Secret Handling

Sentinel tests cover database, OAuth, payment, and site-setting secret-like values. API responses expose only configured booleans; generic site settings reject secret-like keys. `.env` is ignored and not tracked. Source/build scans found no privileged browser API key or bearer secret.

Webhook payload logging recursively redacts credential, token, signature, card, email, phone, and address-like keys. No sentinel secret was observed in tested API responses. Full log-sink redaction under every settings failure path was not exhaustively automated.

## XSS / Frontend Security

Email templates are sanitized on storage and preview, variable content is escaped, and the admin preview uses a sandboxed `srcdoc` iframe without `v-html`. Harmless script, handler, and `javascript:` markers are removed by automated tests.

Public blog content still uses `v-html`, but it is passed through `BlogContentService`/HTML Purifier before persistence. CSP is present with `object-src 'none'` and `frame-ancestors 'none'`. No stored-XSS execution was reproduced.

## OWASP Findings

| Finding | OWASP area | Severity | Status |
| --- | --- | ---: | --- |
| Installer test persists a known privileged fixture into the real database | A01 Broken Access Control; A05 Misconfiguration; A08 Integrity Failures | Critical | CONFIRMED |
| Google OAuth reactivates disabled accounts and overwrites security fields | A07 Authentication Failures; A04 Insecure Design | High | CONFIRMED |
| Aggregate return/refund value can exceed paid amount | A04 Insecure Design; API6 Sensitive Business Flows | High | CONFIRMED |
| Checkout returns raw internal exception text as 422 | A05 Security Misconfiguration; API8 Misconfiguration | Medium | CONFIRMED |
| Password-reset OTP uses non-CSPRNG and IP-only attempt limiting | A07 Authentication Failures; API6 | Medium | CONFIRMED BY INSPECTION |
| Current deployment configuration fails closed preflight | A05 Security Misconfiguration | Medium | CONFIRMED |
| One production file fails Pint | Maintainability | Low | CONFIRMED |
| Ownership enforcement remains duplicated across controllers | A01 defense in depth | Low | CONFIRMED BY INSPECTION |

No direct SQL injection, unsafe shell execution, PHP deserialization, arbitrary URL-fetch endpoint, privileged browser key, or customer IDOR was reproduced.

## Dependency Audit

Initial generated dependency directories were stale relative to their lockfiles:

* `composer install --dry-run`: 21 updates pending.
* Installed Composer audit before synchronization: 21 advisories across 5 packages, including high advisories in Guzzle, CommonMark, and PhpSpreadsheet.
* `npm ci --dry-run`: 3 packages to add and 9 to change.

The ignored/generated dependencies were synchronized with `composer install --prefer-dist` and `npm ci`, then all checks were rerun:

* `composer audit --locked`: PASS, zero advisories.
* `composer audit` against installed packages: PASS, zero advisories.
* `npm audit --omit=dev`: PASS, zero vulnerabilities.
* Backend/frontend/build behavior remained unchanged except for the same audit failures.

Deployments must install from lockfiles; copying a stale `vendor/` or `node_modules/` tree would reintroduce vulnerable packages.

## Production Configuration

The source provides `.env.example`, `PRODUCTION_DEPLOYMENT.md`, CI, and a fail-closed `app:production-preflight` command. The current environment failed 12 preflight checks:

* HTTPS application/frontend URLs
* asynchronous queue
* secure session cookie
* deliverable mail transport
* exact HTTPS CORS origins
* explicit production Sanctum domains
* Paymob public/secret/HMAC/card configuration
* HTTPS Paymob callback and webhook URLs

This is deployment configuration, not evidence that secrets should be committed. HSTS is intentionally conditional on production HTTPS and was correctly absent on local HTTP. Queue workers, scheduler, TLS/proxy trust, real SMTP, provider sandboxes, backups, storage, and monitoring still require deployment validation.

## Architecture Review

Positive changes include service boundaries for checkout/payment/returns, transactions and row locks, unique idempotency constraints, safe settings resources, centralized HTML sanitization, security-header middleware, JSON exception rendering, CI, retry policy, and a deployment runbook.

Remaining architecture concerns:

* Installer code performs environment mutation, configuration caching, migrations, admin creation, and rollback in one controller/service workflow; this defeated test database isolation.
* OAuth linking mutates identity, authorization, activation, and password fields together.
* Return eligibility, quantities, refund value, and provider settlement do not share one locked aggregate invariant.
* Checkout controllers catch broad exceptions and expose messages with the wrong status.
* Ownership remains duplicated in queries despite registered policies.
* Payment provider initialization occurs inside the checkout database transaction.

No runtime `env()` calls were found under `app/`; configuration cache compatibility passes.

## Remaining Issues

### ISSUE-001

Severity: **CRITICAL**

Component: Installer / automated test isolation / database

Observed: Running only `InstallerFlowTest` changes the real configured database and persists an active admin account whose password matches the known committed test fixture.

Expected: Tests must be physically incapable of reaching or mutating any configured non-test database. No test credential may survive a test.

Evidence: Before/after SQLite SHA-256 and modification time both changed; read-only PDO checks confirmed role, active state, and password match. `phpunit.xml` says `:memory:`, while the test restores only `.env` and installer marker state.

Reproduction: Hash `database/database.sqlite`; run only the installer feature test; hash again; query the fixture email without printing the hash. **Do not reproduce on a sensitive environment.**

Recommendation: Immediately review and remove/rotate the fixture account under owner control. Refactor installer testing to an explicit randomly named disposable database and fail if its resolved path is outside a test-only directory. Prevent installer config-cache/environment rebuilds from discarding test overrides. Add before/after protection asserting the configured real database is untouched.

Production blocker: **YES**

### ISSUE-002

Severity: **HIGH**

Component: Google OAuth

Observed: Callback `updateOrCreate` sets `is_active=true`, `role=user`, and a new password before checking activation. A disabled admin becomes active and receives a browser token.

Expected: Existing account role/password/activation must be preserved; disabled accounts must be rejected before token issuance.

Evidence: `PostRemediationVerificationAuditTest::test_google_oauth_cannot_reactivate_a_disabled_account` redirects to success instead of error.

Reproduction: Mock a verified Google identity whose email matches an inactive user and call the callback.

Recommendation: Load existing users without mutating security fields, reject inactive accounts first, link only approved OAuth identity fields, preserve role/password, and restore stateful OAuth state protection.

Production blocker: **YES**

### ISSUE-003

Severity: **HIGH**

Component: Returns/refunds

Observed: The same purchased quantity can be submitted in distinct returns, and each refund is allowed up to the whole order total. A 100 order accepted two pending refunds totaling 200.

Expected: Cumulative returned quantity must not exceed purchased quantity and cumulative pending/processed refund value must not exceed captured payment or eligible returned-item value.

Evidence: `PostRemediationVerificationAuditTest::test_distinct_returns_cannot_request_refunds_above_the_order_total` fails with actual aggregate 200.

Reproduction: Create two returns for the same order item, receive both, request full-order refund for each.

Recommendation: Lock the order, payment, order items, returns, and refunds; compute remaining refundable quantity/value; reject overlaps; add database constraints where practical; correlate provider refund events to one refund record.

Production blocker: **YES**

### ISSUE-004

Severity: **MEDIUM**

Component: Checkout API error handling

Observed: An internal sentinel exception is returned verbatim and mapped to 422 by `CheckoutController`; `PaymentController` has the same raw-message pattern.

Expected: Unexpected failures return a generic 500 envelope and correlation ID; only validation/business exceptions return safe 4xx details.

Evidence: `PostRemediationVerificationAuditTest::test_checkout_internal_failures_use_a_safe_server_error_contract` sees the sentinel in JSON.

Reproduction: Bind a checkout service fake that throws an internal runtime exception, then submit a valid checkout request.

Recommendation: Catch specific validation/domain exceptions; let unexpected exceptions reach centralized sanitized rendering or return a generic 500 after logging a request ID.

Production blocker: **YES**

### ISSUE-005

Severity: **MEDIUM**

Component: Password reset

Observed: Reset OTP uses `rand()` and the five-per-minute limiter is IP-scoped rather than normalized email plus IP; no account attempt counter exists.

Expected: CSPRNG codes and bounded account/IP verification attempts with safe invalidation and monitoring.

Evidence: Code inspection of `AuthController::forgotPassword`, reset route middleware, and contrast with registration's `random_int`.

Reproduction: Inspect reset generation and limiter definitions.

Recommendation: Use `random_int`, hash as currently done, add normalized identity/IP limiting and a per-code failed-attempt budget.

Production blocker: **NO** if fixed before enabling password reset publicly; otherwise treat as release-required.

### ISSUE-006

Severity: **MEDIUM**

Component: Deployment configuration

Observed: Production preflight fails 12 required controls.

Expected: Preflight passes behind the real HTTPS proxy with queues, mail, exact origins/domains, and provider secrets supplied by secret management.

Evidence: `php artisan app:production-preflight` exit 1.

Reproduction: Run preflight after `config:cache` in the current environment.

Recommendation: Complete `PRODUCTION_DEPLOYMENT.md`; do not commit secrets.

Production blocker: **YES**

### ISSUE-007

Severity: **LOW**

Component: Formatting/CI

Observed: Pint reports only `app/Helpers/EnvHelper.php` for line endings.

Expected: `vendor/bin/pint --test` exits 0; CI currently gates on it.

Evidence: Final Pint run.

Reproduction: `vendor/bin/pint --test`.

Recommendation: Normalize the file in a formatting-only change.

Production blocker: **NO**

### ISSUE-008

Severity: **LOW**

Component: Authorization architecture

Observed: Policies are registered and negative tests pass, but many controllers repeat ownership predicates instead of invoking policies/scoped binding consistently.

Expected: One auditable authorization strategy with reusable negative contracts.

Evidence: Code inspection and authorization matrix.

Reproduction: Search controller ownership checks and `authorize(`/`Gate::` usage.

Recommendation: Consolidate incrementally without replacing already-correct scoped queries until parity tests exist.

Production blocker: **NO**

## Commands Executed

Only substantive commands actually executed are listed:

```text
git status
git log --oneline -15
git diff
git diff --check
git show --stat --oneline HEAD
rg -n --hidden ... conflict/API-key/token/XSS/env/error patterns
php --version
composer --version
php artisan --version
node --version
npm --version
composer validate --no-check-publish
php artisan about
php artisan route:list --json
php artisan migrate:status
php artisan migrate:fresh --seed --force (dedicated SQLite environment)
php artisan migrate:rollback --step=1 --force (dedicated SQLite environment)
php artisan migrate --force (dedicated SQLite environment)
php artisan test
php artisan test tests\Feature\PostRemediationVerificationAuditTest.php
php artisan test tests\Feature\InstallerFlowTest.php
npm test
npm run build
composer audit --locked
composer audit
npm audit --omit=dev
vendor\bin\pint --test
vendor\bin\pint tests\Feature\PostRemediationVerificationAuditTest.php
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan app:production-preflight
php artisan schedule:list
php artisan optimize:clear
php artisan serve --host=127.0.0.1 --port=8766
curl.exe smoke/error/header/admin-throttle requests against localhost
composer install --dry-run --no-interaction
composer install --no-interaction --prefer-dist
npm ci --dry-run
npm ci
npm ls --depth=0
Get-FileHash/Get-Item before and after InstallerFlowTest
php temporary read-only PDO verification script
```

## Final Results

```text
Backend tests: FAIL — 65 passed / 3 failed
Frontend tests: PASS — 9 passed / 0 failed
Build: PASS
Migrations: PASS on dedicated SQLite; production-engine concurrency/parity PARTIAL
Pint: FAIL — app/Helpers/EnvHelper.php line ending
Composer audit: PASS — locked and installed, zero advisories after lock install
npm audit: PASS — zero vulnerabilities
Config cache: PASS
Route cache: PASS
```

## Final Production Blockers

1. Installer test contaminates the real database with a known active administrator fixture; the local record remains.
2. Google OAuth reactivates disabled users and overwrites security fields.
3. Multiple returns can request refunds exceeding the paid order total.
4. Checkout exposes raw internal exceptions and maps unexpected failures to 422.
5. Current deployment configuration fails the production preflight.

## Final Verdict

**CRITICAL SECURITY RISK**

The project cannot ship. The most severe confirmed defect is a test-isolation escape that has already inserted a known-credential active administrator into the configured database. Two additional High-severity authentication and financial-integrity defects and an unsafe checkout error path remain. These are source/runtime blockers, not merely missing deployment credentials. Production readiness must be reassessed only after containment, remediation, safe database cleanup, and a rerun using a physically isolated disposable database plus the intended production database engine for concurrency testing.
