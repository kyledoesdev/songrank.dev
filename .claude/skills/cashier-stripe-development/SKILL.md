---
name: cashier-stripe-development
description: "Handles this application's Stripe billing through Laravel Cashier: the one-time Song Rank Pro purchase, Stripe Checkout sessions, the pro_licenses entitlement, fulfillment idempotency, webhook routing, refunds and chargebacks, invoices, and automatic tax. Trigger whenever a task mentions Cashier, Stripe, Checkout, billing, Pro, licences, payments, refunds, receipts or webhooks. Also applies when testing billing flows or troubleshooting webhook signature and fulfillment problems. This application has no subscriptions."
license: MIT
metadata:
  author: laravel
  customized-for: songrank.dev
---

# Cashier Stripe Development

**songrank.dev sells one thing: a single, non-recurring $10 Song Rank Pro licence.** There are no subscriptions, no plans, no seats, no trials and no metered billing. Cashier is here for its Stripe client, the `Billable` trait, its Checkout wrapper and its webhook plumbing — nothing more.

If a task sounds like `newSubscription()`, `swap()`, `subscribed()`, grace periods, `trial_ends_at`, proration or `IncompletePayment`, **none of it applies here.** Say so rather than introducing a subscription, and solve the problem on the one-off purchase path below.

## The Flag Is Not the Entitlement

Two concepts, deliberately never merged:

- **The `songrank-pro` Pennant flag** answers "does the Pro system exist for this user yet?" It is a rollout switch.
- **A `pro_licenses` row** answers "has this person paid?" It is the entitlement.

**Never grant Pro by activating a Pennant flag.** `users.is_pro` is a projection of the licences table maintained by `ProLicenseObserver`, not a second source of truth — so `$user->is_pro` is a column read, which matters because the nav, the setup header, the middleware and the billing page all ask on a single page load.

The one gap is mass assignment: `ProLicense::query()->update(...)` fires no model events, so anything doing that must call `$user->syncProStatus()` itself. `DeleteUserJob` is the only place that currently does.

## The Purchase Lifecycle

1. **`POST /billing/checkout`** → `ProCheckoutController::store` → `StartProCheckout`
   Writes a `pro_licenses` row with status `pending` **before** redirecting, so a webhook arriving moments later has something to attach to. The licence uuid rides along in both `client_reference_id` and `metadata.pro_license_uuid`. The session is `mode: payment` with `invoice_creation.enabled`. If Stripe issues no session the row is marked `abandoned` and the throwable re-raised — nothing will ever attach to it.
2. **`GET /billing/checkout/success?session_id=…`** → `ProCheckoutController::show` → `FulfillProPurchase`
3. **In parallel**, `checkout.session.completed` reaches Cashier's webhook route, which dispatches `WebhookReceived`, which `HandleStripeWebhook` listens to → `FulfillProPurchase`

Both callers run, and Stripe retries webhooks for three days, so **fulfillment must stay safe to run repeatedly**:

- It **claims** the licence with a single conditional `where('status', PENDING)->update(...)` and checks the affected row count. Reading the status into PHP and then writing would leave a window where two callers both pass the check and both send a receipt.
- The loser backfills any field the winner could not populate. Stripe raises the invoice asynchronously, so an early caller can legitimately see `session.invoice` as `null`.
- The invoice is read over HTTP **before** the write opens — no HTTP calls inside a database call.
- `payment_status` is resolved through `StripePaymentStatus::tryFrom()` and must be settled; anything else returns early.

## Rules

