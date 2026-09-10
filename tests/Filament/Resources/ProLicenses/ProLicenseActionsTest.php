<?php

use App\Enums\Billing\ProLicenseSource;
use App\Enums\Billing\ProLicenseStatus;
use App\Filament\Resources\ProLicenses\Pages\ListProLicenses;
use App\Models\ProLicense;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;

beforeEach(fn () => actingAs(kyle()));

describe('granting a licence', function () {
    it('gives the user Pro with no payment behind it', function () {
        $recipient = User::factory()->createOne();

        Livewire::test(ListProLicenses::class)
            ->callAction('grant', [
                'user_id' => $recipient->getKey(),
                'notes' => 'Beta tester for tier lists.',
            ]);

        $license = ProLicense::query()->forUser($recipient)->first();

        expect($license->status)->toBe(ProLicenseStatus::ACTIVE)
            ->and($license->source)->toBe(ProLicenseSource::COMP)
            ->and($license->amount_total)->toBeNull()
            ->and($recipient->fresh()->is_pro)->toBeTrue();
    });

    it('records why it was granted', function () {
        $recipient = User::factory()->createOne();

        Livewire::test(ListProLicenses::class)
            ->callAction('grant', [
                'user_id' => $recipient->getKey(),
                'notes' => 'Support gesture.',
            ]);

        $license = ProLicense::query()->forUser($recipient)->first();

        expect($license->notes)->toBe('Support gesture.');
    });

    it('insists on a reason', function () {
        Livewire::test(ListProLicenses::class)
            ->callAction('grant', [
                'user_id' => User::factory()->createOne()->getKey(),
                'notes' => '',
            ])
            ->assertHasActionErrors(['notes' => 'required']);

        assertDatabaseCount('pro_licenses', 0);
    });
});

describe('revoking a licence', function () {
    it('ends access and records the reason', function () {
        $license = ProLicense::factory()->active()->create();

        Livewire::test(ListProLicenses::class)
            ->callAction(TestAction::make('revoke')->table($license), [
                'notes' => 'Abuse of the comment system.',
            ]);

        $license->refresh();

        expect($license->status)->toBe(ProLicenseStatus::REVOKED)
            ->and($license->revoked_at)->not->toBeNull()
            ->and($license->notes)->toBe('Abuse of the comment system.')
            ->and($license->user->fresh()->is_pro)->toBeFalse();
    });

    it('insists on a reason', function () {
        $license = ProLicense::factory()->active()->create();

        Livewire::test(ListProLicenses::class)
            ->callAction(TestAction::make('revoke')->table($license), ['notes' => ''])
            ->assertHasActionErrors(['notes' => 'required']);

        expect($license->fresh()->status)->toBe(ProLicenseStatus::ACTIVE);
    });

    it('leaves the Stripe trail untouched so refunds stay a Stripe decision', function () {
        $license = ProLicense::factory()->active()->create();
        $paymentIntent = $license->stripe_payment_intent_id;

        Livewire::test(ListProLicenses::class)
            ->callAction(TestAction::make('revoke')->table($license), [
                'notes' => 'Revoked without refund.',
            ]);

        $license->refresh();

        expect($license->stripe_payment_intent_id)->toBe($paymentIntent)
            ->and($license->amount_refunded)->toBe(0)
            ->and($license->refunded_at)->toBeNull();
    });

    it('is not offered on a licence that is already inactive', function () {
        $license = ProLicense::factory()->refunded()->create();

        Livewire::test(ListProLicenses::class)
            ->assertActionHidden(TestAction::make('revoke')->table($license));
    });
});

describe('licences are not hand-editable', function () {
    it('offers no create, edit or delete route', function () {
        expect(ProLicense::query()->count())->toBe(0);

        Livewire::test(ListProLicenses::class)
            ->assertDontSee('New Pro Licence');
    });
});
