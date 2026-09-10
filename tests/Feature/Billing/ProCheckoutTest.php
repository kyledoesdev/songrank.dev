<?php

use App\Enums\Billing\ProLicenseStatus;
use App\Models\ProLicense;
use App\Models\User;
use Laravel\Pennant\Feature;
use Stripe\Checkout\Session;
use Stripe\Customer;
use Stripe\StripeClient;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\post;

beforeEach(function () {
    Feature::define('songrank-pro', true);
    config()->set('billing.pro.price_id', 'price_test_pro');
});

describe('starting a checkout', function () {
    it('records a pending license before sending the user to Stripe', function () {
        fakeStripe();

        actingAs(User::factory()->createOne())
            ->post(route('billing.checkout'))
            ->assertRedirect('https://checkout.stripe.com/c/pay/cs_test_songrank');

        assertDatabaseCount('pro_licenses', 1);

        expect(ProLicense::first()->status)->toBe(ProLicenseStatus::PENDING);
    });

    it('sends Stripe the configured price at quantity one', function () {
        $stripe = fakeStripe();

        actingAs(User::factory()->createOne())->post(route('billing.checkout'));

        expect($stripe->sessionPayload['line_items'])->toBe([
            ['price' => 'price_test_pro', 'quantity' => 1],
        ])
            ->and($stripe->sessionPayload['mode'])->toBe('payment');
    });

    it('carries the license uuid so fulfillment can find it again', function () {
        $stripe = fakeStripe();

        actingAs(User::factory()->createOne())->post(route('billing.checkout'));

        $uuid = ProLicense::first()->uuid;

        expect($stripe->sessionPayload['metadata']['pro_license_uuid'])->toBe($uuid)
            ->and($stripe->sessionPayload['client_reference_id'])->toBe($uuid);
    });

    it('asks Stripe to raise an invoice so a receipt exists', function () {
        $stripe = fakeStripe();

        actingAs(User::factory()->createOne())->post(route('billing.checkout'));

        expect($stripe->sessionPayload['invoice_creation']['enabled'])->toBeTrue();
    });

    it('always collects a billing address', function () {
        $stripe = fakeStripe();

        actingAs(User::factory()->createOne())->post(route('billing.checkout'));

        expect($stripe->sessionPayload['billing_address_collection'])->toBe('required')
            ->and($stripe->sessionPayload['customer_update'])->toBe(['address' => 'auto', 'name' => 'auto']);
    });

    it('leaves tax alone while automatic tax is off', function () {
        config()->set('billing.tax.automatic', false);

        $stripe = fakeStripe();

        actingAs(User::factory()->createOne())->post(route('billing.checkout'));

        /* Stripe rejects the whole session if this is sent before a head office address exists. */
        expect($stripe->sessionPayload)->not->toHaveKey('automatic_tax')
            ->and($stripe->sessionPayload)->not->toHaveKey('tax_id_collection');
    });

    it('enables tax collection once switched on', function () {
        config()->set('billing.tax.automatic', true);

        $stripe = fakeStripe();

        actingAs(User::factory()->createOne())->post(route('billing.checkout'));

        expect($stripe->sessionPayload['automatic_tax']['enabled'])->toBeTrue()
            ->and($stripe->sessionPayload['tax_id_collection']['enabled'])->toBeTrue();
    });

    it('points Stripe back at the billing routes', function () {
        $stripe = fakeStripe();

        actingAs(User::factory()->createOne())->post(route('billing.checkout'));

        expect($stripe->sessionPayload['success_url'])->toStartWith(route('billing.success'))
            ->and($stripe->sessionPayload['success_url'])->toContain('{CHECKOUT_SESSION_ID}')
            ->and($stripe->sessionPayload['cancel_url'])->toBe(route('billing.cancel'));
    });

    it('turns an existing Pro user away without charging again', function () {
        fakeStripe();

        actingAs(proUser())
            ->post(route('billing.checkout'))
            ->assertRedirect(route('billing'));

        /* Only the license they already hold. */
        assertDatabaseCount('pro_licenses', 1);
    });
});

