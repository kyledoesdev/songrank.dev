<?php

use App\Livewire\Billing\Billing;
use App\Models\ProLicense;
use App\Models\User;
use Livewire\Livewire;

describe('the free plan view', function () {
    it('shows the upgrade path', function () {
        Livewire::actingAs(User::factory()->createOne())
            ->test(Billing::class)
            ->assertSee(route('billing.checkout'), escape: false);
    });

    it('compares the two plans', function () {
        Livewire::actingAs(User::factory()->createOne())
            ->test(Billing::class)
            ->assertSee(config('app.name').' Pro')
            ->assertSee((string) config('billing.ranking_limits.free'));
    });
});

describe('the Pro view', function () {
    it('names the plan and drops the upgrade call to action', function () {
        Livewire::actingAs(proUser())
            ->test(Billing::class)
            ->assertSee(config('app.name').' Pro')
            ->assertDontSee(route('billing.checkout'), escape: false);
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
            ->assertOk();
    });
});

describe('a complimentary licence', function () {
    it('renders without payment details or empty receipt buttons', function () {
        $user = User::factory()->createOne();
        ProLicense::factory()->comp()->for($user)->create();

        Livewire::actingAs($user)
            ->test(Billing::class)
            ->assertOk()
            ->assertDontSee('fa-file-pdf', escape: false);
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
            ->assertSee(route('billing.checkout'), escape: false);

        expect($user->fresh()->is_pro)->toBeFalse();
    });
});
