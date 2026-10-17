<?php

use App\Enums\Billing\ProLicenseSource;
use App\Enums\Billing\ProLicenseStatus;
use App\Filament\Resources\ProLicenses\Pages\ListProLicenses;
use App\Filament\Resources\ProLicenses\ProLicenseResource;
use App\Models\ProLicense;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Collection;
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

    it('grants a gift when that type is chosen', function () {
        $recipient = User::factory()->createOne();

        Livewire::test(ListProLicenses::class)
            ->callAction('grant', [
                'user_id' => $recipient->getKey(),
                'source' => ProLicenseSource::GIFT->value,
                'notes' => 'Gift from Shania for the charity stream.',
            ]);

        $license = ProLicense::query()->forUser($recipient)->first();

        expect($license->source)->toBe(ProLicenseSource::GIFT)
            ->and($license->status)->toBe(ProLicenseStatus::ACTIVE)
            ->and($license->notes)->toBe('Gift from Shania for the charity stream.')
            ->and($recipient->fresh()->is_pro)->toBeTrue();
    });

    it('defaults to complimentary when no type is picked', function () {
        $recipient = User::factory()->createOne();

        Livewire::test(ListProLicenses::class)
            ->callAction('grant', [
                'user_id' => $recipient->getKey(),
                'notes' => 'Beta tester.',
            ]);

        expect(ProLicense::query()->forUser($recipient)->first()->source)
            ->toBe(ProLicenseSource::COMP);
    });

    it('refuses to hand-grant a purchase, which only Stripe may write', function () {
        $recipient = User::factory()->createOne();

        Livewire::test(ListProLicenses::class)
            ->callAction('grant', [
                'user_id' => $recipient->getKey(),
                'source' => ProLicenseSource::PURCHASE->value,
                'notes' => 'Should never land.',
            ])
            ->assertHasActionErrors(['source']);

        assertDatabaseCount('pro_licenses', 0);
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

    it('finds the recipient by name, email or Spotify id', function (string $field) {
        $recipient = User::factory()->createOne(['name' => 'Findable Person', 'email' => 'findable@example.test']);
        User::factory()->createOne();

        $found = grantPickerResults($recipient->{$field});

        expect($found->pluck('value')->all())->toBe([(string) $recipient->getKey()]);
    })->with(['name', 'email', 'spotify_id']);

    it('shows each person\'s email, so two people with the same name can be told apart', function () {
        $first = User::factory()->createOne(['name' => 'Same Name']);
        $second = User::factory()->createOne(['name' => 'Same Name']);

        $labels = grantPickerResults('Same Name')->pluck('label', 'value');

        expect($labels[(string) $first->getKey()])->toContain($first->email)
            ->and($labels[(string) $second->getKey()])->toContain($second->email);
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
            ->filterTable('hide_refunded', false)
            ->assertActionHidden(TestAction::make('revoke')->table($license));
    });
});

describe('licences are not hand-editable', function () {
    it('offers no create, edit or delete route', function () {
        $license = ProLicense::factory()->active()->create();

        expect(ProLicenseResource::getPages())->not->toHaveKeys(['create', 'edit']);

        Livewire::test(ListProLicenses::class)
            ->assertActionDoesNotExist('create')
            ->assertActionDoesNotExist(TestAction::make('edit')->table($license))
            ->assertActionDoesNotExist(TestAction::make('delete')->table($license));
    });
});

/**
 * What the grant form's user picker offers for a search, as the browser receives it.
 *
 * @return Collection<int, array{label: string, value: string}>
 */
function grantPickerResults(string $search): Collection
{
    $page = Livewire::test(ListProLicenses::class)->mountAction('grant');

    return collect($page->instance()->callSchemaComponentMethod('mountedActionSchema0.user_id', 'getSearchResultsForJs', ['search' => $search]));
}