- **Resolve Stripe strings through enums.** `StripeWebhookEvent` and `StripePaymentStatus`, always via `tryFrom()`, never by comparing raw strings. Stripe event names contain dots, so they can never be looked up with `data_get()`.
- **`config/cashier.php` pins `webhook.events` to `StripeWebhookEvent::cases()`**, not Cashier's `DEFAULT_EVENTS`. The defaults are all subscription-shaped; leaving them would make `php artisan cashier:webhook` create an endpoint that never delivers `checkout.session.completed`, silently stopping every purchase from fulfilling.
- **Never let a money event pass in silence.** A refund or chargeback for a payment intent no licence claims is `Log::warning`ed loudly, because Stripe will keep retrying the original `checkout.session.completed` and could hand Pro to somebody already refunded.
- **Money is integer minor units**, displayed through `Cashier::formatAmount()`. Never floats.
- **Store as little as possible.** No card brand or last four, no payment-method columns on `users`, no trial columns, no subscription tables — Cashier's published migrations for those were deliberately removed. Stripe holds the card; we hold an invoice URL. Anything added here has to be disclosed in the privacy policy, so the bar is "the product genuinely cannot work without it".
- **Invoice PDFs come from Stripe** (`invoice_creation` on the session). There is no PDF library and none should be added, whatever `config/cashier.php`'s `invoices.renderer` default says.
- **Automatic tax is opt-in** via `STRIPE_AUTOMATIC_TAX`, gating both `Cashier::calculateTaxes()` and the session's `automatic_tax`/`tax_id_collection`. Sending those before the Stripe account has a head office address makes Stripe reject the whole checkout session, so it stays off until Stripe Tax is genuinely configured.
- **The checkout session is a resource:** `store`, `show`, `cancel`. `show` and `cancel` answer GET because Stripe redirects the customer's browser to them. `RedirectIfAlreadyPro` handles entitlement on the route so the controller keeps a single return type.
- **Limits are a UI block, not a policy.** `setup-header.blade.php` swaps the search box for an upgrade prompt when the allowance is spent, so a capped user never reaches a search. `ensureCanCreateRanking()` / `ensureCanCreateTierlist()` are backstops for stale pages. There is no ranking or tier list policy — a 403 after clicking "begin ranking" is the wrong place to find out.

## The Files

| File | Role |
| --- | --- |
| `app/Actions/Billing/StartProCheckout.php` | Pending licence + Checkout session |
| `app/Actions/Billing/FulfillProPurchase.php` | Claim, backfill, receipt |
| `app/Actions/Billing/HandleStripeWebhook.php` | Routes the events Cashier ignores |
| `app/Actions/Billing/GrantProLicense.php` | Manual/comped grants |
| `app/Actions/Billing/RevokeProLicense.php` | Refunds, chargebacks, admin revocation |
| `app/Enums/Billing/` | `StripeWebhookEvent`, `StripePaymentStatus`, `ProLicenseStatus`, `ProLicenseSource` |
| `app/Observers/ProLicenseObserver.php` | Keeps `users.is_pro` true to the licences table |
| `app/Services/Billing/StripeReceiptService.php` | Invoice URL / PDF lookup |
| `app/Http/Controllers/Billing/ProCheckoutController.php` | `store`, `show`, `cancel` |
| `app/Http/Middleware/RedirectIfAlreadyPro.php` | Entitlement guard on the route |
| `config/billing.php` | Price, tax switch, ranking and tier list limits |
| `routes/billing.php` | All of it behind `EnsureFeaturesAreActive` |

Deeper reference, read before implementing:

- `references/webhooks.md` — which events we handle, how they are routed, local forwarding with the Stripe CLI
- `references/testing.md` — how billing is tested here (no live Stripe calls)

## Verification

1. `php artisan test --compact tests/Feature/Billing` — checkout, fulfillment, webhooks, entitlement, the billing page
2. Confirm fulfillment is still idempotent: the suite replays `checkout.session.completed` three times and asserts one licence and exactly one `ProPurchaseReceipt`
3. `php artisan pennant:purge` after any licence-related deploy, and remember the flag is not the entitlement

## Pitfalls

- **Our webhook route has no CSRF middleware to exclude.** Cashier registers `stripe/webhook` in its own route group with only `VerifyWebhookSignature` — it never joins the `web` group. Advice about adding `stripe/*` to `preventRequestForgery(except: …)` is from an older Laravel/Cashier layout and does not apply. Verify with `php artisan route:list --path=stripe/webhook -v` before changing anything.
- The Stripe CLI prints its own `whsec_…` signing secret per `listen` session. It is **not** the Dashboard endpoint secret. Using the wrong one fails signature verification, and `StripeWebhookSecretCheck` is the health check that notices in production.
- `CASHIER_CURRENCY` is set explicitly in `.env.example`; `config/billing.php` also carries a fallback currency for licences that predate a recorded one.
- The migration publish tag is `cashier-migrations`, not `cashier` — but do not publish the subscription migrations. They were removed on purpose.
- In MySQL, `stripe_id`-style columns want `utf8_bin` collation to stay case-sensitive.
- `php artisan cashier:webhook` reads `config('cashier.webhook.events')`. Check that list before assuming an event reaches us.
- Use `search-docs` for current Cashier API details rather than relying on this skill alone — but prefer this file for *what this application does*.
