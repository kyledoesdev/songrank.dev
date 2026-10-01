# Testing Reference

Billing tests in this application **never call Stripe.** They bind a fake `StripeClient` into the container and feed synthetic payloads to the actions. There are no `pm_card_*` tokens, no test-mode API keys in CI, and no network access in the suite.

Everything lives in `tests/Feature/Billing/`:

| File | Covers |
| --- | --- |
| `ProCheckoutTest.php` | pending licence, session payload, redirect |
| `StripeWebhookTest.php` | fulfillment, idempotency, refunds, disputes, signatures |
| `ProEntitlementTest.php` | `is_pro` projection and access |
| `ProAccessTest.php` | route and UI gating |
| `BillingPageTest.php` | the `/billing` Livewire page |
| `ProAccountDeletionTest.php` | licences when a user is deleted |
| `StripeWebhookSecretCheckTest.php` | the health check |

## Faking Stripe

Two helpers, each at the bottom of the file that uses it (helper names are global, so they stay unique across the suite):

- **`fakeStripe()`** in `ProCheckoutTest.php` binds a client whose `sessions->create()` records the payload instead of calling the API and returns a `Session::constructFrom([...])`. The returned spy exposes `sessionPayload`, which is how the tests assert on `line_items`, `mode` and `metadata` — assert on **what we sent Stripe**, not on a mock expectation.
- **`fakeStripeInvoices()`** in `StripeWebhookTest.php` binds a client whose `invoices->retrieve()` returns a constructed `Invoice`. Call it in `beforeEach`. Without it every fulfillment test makes a real request that 401s, so the invoice-capture path only ever runs in its failure mode — slower and flakier, and it stops proving anything.

Both work the same way: build an anonymous class, then `app()->bind(StripeClient::class, fn () => $client)`.

## Driving a Webhook

Prefer invoking the listener directly — it is faster and keeps the test about fulfillment rather than about HTTP:

```php
handleStripeEvent('checkout.session.completed', completedSession($license->uuid));
```

`handleStripeEvent()` wraps the payload in a `WebhookReceived` and calls `app(HandleStripeWebhook::class)`. `completedSession()` returns the shape Stripe actually sends for a completed one-off session — copy it rather than inventing a payload, and extend it with named arguments (`tax:`, `country:`) when a test needs them.

Post to the endpoint only when the **endpoint itself** is what is under test (signature verification):

```php
postJson('/stripe/webhook', $payload, ['Stripe-Signature' => stripeSignature($payload)])->assertOk();
```

`stripeSignature()` computes the HMAC from `config('cashier.webhook.secret')`, which that describe block sets to `whsec_test_secret` in its own `beforeEach`.

## What Every Billing Test Needs

- `Notification::fake()` in `beforeEach`, so `ProPurchaseReceipt` is assertable and no mail is attempted
- `Feature::define('songrank-pro', true)` — the flag resolves on `is_dev`, so the routes 404 without it. `proUser()` from `tests/Helpers/users.php` builds an `is_dev` user holding an active licence; `pendingLicense()` builds the pending row
- `config()->set('billing.pro.price_id', 'price_test_pro')` when the payload matters
- `PENNANT_STORE=array` is already set in `phpunit.xml`, so flags do not leak between tests

## Always Prove Idempotency

Fulfillment runs from both the success redirect and the webhook, and Stripe retries for three days. Any change to it must keep this passing:

```php
handleStripeEvent('checkout.session.completed', $session);
handleStripeEvent('checkout.session.completed', $session);
handleStripeEvent('checkout.session.completed', $session);

assertDatabaseCount('pro_licenses', 1);
Notification::assertSentToTimes($license->user, ProPurchaseReceipt::class, 1);
```

`assertDatabaseHas` is the right assertion for licence state here: these tests care about the exact column values Stripe produced (`amount_total`, `amount_tax`, `currency`, `billing_country`, the four `stripe_*` ids), which `assertModelExists` cannot express.

## Running Them

```bash
php artisan test --compact tests/Feature/Billing
```