describe('returning from Stripe', function () {
    it('fulfills the purchase and welcomes the buyer', function () {
        $stripe = fakeStripe();
        $user = User::factory()->createOne();

        actingAs($user)->post(route('billing.checkout'));

        $license = ProLicense::first();
        $stripe->sessions->retrieved = paidSession($license->uuid);

        actingAs($user)
            ->get(route('billing.success', ['session_id' => 'cs_test_songrank']))
            ->assertRedirect(route('billing'))
            ->assertSessionHas('success', 'Welcome to Song Rank Pro!');

        expect($license->fresh()->status)->toBe(ProLicenseStatus::ACTIVE)
            ->and($user->fresh()->is_pro)->toBeTrue();
    });

    it('does not welcome anyone whose session is still unpaid', function () {
        $stripe = fakeStripe();
        $user = User::factory()->createOne();

        actingAs($user)->post(route('billing.checkout'));

        $license = ProLicense::first();
        $stripe->sessions->retrieved = [
            ...paidSession($license->uuid),
            'payment_status' => 'unpaid',
        ];

        actingAs($user)
            ->get(route('billing.success', ['session_id' => 'cs_test_songrank']))
            ->assertRedirect(route('billing'))
            ->assertSessionHas('success', 'Thanks! Your purchase is being confirmed and will appear here shortly.');

        expect($license->fresh()->status)->toBe(ProLicenseStatus::PENDING)
            ->and($user->fresh()->is_pro)->toBeFalse();
    });

    it('sends the user back to billing when no session id comes back', function () {
        actingAs(User::factory()->createOne())
            ->get(route('billing.success'))
            ->assertRedirect(route('billing'));
    });

    it('does not fail the user when Stripe cannot be reached', function () {
        actingAs(User::factory()->createOne())
            ->get(route('billing.success', ['session_id' => 'cs_test_unreachable']))
            ->assertRedirect(route('billing'))
            ->assertSessionHas('success');
    });

    it('cancelling charges nothing and says so', function () {
        actingAs(User::factory()->createOne())
            ->get(route('billing.cancel'))
            ->assertRedirect(route('billing'))
            ->assertSessionHas('success');
    });
});

describe('guests', function () {
    it('cannot start a checkout', function () {
        post(route('billing.checkout'))->assertRedirect(route('welcome'));
    });
});

/* Helpers */

/**
 * A settled Checkout Session, as the success redirect would re-read it.
 *
 * @return array<string, mixed>
 */
function paidSession(string $uuid): array
{
    return [
        'id' => 'cs_test_songrank',
        'object' => 'checkout.session',
        'mode' => 'payment',
        'payment_status' => 'paid',
        'metadata' => ['pro_license_uuid' => $uuid],
        'payment_intent' => 'pi_test_songrank',
        'invoice' => null,
        'customer' => 'cus_test_songrank',
        'currency' => 'usd',
        'amount_subtotal' => 1000,
        'amount_total' => 1000,
        'total_details' => ['amount_tax' => 0],
        'customer_details' => ['address' => ['country' => 'US']],
    ];
}

/**
 * Bind a Stripe client that records the checkout session payload instead of
 * calling the API. Returns an object whose `sessionPayload` holds what Cashier
 * ultimately sent.
 */
function fakeStripe(): object
{
    $spy = new class
    {
        /** @var array<string, mixed> */
        public array $sessionPayload = [];

        public ?object $sessions = null;
    };

    $sessions = new class($spy)
    {
        /** @var array<string, mixed> */
        public array $retrieved = [];

        public function __construct(private object $spy) {}

        public function create(array $payload = [], $opts = null): Session
        {
            $this->spy->sessionPayload = $payload;

            return Session::constructFrom([
                'id' => 'cs_test_songrank',
                'object' => 'checkout.session',
                'url' => 'https://checkout.stripe.com/c/pay/cs_test_songrank',
            ]);
        }

        public function retrieve(string $id, array $params = [], $opts = null): Session
        {
            return Session::constructFrom($this->retrieved + [
                'id' => $id,
                'object' => 'checkout.session',
            ]);
        }
    };

    $spy->sessions = $sessions;

    $customers = new class
    {
        public function create(array $params = [], $opts = null): Customer
        {
            return $this->customer();
        }

        public function update(string $id, array $params = [], $opts = null): Customer
        {
            return $this->customer();
        }

        public function retrieve(string $id, array $params = [], $opts = null): Customer
        {
            return $this->customer();
        }

        private function customer(): Customer
        {
            return Customer::constructFrom(['id' => 'cus_test_songrank', 'object' => 'customer']);
        }
    };

    /*
     * A real StripeClient with its service factory swapped out, rather than a
     * mock: Stripe resolves `$client->checkout` through getService(), which a
     * Mockery double cannot intercept cleanly.
     */
    $client = new class(['api_key' => 'sk_test_songrank'], $sessions, $customers) extends StripeClient
    {
        /** @var array<string, object> */
        private array $stubs;

        public function __construct(array $config, object $sessions, object $customers)
        {
            parent::__construct($config);

            $this->stubs = [
                'checkout' => new class($sessions)
                {
                    public function __construct(public object $sessions) {}
                },
                'customers' => $customers,
            ];
        }

        public function getService($name)
        {
            return $this->stubs[$name] ?? parent::getService($name);
        }
    };

    /*
     * bind(), not instance(): Cashier::stripe() resolves the client with build
     * parameters, and the container skips shared instances when any are given.
     */
    app()->bind(StripeClient::class, fn () => $client);

    return $spy;
}
