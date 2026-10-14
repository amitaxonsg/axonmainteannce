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
