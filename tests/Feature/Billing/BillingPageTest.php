<?php

use App\Livewire\Billing\Billing;
use App\Models\ProLicense;
use App\Models\User;
use Livewire\Livewire;

describe('the free plan view', function () {
    it('shows what the plan is called and how to upgrade', function () {
        Livewire::actingAs(User::factory()->createOne())
            ->test(Billing::class)
            ->assertSee('Free Plan')
            ->assertSee('Unlock forever')
            ->assertSee(route('billing.checkout'), escape: false);
    });

    it('compares the two plans', function () {
        Livewire::actingAs(User::factory()->createOne())
            ->test(Billing::class)
            ->assertSee('Your current plan')
            ->assertSee(config('app.name').' Pro')
            ->assertSee('100 rankings')
            ->assertSee('Unlimited rankings')
            ->assertSee('once, no subscription');
    });

    it('does not report ranking usage — that card belongs to the dashboard', function () {
        Livewire::actingAs(userWithRankings(3))
            ->test(Billing::class)
            ->assertDontSee('Your Rankings')
            ->assertDontSee('3 of 100');
    });
});

describe('the Pro view', function () {
    it('names the plan and drops the upgrade call to action', function () {
        Livewire::actingAs(proUser())
            ->test(Billing::class)
            ->assertSee(config('app.name').' Pro')
            ->assertDontSee('Unlock forever')
            ->assertDontSee('Your current plan');
    });

    it('thanks the buyer rather than listing entitlements', function () {
        Livewire::actingAs(proUser())
            ->test(Billing::class)
            ->assertSee('Thanks for supporting '.config('app.name'))
            ->assertDontSee('of 100');
    });

    it('shows the licence id, amount and purchase date', function () {
        $user = User::factory()->createOne(['timezone' => 'America/New_York']);

        $license = ProLicense::factory()->active()->for($user)->create([
            'purchased_at' => now(),
        ]);

        Livewire::actingAs($user)
            ->test(Billing::class)
            ->assertSee($license->uuid)
            ->assertSee('$10.00')
            ->assertSee('Active');
    });

    it('breaks out tax when there was any', function () {
        $user = User::factory()->createOne();

        ProLicense::factory()->active()->withTax(210, 'DE')->for($user)->create();

        Livewire::actingAs($user)
            ->test(Billing::class)
            ->assertSee('Includes tax')
            ->assertSee('$2.10');
    });

    it('omits the tax row when none was charged', function () {
        Livewire::actingAs(proUser())
            ->test(Billing::class)
            ->assertDontSee('Includes tax');
    });

    it('links out to the Stripe invoice and PDF', function () {
        $user = User::factory()->createOne();
        $license = ProLicense::factory()->active()->for($user)->create();

        Livewire::actingAs($user)
            ->test(Billing::class)
            ->assertSee($license->hosted_invoice_url, escape: false)
            ->assertSee($license->invoice_pdf_url, escape: false);
    });

    it('never shows card details, because none are stored', function () {
        $user = User::factory()->createOne();
        ProLicense::factory()->active()->for($user)->create();

        Livewire::actingAs($user)
            ->test(Billing::class)
            ->assertDontSee('Payment method');
    });
});

describe('a complimentary licence', function () {
    it('renders without payment details or empty receipt buttons', function () {
        $user = User::factory()->createOne();
        ProLicense::factory()->comp()->for($user)->create();

        Livewire::actingAs($user)
            ->test(Billing::class)
            ->assertOk()
            ->assertSee('Complimentary')
            ->assertDontSee('Fetch receipt')
            ->assertDontSee('fa-file-pdf')
            ->assertDontSee('Payment method');
    });

    it('still counts as Pro', function () {
        $user = User::factory()->createOne();
        ProLicense::factory()->comp()->for($user)->create();

        expect($user->fresh()->is_pro)->toBeTrue();
    });
});

describe('a refunded licence', function () {
    it('is reported without granting Pro', function () {
        $user = User::factory()->createOne();
        ProLicense::factory()->refunded()->for($user)->create();

        Livewire::actingAs($user)
            ->test(Billing::class)
            ->assertSee('Free Plan')
            ->assertSee('Your Previous Purchase')
            ->assertSee('Refunded')
            ->assertSee('Unlock forever');
    });
});
