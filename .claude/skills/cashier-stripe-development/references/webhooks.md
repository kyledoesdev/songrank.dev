# Webhooks Reference

How songrank.dev receives Stripe events. This application sells a one-off licence, so **none of Cashier's built-in subscription handlers are in play** — the lifecycle is ours.

## The Route

Cashier registers two routes under `config('cashier.path')` (default `stripe`):

- `POST /stripe/webhook` → `cashier.webhook`
- `GET /stripe/payment/{id}` → `cashier.payment` (unused here — it is the incomplete-subscription-payment screen)

**There is no CSRF middleware to exclude.** Cashier registers its own route group without the `web` group, so `stripe/webhook` carries only `VerifyWebhookSignature`. Confirm before changing anything:

```bash
php artisan route:list --path=stripe/webhook -v
```

`VerifyWebhookSignature` is applied automatically whenever `cashier.webhook.secret` is set. `app/Checks/StripeWebhookSecretCheck.php` is the Spatie Health check that notices when it is missing in production.

## Which Events We Handle

`app/Enums/Billing/StripeWebhookEvent` is the single list, and `config/cashier.php` pins `webhook.events` to it so `php artisan cashier:webhook` subscribes the endpoint to exactly these:

| Event | Handled by | Effect |
| --- | --- | --- |
| `checkout.session.completed` | `FulfillProPurchase` | pending → active, receipt sent |
| `checkout.session.async_payment_succeeded` | `FulfillProPurchase` | same, for delayed payment methods |
| `checkout.session.expired` | `HandleStripeWebhook::abandon` | pending → abandoned |
| `checkout.session.async_payment_failed` | `HandleStripeWebhook::abandon` | pending → abandoned |
| `charge.refunded` | `HandleStripeWebhook::refund` | partial refund records the amount; a full one revokes |
| `charge.dispute.created` | `HandleStripeWebhook::dispute` | revoke and `Log::warning` |

Anything else falls through `match`'s `default => null`. **Returning quietly is deliberate** — throwing on an unrecognised event would put Stripe into a retry loop.

## How Routing Works

Cashier's `WebhookController` accepts the payload, verifies the signature, and dispatches `WebhookReceived` for every event. `HandleStripeWebhook` is an invokable listener on that event:

```php
public function __invoke(WebhookReceived $event): void
{
    $type = StripeWebhookEvent::tryFrom(data_get($event->payload, 'type', ''));
    $object = data_get($event->payload, 'data.object', []);

    match ($type) { /* … */ };
}
```

Two things to keep in mind:

- **Resolve the type with `tryFrom()`.** Stripe event names contain dots, so they can never be looked up with `data_get()` — `data_get($map, 'checkout.session.completed')` would descend into nested keys.
- **Do not extend `WebhookController`.** Listening to `WebhookReceived` is the whole mechanism here. Subclassing it would mean calling `Cashier::ignoreRoutes()` and re-registering routes for no benefit, since none of the events we care about have a parent handler to call.

## Orphaned Money

A refund or dispute is matched back to a licence through `stripe_payment_intent_id`. If no licence claims that payment intent, `reportOrphanedMoney()` logs a warning rather than returning silently — Stripe will keep retrying the original `checkout.session.completed`, and without the log we could hand Pro to somebody already refunded.

## Local Development

```bash
stripe login
stripe listen --forward-to song-ranker.test/stripe/webhook
stripe trigger checkout.session.completed
```

The CLI prints its **own** `whsec_…` signing secret for that session. It is not the Dashboard endpoint secret. Put the CLI's value in `STRIPE_WEBHOOK_SECRET` while forwarding locally, or signature verification fails with a 403.

`stripe trigger` builds a synthetic session with no `metadata.pro_license_uuid`, so fulfillment will log "a paid Stripe session referenced no known Pro license" and stop. To exercise a real purchase end to end, start a checkout from `/billing` and pay with `4242 4242 4242 4242`.

## Registering the Endpoint

```bash
php artisan cashier:webhook
```

This reads `config('cashier.webhook.events')` — our enum, not Cashier's `DEFAULT_EVENTS`. If that pin is ever removed, the created endpoint subscribes to eight subscription-shaped events, never delivers `checkout.session.completed`, and every purchase silently stops fulfilling.
