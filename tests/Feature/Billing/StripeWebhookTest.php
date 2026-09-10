<?php

use App\Actions\Billing\HandleStripeWebhook;
use App\Enums\Billing\ProLicenseSource;
use App\Enums\Billing\ProLicenseStatus;
use App\Models\ProLicense;
use App\Models\User;
use App\Notifications\ProPurchaseReceipt;
use Illuminate\Support\Facades\Notification;
use Laravel\Cashier\Events\WebhookReceived;
use Stripe\Invoice;
use Stripe\StripeClient;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

beforeEach(function () {
    Notification::fake();
    fakeStripeInvoices();
});

describe('checkout.session.completed', function () {
    it('activates a pending license and records the payment', function () {
        $license = pendingLicense();

        handleStripeEvent('checkout.session.completed', completedSession($license->uuid));

        assertDatabaseHas('pro_licenses', [
            'id' => $license->getKey(),
            'status' => ProLicenseStatus::ACTIVE->value,
            'stripe_checkout_session_id' => 'cs_test_songrank',
            'stripe_payment_intent_id' => 'pi_test_songrank',
            'stripe_invoice_id' => 'in_test_songrank',
            'stripe_customer_id' => 'cus_test_songrank',
            'amount_total' => 1000,
            'currency' => 'usd',
        ]);

        expect($license->fresh()->purchased_at)->not->toBeNull()
            ->and($license->user->fresh()->is_pro)->toBeTrue();
    });

    it('records tax and billing country for VAT reporting', function () {
        $license = pendingLicense();

        handleStripeEvent('checkout.session.completed', completedSession(
            uuid: $license->uuid,
            tax: 210,
            country: 'DE',
        ));

        assertDatabaseHas('pro_licenses', [
            'id' => $license->getKey(),
            'amount_tax' => 210,
            'billing_country' => 'DE',
        ]);
    });

    it('is idempotent when Stripe retries the same event', function () {
        $license = pendingLicense();
        $session = completedSession($license->uuid);

        handleStripeEvent('checkout.session.completed', $session);
        handleStripeEvent('checkout.session.completed', $session);
        handleStripeEvent('checkout.session.completed', $session);

        assertDatabaseCount('pro_licenses', 1);

        Notification::assertSentToTimes($license->user, ProPurchaseReceipt::class, 1);
    });

    it('does not activate a session that was never paid', function () {
        $license = pendingLicense();

        handleStripeEvent('checkout.session.completed', [
            ...completedSession($license->uuid),
            'payment_status' => 'unpaid',
        ]);

        expect($license->fresh()->status)->toBe(ProLicenseStatus::PENDING);
        Notification::assertNothingSent();
    });

    it('ignores a session whose license cannot be found', function () {
        handleStripeEvent('checkout.session.completed', completedSession('00000000-0000-4000-8000-000000000000'));

        assertDatabaseCount('pro_licenses', 0);
    });

    it('captures the Stripe invoice links', function () {
        $license = pendingLicense();

        handleStripeEvent('checkout.session.completed', completedSession($license->uuid));

        $license->refresh();

        expect($license->stripe_invoice_id)->toBe('in_test_songrank')
            ->and($license->hosted_invoice_url)->toContain('in_test_songrank')
            ->and($license->invoice_pdf_url)->toContain('in_test_songrank');
    });

    it('will not resurrect a license that was already refunded', function () {
        $license = ProLicense::factory()->refunded()->create();

        handleStripeEvent('checkout.session.completed', completedSession($license->uuid));

        expect($license->fresh()->status)->toBe(ProLicenseStatus::REFUNDED)
            ->and($license->user->fresh()->is_pro)->toBeFalse();

        Notification::assertNothingSent();
    });

    it('backfills an invoice the first caller could not see', function () {
        $license = pendingLicense();

        /* The browser wins the race on a session Stripe has not invoiced yet. */
        handleStripeEvent('checkout.session.completed', [
            ...completedSession($license->uuid),
            'invoice' => null,
        ]);

        expect($license->fresh()->stripe_invoice_id)->toBeNull();

        /* The webhook lands a moment later, once the invoice exists. */
        handleStripeEvent('checkout.session.completed', completedSession($license->uuid));

        expect($license->fresh()->stripe_invoice_id)->toBe('in_test_songrank')
            ->and($license->fresh()->hosted_invoice_url)->not->toBeNull();
    });

    it('notifies the buyer by mail and in-app', function () {
        $license = pendingLicense();

        handleStripeEvent('checkout.session.completed', completedSession($license->uuid));

        Notification::assertSentTo(
            $license->user,
            ProPurchaseReceipt::class,
            fn (ProPurchaseReceipt $notification, array $channels) => $channels === ['mail', 'database'],
        );
    });
});

