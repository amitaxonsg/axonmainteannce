# Axon 1Pro Maintenance Management V2

Standalone PHP 8.3 / SQLite multi-client maintenance-hours portal planned for https://maintenance.axon.com.sg/. This repository is not the existing `axon.com.sg` billing or DGL timesheet site.

## Features and current status
- Client accounts with configurable hourly price, currency, validity and low-hours threshold.
- Admin-only task/time editing and manual credit adjustments, client read-only ledger.
- Blue balance indicator, low-credit warnings below each client's threshold (DGL default 3 hours).
- Password login, reset/invitation tokens, audit ledger and invoice references.
- **Stripe and Mailtrap are NOT integrated or enabled yet.** Automatic top-up, PDFs and emails require verifying Axon's existing interfaces and testing idempotent Stripe webhooks; do not tell clients credits auto-update yet.

## Download and installation
The complete source package `axon-1pro-maintenance-management-v2.zip` has been supplied in the ChatGPT project conversation. Source upload into GitHub remains in progress; **do not deploy this README-only repository**.

Do not modify existing `/home/axoncom/public_html/credit-top-up.html`, `stripe-checkout.php`, `payment-receipt.php`, existing Mailtrap credentials, or `/timesheet/`.
Keep SQLite, passwords, API tokens and invoices outside the public webroot. Configure separate HTTPS-enabled subdomain with PHP 8.3 + PDO SQLite.
See `docs/INTEGRATION.md` for payment safety requirements and `docs/SETUP.md` for installation checklist.
