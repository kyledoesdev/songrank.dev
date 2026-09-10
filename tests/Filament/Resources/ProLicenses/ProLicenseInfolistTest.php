<?php

use App\Filament\Resources\ProLicenses\Pages\ViewProLicense;
use App\Filament\Resources\Users\UserResource;
use App\Models\ProLicense;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(fn () => actingAs(kyle()));

describe('the licence detail view', function () {
    it('shows the whole Stripe trail', function () {
        $license = ProLicense::factory()->active()->create();

        Livewire::test(ViewProLicense::class, ['record' => $license->uuid])
            ->assertOk()
            ->assertSee($license->stripe_checkout_session_id)
            ->assertSee($license->stripe_payment_intent_id)
            ->assertSee($license->stripe_invoice_id)
            ->assertSee($license->stripe_customer_id);
    });

    it('breaks the money down', function () {
        $license = ProLicense::factory()->active()->withTax(210, 'DE')->create();

        Livewire::test(ViewProLicense::class, ['record' => $license->uuid])
            ->assertSee('$10.00')
            ->assertSee('$2.10')
            ->assertSee('DE');
    });

    it('explains why a complimentary licence exists', function () {
        $license = ProLicense::factory()->comp()->create(['notes' => 'Top ranker.']);

        Livewire::test(ViewProLicense::class, ['record' => $license->uuid])
            ->assertSee('Top ranker.')
            ->assertSee('Complimentary');
    });

    it('hides the money and Stripe sections on a complimentary licence', function () {
        $license = ProLicense::factory()->comp()->create();

        Livewire::test(ViewProLicense::class, ['record' => $license->uuid])
            ->assertOk()
            ->assertDontSee('Payment Intent')
            ->assertDontSee('Checkout Session');
    });

    it('resolves the record by uuid rather than id', function () {
        $license = ProLicense::factory()->active()->create();

        expect($license->getRouteKey())->toBe($license->uuid);
    });
});

describe('the link to the buyer', function () {
    it('points the buyer name at their user record', function () {
        $buyer = User::factory()->createOne();
        $license = ProLicense::factory()->active()->for($buyer)->create();

        Livewire::test(ViewProLicense::class, ['record' => $license->uuid])
            ->assertOk()
            ->assertSee($buyer->name)
            ->assertSee(UserResource::getUrl('view', ['record' => $buyer]));
    });

    it('flags the name as a link with its own open-user button', function () {
        $buyer = User::factory()->createOne();
        $license = ProLicense::factory()->active()->for($buyer)->create();

        Livewire::test(ViewProLicense::class, ['record' => $license->uuid])
            ->assertSee('Open user');
    });

    it('offers no link once the account is gone', function () {
        $license = ProLicense::factory()->active()->create(['user_id' => null]);

        Livewire::test(ViewProLicense::class, ['record' => $license->uuid])
            ->assertOk()
            ->assertSee('Account deleted')
            ->assertDontSee('Open user');
    });

    it('never points at somebody else', function () {
        $buyer = User::factory()->createOne();
        $stranger = User::factory()->createOne();
        $license = ProLicense::factory()->active()->for($buyer)->create();

        Livewire::test(ViewProLicense::class, ['record' => $license->uuid])
            ->assertDontSee(UserResource::getUrl('view', ['record' => $stranger]));
    });
});