describe('abandonment events', function () {
    it('marks an expired session abandoned', function () {
        $license = pendingLicense();

        handleStripeEvent('checkout.session.expired', completedSession($license->uuid));

        expect($license->fresh()->status)->toBe(ProLicenseStatus::ABANDONED);
    });

    it('marks a failed async payment abandoned', function () {
        $license = pendingLicense();

        handleStripeEvent('checkout.session.async_payment_failed', completedSession($license->uuid));

        expect($license->fresh()->status)->toBe(ProLicenseStatus::ABANDONED);
    });

    it('never abandons a license that already went active', function () {
        $license = ProLicense::factory()->active()->create();

        handleStripeEvent('checkout.session.expired', completedSession($license->uuid));

        expect($license->fresh()->status)->toBe(ProLicenseStatus::ACTIVE);
    });

    it('fulfills a successful async payment', function () {
        $license = pendingLicense();

        handleStripeEvent('checkout.session.async_payment_succeeded', completedSession($license->uuid));

        expect($license->fresh()->status)->toBe(ProLicenseStatus::ACTIVE);
    });
});

describe('charge.refunded', function () {
    it('revokes access and records the refund', function () {
        $license = ProLicense::factory()->active()->create([
            'stripe_payment_intent_id' => 'pi_test_refund',
        ]);

        handleStripeEvent('charge.refunded', [
            'id' => 'ch_test_refund',
            'payment_intent' => 'pi_test_refund',
            'amount_refunded' => 1000,
        ]);

        $license->refresh();

        expect($license->status)->toBe(ProLicenseStatus::REFUNDED)
            ->and($license->amount_refunded)->toBe(1000)
            ->and($license->refunded_at)->not->toBeNull()
            ->and($license->user->fresh()->is_pro)->toBeFalse();
    });

    it('ignores a refund for a payment we do not know about', function () {
        $license = ProLicense::factory()->active()->create();

        handleStripeEvent('charge.refunded', [
            'payment_intent' => 'pi_test_unknown',
            'amount_refunded' => 1000,
        ]);

        expect($license->fresh()->status)->toBe(ProLicenseStatus::ACTIVE);
    });

    it('keeps access on a partial refund and records the amount', function () {
        $license = ProLicense::factory()->active()->create([
            'stripe_payment_intent_id' => 'pi_test_partial',
        ]);

        handleStripeEvent('charge.refunded', [
            'payment_intent' => 'pi_test_partial',
            'amount' => 1000,
            'amount_refunded' => 200,
        ]);

        $license->refresh();

        expect($license->status)->toBe(ProLicenseStatus::ACTIVE)
            ->and($license->amount_refunded)->toBe(200)
            ->and($license->user->fresh()->is_pro)->toBeTrue();
    });

    it('ends access once the whole charge is refunded', function () {
        $license = ProLicense::factory()->active()->create([
            'stripe_payment_intent_id' => 'pi_test_full',
        ]);

        handleStripeEvent('charge.refunded', [
            'payment_intent' => 'pi_test_full',
            'amount' => 1000,
            'amount_refunded' => 1000,
        ]);

        expect($license->fresh()->status)->toBe(ProLicenseStatus::REFUNDED)
            ->and($license->user->fresh()->is_pro)->toBeFalse();
    });
});

describe('a buyer who is no longer there', function () {
    it('still records the payment', function () {
        $license = pendingLicense();
        $license->update(['user_id' => null]);

        handleStripeEvent('checkout.session.completed', completedSession($license->uuid));

        expect($license->fresh()->status)->toBe(ProLicenseStatus::ACTIVE)
            ->and($license->fresh()->amount_total)->toBe(1000);
    });

    it('sends nothing, rather than failing', function () {
        $license = pendingLicense();
        $license->update(['user_id' => null]);

        handleStripeEvent('checkout.session.completed', completedSession($license->uuid));

        Notification::assertNothingSent();
    });
});

describe('charge.dispute.created', function () {
    it('revokes access on a chargeback', function () {
        $license = ProLicense::factory()->active()->create([
            'stripe_payment_intent_id' => 'pi_test_dispute',
        ]);

        handleStripeEvent('charge.dispute.created', [
            'id' => 'dp_test_1',
            'payment_intent' => 'pi_test_dispute',
        ]);

        $license->refresh();

        expect($license->status)->toBe(ProLicenseStatus::REVOKED)
            ->and($license->revoked_at)->not->toBeNull()
            ->and($license->notes)->toContain('chargeback');
    });
});

