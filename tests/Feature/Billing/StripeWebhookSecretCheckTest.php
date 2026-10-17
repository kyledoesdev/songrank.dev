<?php

use App\Checks\StripeWebhookSecretCheck;
use App\Enums\Billing\StripeWebhookEvent;
use Spatie\Health\Enums\Status;

describe('the webhook secret health check', function () {
    it('passes when a secret is configured', function () {
        config()->set('cashier.webhook.secret', 'whsec_test_secret');

        expect(StripeWebhookSecretCheck::new()->run()->status)->toBe(Status::ok());
    });

    it('fails when the secret is missing, because the endpoint would accept unsigned requests', function () {
        config()->set('cashier.webhook.secret', null);

        $result = StripeWebhookSecretCheck::new()->run();

        expect($result->status)->toBe(Status::failed())
            ->and($result->notificationMessage)->toContain('unsigned');
    });

    it('fails on an empty string, not just null', function () {
        config()->set('cashier.webhook.secret', '');

        expect(StripeWebhookSecretCheck::new()->run()->status)->toBe(Status::failed());
    });
});

describe('the webhook event subscription list', function () {
    it('subscribes the Stripe endpoint to exactly the events we handle', function () {
        expect(config('cashier.webhook.events'))
            ->toBe(array_column(StripeWebhookEvent::cases(), 'value'));
    });

    it('does not carry Cashier subscription defaults, which would never deliver a purchase', function () {
        expect(config('cashier.webhook.events'))
            ->not->toContain('customer.subscription.created')
            ->and(config('cashier.webhook.events'))->toContain('checkout.session.completed');
    });
});
