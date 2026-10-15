# Deployment / operating reference

## Hosting
Use a NEW dedicated subdomain `maintenance.axon.com.sg`, PHP 8.3 with pdo_sqlite / sqlite3, TLS via cPanel AutoSSL, and a separate directory outside the live `axon.com.sg` document root. The web document root must be the application's `public/` directory. Do not point it at the entire repository.
The historical DGL portal stays at `https://axon.com.sg/timesheet/` until balances and history are reconciled.

## Config
Copy `config.example.php` to `config.php`. Generate a cryptographic secret using `openssl rand -hex 32`. Do not post it in chat or commit it. Private `var/` and config must be unreadable from the public web. Run `php bin/install.php admin@example.com` as the new subdomain cPanel account user only after the full source is deployed.

## Business settings
Each client has independently configurable currency, SGD or USD hourly rate, credit expiry in calendar months, balance alert threshold, customer contacts and active/suspended status. DGL reference settings: SGD 80/hour, validity 6 months, low hours threshold 3, initial purchase 10 hours with actual paid date.

## Operations
Administrator creates client, sends expiring password setup invite, logs tasks and hours, adjusts hours with audit reason, confirms manual purchase when received. Client sees their own transactions and invoices but cannot edit tasks or credits. Password resets are tokenized. Notify customer below threshold and when approaching expiry; dedupe repeat alerts.

## Payments and invoices
Checkout and webhooks MUST NOT go live until the existing Axon Stripe/Mailtrap/invoice implementations are inspected read-only and an adapter is written and tested. Payment success redirects do not grant credits. Stripe signature verification, amount, currency, client ID, event uniqueness and DB transaction must all succeed. Generate/send the actual existing Axon invoice PDF only after confirming its contract. Never hardcode SGD80 across accounts.

## Tests before launch
Verify auth and sessions, client isolation, role-based authorization on server, CSRF, credit expiry and earliest-expiry deduction, low-balance alert, ledger history, manual top-up, double webhook delivery, incorrect payment amount/currency, Mailtrap failures, backups and restore. No changes permitted to main Axon website or other cPanel accounts. Test SSL and main site separately.


---

# HANDOVER — 2026-10-15, Axon 1Pro Maintenance Management V2