describe('unhandled events', function () {
    it('ignores event types the application does not act on', function () {
        $license = ProLicense::factory()->active()->create();

        handleStripeEvent('customer.subscription.created', ['id' => 'sub_test']);

        expect($license->fresh()->status)->toBe(ProLicenseStatus::ACTIVE);
    });
});

describe('the webhook endpoint', function () {
    beforeEach(fn () => config()->set('cashier.webhook.secret', 'whsec_test_secret'));

    it('rejects a payload with no signature', function () {
        postJson('/stripe/webhook', [
            'id' => 'evt_test',
            'type' => 'checkout.session.completed',
            'data' => ['object' => []],
        ])->assertForbidden();
    });

    it('rejects a payload with a forged signature', function () {
        postJson('/stripe/webhook', [
            'id' => 'evt_test',
            'type' => 'checkout.session.completed',
            'data' => ['object' => []],
        ], ['Stripe-Signature' => 't=1,v1=forged'])->assertForbidden();
    });

    it('accepts and fulfills a correctly signed payload', function () {
        $license = pendingLicense();

        $payload = [
            'id' => 'evt_test',
            'type' => 'checkout.session.completed',
            'data' => ['object' => completedSession($license->uuid)],
        ];

        postJson('/stripe/webhook', $payload, [
            'Stripe-Signature' => stripeSignature($payload),
        ])->assertOk();

        expect($license->fresh()->status)->toBe(ProLicenseStatus::ACTIVE);
    });
});

/* Helpers */

/**
 * Stop fulfilment reaching for api.stripe.com.
 *
 * Without this every fulfilment test makes a real request that 401s, so the
 * invoice-capture path only ever runs in its failure mode — and the suite is
 * slower and flakier for it.
 */
function fakeStripeInvoices(): void
{
    $invoices = new class
    {
        public function retrieve(string $id, array $params = [], $opts = null): Invoice
        {
            return Invoice::constructFrom([
                'id' => $id,
                'object' => 'invoice',
                'hosted_invoice_url' => "https://invoice.stripe.com/i/{$id}",
                'invoice_pdf' => "https://pay.stripe.com/invoice/{$id}/pdf",
            ]);
        }
    };

    $client = new class(['api_key' => 'sk_test_songrank'], $invoices) extends StripeClient
    {
        private object $invoices;

        public function __construct(array $config, object $invoices)
        {
            parent::__construct($config);

            $this->invoices = $invoices;
        }

        public function getService($name)
        {
            return $name === 'invoices' ? $this->invoices : parent::getService($name);
        }
    };

    app()->bind(StripeClient::class, fn () => $client);
}

function pendingLicense(): ProLicense
{
    return ProLicense::factory()
        ->pending()
        ->for(User::factory())
        ->create(['source' => ProLicenseSource::PURCHASE]);
}

/**
 * @param  array<string, mixed>  $object
 */
function handleStripeEvent(string $type, array $object): void
{
    app(HandleStripeWebhook::class)(new WebhookReceived([
        'id' => 'evt_test_'.uniqid(),
        'type' => $type,
        'data' => ['object' => $object],
    ]));
}

/**
 * The shape Stripe sends for a completed one-off Checkout Session.
 *
 * @return array<string, mixed>
 */
function completedSession(string $uuid, int $tax = 0, string $country = 'US'): array
{
    return [
        'id' => 'cs_test_songrank',
        'object' => 'checkout.session',
        'mode' => 'payment',
        'payment_status' => 'paid',
        'status' => 'complete',
        'client_reference_id' => $uuid,
        'metadata' => ['pro_license_uuid' => $uuid],
        'payment_intent' => 'pi_test_songrank',
        'invoice' => 'in_test_songrank',
        'customer' => 'cus_test_songrank',
        'currency' => 'usd',
        'amount_subtotal' => 1000 - $tax,
        'amount_total' => 1000,
        'total_details' => ['amount_tax' => $tax, 'amount_discount' => 0],
        'customer_details' => [
            'email' => 'buyer@example.com',
            'address' => ['country' => $country],
        ],
    ];
}

/**
 * @param  array<string, mixed>  $payload
 */
function stripeSignature(array $payload): string
{
    $timestamp = time();
    $secret = config('cashier.webhook.secret');
    $signature = hash_hmac('sha256', "{$timestamp}.".json_encode($payload), $secret);

    return "t={$timestamp},v1={$signature}";
}
