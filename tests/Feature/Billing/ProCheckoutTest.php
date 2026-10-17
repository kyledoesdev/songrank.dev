<?php

use App\Enums\Billing\ProLicenseStatus;
use App\Models\ProLicense;
use App\Models\User;
use Stripe\Checkout\Session;
use Stripe\Customer;
use Stripe\Exception\InvalidRequestException;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\post;

beforeEach(function () {
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

    it('remembers the Stripe session it opened', function () {
        fakeStripe();

        actingAs(User::factory()->createOne())->post(route('billing.checkout'));

        expect(ProLicense::sole()->stripe_checkout_session_id)->toBe('cs_test_songrank');
    });

    it('closes a checkout the buyer left open in another tab', function () {
        $stripe = fakeStripe();
        $buyer = User::factory()->createOne();
        $otherTab = ProLicense::factory()->pending()->for($buyer)->create(['stripe_checkout_session_id' => 'cs_test_other_tab']);
        $someoneElse = ProLicense::factory()->pending()->create(['stripe_checkout_session_id' => 'cs_test_someone_else']);

        actingAs($buyer)->post(route('billing.checkout'));

        expect($stripe->sessions->expired)->toBe(['cs_test_other_tab'])
            ->and($otherTab->fresh()->status)->toBe(ProLicenseStatus::ABANDONED)
            ->and($someoneElse->fresh()->status)->toBe(ProLicenseStatus::PENDING)
            ->and($buyer->proLicenses()->pending()->sole()->stripe_checkout_session_id)->toBe('cs_test_songrank');
    });

    it('leaves a checkout for fulfillment once Stripe says it was paid', function () {
        $stripe = fakeStripe();
        $stripe->sessions->paid = ['cs_test_paid'];
        $buyer = User::factory()->createOne();
        $paid = ProLicense::factory()->pending()->for($buyer)->create(['stripe_checkout_session_id' => 'cs_test_paid']);

        actingAs($buyer)->post(route('billing.checkout'));

        expect($paid->fresh()->status)->toBe(ProLicenseStatus::PENDING);
    });

    it('sends the buyer back to billing when Stripe cannot be reached', function () {
        actingAs(User::factory()->createOne())
            ->post(route('billing.checkout'))
            ->assertRedirect(route('billing'))
            ->assertSessionHasErrors('checkout');

        expect(ProLicense::sole()->status)->toBe(ProLicenseStatus::ABANDONED);
    });

    it('limits how often one person can start a checkout', function () {
        fakeStripe();

        actingAs(User::factory()->createOne());

        $statuses = collect(range(1, 10))->map(fn (): int => post(route('billing.checkout'))->status());
        $started = $statuses->reject(fn (int $status): bool => $status === 429)->count();

        expect($statuses)->toContain(429);

        assertDatabaseCount('pro_licenses', $started);
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
            ->assertSessionHas('success');

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
            ->assertSessionHas('success');

        expect($license->fresh()->status)->toBe(ProLicenseStatus::PENDING)
            ->and($user->fresh()->is_pro)->toBeFalse();
    });

    it('never hands Pro to someone returning with another buyer\'s session', function () {
        $stripe = fakeStripe();
        $buyer = User::factory()->createOne();

        actingAs($buyer)->post(route('billing.checkout'));

        $license = ProLicense::first();
        $stripe->sessions->retrieved = paidSession($license->uuid);

        $stranger = User::factory()->createOne();

        actingAs($stranger)
            ->get(route('billing.success', ['session_id' => 'cs_test_songrank']))
            ->assertRedirect(route('billing'));

        expect($stranger->fresh()->is_pro)->toBeFalse()
            ->and($stranger->proLicenses()->exists())->toBeFalse()
            ->and($license->fresh()->user->is($buyer))->toBeTrue();
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

        /** @var list<string> */
        public array $expired = [];

        /** @var list<string> */
        public array $paid = [];

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

        public function expire(string $id, $params = null, $opts = null): Session
        {
            if (in_array($id, $this->paid, true)) {
                throw InvalidRequestException::factory('Only Checkout Sessions with a status of open can be expired.');
            }

            $this->expired[] = $id;

            return Session::constructFrom(['id' => $id, 'object' => 'checkout.session', 'status' => 'expired']);
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

    fakeStripeServices([
        'checkout' => new class($sessions)
        {
            public function __construct(public object $sessions) {}
        },
        'customers' => $customers,
    ]);

    return $spy;
}
