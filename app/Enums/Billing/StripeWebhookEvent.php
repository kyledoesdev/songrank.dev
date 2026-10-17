<?php

namespace App\Enums\Billing;

/**
 * The Stripe events this application acts on.
 *
 * Cashier's own controller covers eight subscription- and customer-shaped
 * events; none of the one-off purchase lifecycle is among them. These are the
 * ones the Stripe dashboard endpoint should subscribe to.
 */
enum StripeWebhookEvent: string
{
    case CHECKOUT_COMPLETED = 'checkout.session.completed';
    case CHECKOUT_ASYNC_SUCCEEDED = 'checkout.session.async_payment_succeeded';
    case CHECKOUT_ASYNC_FAILED = 'checkout.session.async_payment_failed';
    case CHECKOUT_EXPIRED = 'checkout.session.expired';
    case CHARGE_REFUNDED = 'charge.refunded';
    case DISPUTE_CREATED = 'charge.dispute.created';
}
