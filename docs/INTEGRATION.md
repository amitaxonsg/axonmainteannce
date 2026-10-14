# Axon Stripe / Mailtrap integration contract — pending verification

## Existing untouched live files
- /home/axoncom/public_html/credit-top-up.html
- /home/axoncom/public_html/stripe-checkout.php
- /home/axoncom/public_html/payment-receipt.php

**These files are live. Read them without printing secrets. Do not change their behavior.**

## Safe SSH inspection (filename, function names and field names only)
```bash
BASE=/home/axoncom/public_html
for f in credit-top-up.html stripe-checkout.php payment-receipt.php; do
  echo "--- $f ---"
  test -f "$BASE/$f" || continue
  grep -nE 'function |fetch\(|form |name=|Stripe|payment_intent|checkout|webhook|invoice|mailtrap' "$BASE/$f" |
    sed -E 's/(sk_(live|test)_[A-Za-z0-9]+|whsec_[A-Za-z0-9]+|Bearer [A-Za-z0-9_-]+)/[REDACTED]/g' | head -70
done
```
Review output before sharing; source can contain embedded secrets not caught by a redactor. Prefer manual inspection of non-secret interface details.

## Payment sequence
1. Authenticated client POSTs desired top-up hours.
2. Server reads persisted client rate, currency and active account; calculates integer minor-unit amount; stores pending order with unique order ID.
3. Verified existing Axon checkout integration creates a Stripe Checkout Session carrying server-generated order ID. Client cannot set unit price or client ID.
4. Stripe webhook receives raw request, verifies signature against configured webhook secret, validates checkout payment status, exact paid amount/currency and order's client; inserts unique event ID and grants credits **atomically**. Retries must be idempotent.
5. Credit lot purchase timestamp is the confirmed paid time and expiry is per-client calendar month policy.
6. Reuse existing Axon invoice creation / receipt numbering and store provider invoice identifier, PDF reference and email delivery status. Never expose another client's PDF.
7. Notify both client and Axon via Mailtrap API; retries recorded separately from credit grant.

Do not automatically add hours after returning from Stripe success URL. Disable Stripe checkout button until the adapter is fully tested in test mode, including double-delivered webhooks, mismatched currency/amount, expired sessions and refunds.

## Mailtrap sequence
- Review the existing API sending endpoint, authenticated domain and sender formatting in read-only mode.
- Send branded HTML invite with single-use hashed token expiring after 24h; no plaintext permanent passwords.
- Notifications: account creation, password reset, hours below threshold (deduped), credits expiring, tasks, successful top-up, invoice.
- Persist delivery request ID, failures and retry status; prevent client enumeration.

## Existing invoice flow
The old receipt handler may send an email but does not necessarily expose a formal tax invoice API. Confirm PDF and invoice-number ownership before promising PDF download.