## Actual deployed target (supersedes earlier proposed subdomain)
- **URL:** https://timesheet.axon.com.sg/
- **cPanel account:** `timesheetaxoncom`, UID 1163.
- **Document root:** `/home/timesheetaxoncom/public_html`.
- **Private working application:** `/home/timesheetaxoncom/axon-maintenance-upload/axon-1pro-maintenance-v2`.
- **Public bootstrap:** `/home/timesheetaxoncom/public_html/index.php` loads the private app's `public/index.php`.
- **Public webhook bootstrap:** `/home/timesheetaxoncom/public_html/webhook.php` loads private app's `public/webhook.php`.
- **SQLite:** `/home/timesheetaxoncom/axon-maintenance-upload/axon-1pro-maintenance-v2/var/maintenance.sqlite`; permissions observed 600; private app configuration permissions observed 600.
- **Admin email:** `rico.m@axon.com.sg`; password was entered locally at installation and is NOT recorded in GitHub.
- **Versions:** PHP 8.3, pdo_sqlite/sqlite3 available.
- **GitHub clone path:** `/home/timesheetaxoncom/axon-maintenance-v2` (repository currently lacks the complete PHP app; don't deploy a blind git pull over working private application).
- **Staged original ZIP:** `/home/timesheetaxoncom/axon-maintenance-upload/axon-1pro-maintenance-v2`.
- **Payment staging:** `/home/timesheetaxoncom/axon-payment-staging/axon-1pro-maintenance-v2`.
- **Isolated migration test:** `/home/timesheetaxoncom/axon-payment-test`.
- **Current deployed payment code:** private `src/payment.php`, `public/webhook.php`, and `bin/migrate-payments.php`; updated private `public/index.php`.

## Verified steps, from user-supplied SSH output
1. WordPress is not involved: independent PHP/SQLite portal.
2. Administrator installer completed. SQLite `PRAGMA integrity_check` returned `ok`, one admin present. PHP syntax checks passed.
3. Portal GET `/` returned HTTP **200**, login UI visible; administrator reported a successful dashboard login.
4. Attempts to fetch `/config.php`, `/var/maintenance.sqlite`, `/src/bootstrap.php`, `/bin/install.php` returned **404** (not a comprehensive security audit).
5. Existing live SQLite backup `/home/timesheetaxoncom/maintenance-backup-20261015-093218/maintenance.sqlite` integrity `ok` plus `application.tar.gz`.
6. Payment migration was applied twice on disposable test database successfully; schema had exactly one `payment_orders.updated_at` column and integrity `ok`.
7. Payment PHP deployed with flags disabled. Deployment backup `/home/timesheetaxoncom/payment-deploy-20261015-093613`.
8. Live payment schema migration completed, SQLite integrity `ok`, exactly one `updated_at` column, portal HTTP **200**. Pre-migration DB backup: `/home/timesheetaxoncom/payment-db-before-migration-20261015-093643.sqlite`.
9. Recorded table counts (last supplied): clients **1**, payment_orders **0**, payment_events **0**, credits **0**, invoices **0**. This is not proof that DGL's original V1 maintenance data is migrated.
10. Webhook public bootstrap created. GET `/webhook.php` returned **405** (method rejected); unsigned POST while `stripe_enabled=false` returned **503**; portal stayed **200**. This proves disabled path routing only, not signed webhook correctness or ModSecurity compatibility with real Stripe events.
11. Current flags (last observed): `'stripe_enabled' => false` and `'mailtrap_enabled' => false`. Keep both disabled until tested.

## Existing Axon billing inspected READ-ONLY (DO NOT EDIT)
- `/home/axoncom/public_html/credit-top-up.html`
- `/home/axoncom/public_html/stripe-checkout.php`
- `/home/axoncom/public_html/payment-receipt.php`

Observed: old checkout uses USD rate 100/hr or 80/hr for 5+ hours, not suitable as direct maintenance pricing. Receipt verifies Stripe checkout session paid status, uses a session-based sent record, creates a PDF using `make_pdf_invoice()`, and sends customer/admin messages using **Resend** via `resend_send()`. Env variable names included `STRIPE_SECRET_KEY`, `RESEND_API_KEY` and sender/reply settings. No Stripe signature/webhook handler found in the scanned old PHP files. This does not authorize copying credentials; separate secrets must remain outside public webroot and repository.

## Planned per-client rules
- Multi-tenant data isolation; only Axon admin may add/edit tasks and manual credits. Client logs in to see its own tasks, available/expired hours, transactions, invoice PDFs, and top-up links.
- Per-client configurable hourly rate, currency, credit validity and low-balance threshold. DGL example SGD 80 per hour, six months, warn below 3 hours; purchase 10 hours only when documented actual payment/date is verified.
- Balance appears blue, warning below threshold. Use earliest-expiring credits first, track negative debt and historical adjustments with audit reasons.
- Client invitation, password reset, changed-password email, login security, expiring tokens and branded HTML notifications.
- Stripe: server-side computed amount; pending order; Checkout session; signed webhook; exact amount/currency/client/session validation; transactional exactly-once hours credit; handle duplicates/refunds; must not rely on return page. ModSecurity previously blocked old Axon thank-you page; test genuine webhook/return with narrow host/path investigation only, no global exclusions.
- Invoice: reuse verified Axon PDF layout/numbering if appropriate, private storage, client-specific authorized download, mail delivery status and retry.
- Check whether Mailtrap is used at all: old receipt code uses Resend. Do not claim Mailtrap works without verifying credentials and sender setup.

## INCOMPLETE — DO NOT CLAIM LIVE
- Signed Stripe test-mode Checkout + webhook end-to-end validation.
- Payment failures / duplicates / mismatch / blocked return page / ModSecurity testing.
- Actual automatic credit grant from paid sessions.
- Invoice PDF generation, client invoice download and outgoing email integration.
- Client invitation, password reset and notifications end-to-end.
- Full multi-client role/isolation testing, balance expiry reconciliation and original DGL V1 migration.
- Committing complete PHP source to GitHub; current repository is documentation-only or partial. **Do not overwrite working deployed app with GitHub clone.**

## Strict hosting boundaries
NO service restarts, global PHP/Apache/Redis/MySQL changes, server-level ModSecurity changes, permission changes outside this user, alterations to `axon.com.sg` or `/home/axoncom`, or changes to other ~200 cPanel accounts. Work only within `/home/timesheetaxoncom` for application deployment. Existing Axon files only for approved read-only interface inspection.

## Safe resume checklist
1. Read this handover and inspect live code/config names without exposing secrets.
2. Verify private application & SQLite backup, confirm portal HTTP 200 and admin dashboard login.
3. Finish secure test-mode Stripe integration and run signed webhook regression tests; keep live payments disabled.
4. Build Resend/Mailtrap adapter and invoice PDF/downloads; test per-client access.
5. Bring full tested source into GitHub as a reviewed commit; do not auto-deploy documentation-only main branch.
6. After end-to-end acceptance, enable payments deliberately within dedicated account and monitor logs.

