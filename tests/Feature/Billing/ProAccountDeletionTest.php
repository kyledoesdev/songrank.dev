<?php

use App\Enums\Billing\ProLicenseStatus;
use App\Jobs\DeleteUserJob;
use App\Livewire\Billing\Billing;
use App\Models\ProLicense;
use App\Models\User;
use Laravel\Pennant\Feature;
use Livewire\Livewire;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertSoftDeleted;

describe('deleting a Pro account', function () {
    it('keeps the licence as a financial record', function () {
        $user = User::factory()->createOne();
        $license = ProLicense::factory()->active()->for($user)->create();

        (new DeleteUserJob($user))->handle();

        assertDatabaseHas('pro_licenses', [
            'id' => $license->getKey(),
            'deleted_at' => null,
        ]);
    });

    it('detaches the licence from the deleted account', function () {
        $user = User::factory()->createOne();
        $license = ProLicense::factory()->active()->for($user)->create();

        (new DeleteUserJob($user))->handle();

        expect($license->fresh()->user_id)->toBeNull();
    });

    it('keeps the Stripe customer id so the record stays meaningful', function () {
        $user = User::factory()->createOne();
        $license = ProLicense::factory()->active()->for($user)->create();

        (new DeleteUserJob($user))->handle();

        expect($license->fresh()->stripe_customer_id)->not->toBeNull()
            ->and($license->fresh()->stripe_payment_intent_id)->not->toBeNull();
    });

    it('still soft deletes the user', function () {
        $user = User::factory()->createOne();
        ProLicense::factory()->active()->for($user)->create();

        (new DeleteUserJob($user))->handle();

        assertSoftDeleted('users', ['id' => $user->getKey()]);
    });

    it('does not hand Pro back to a restored account', function () {
        $user = User::factory()->createOne();
        ProLicense::factory()->active()->for($user)->create();

        (new DeleteUserJob($user))->handle();

        $user->restore();

        expect($user->fresh()->is_pro)->toBeFalse();
    });

    it('leaves a free user deletion unchanged', function () {
        $user = User::factory()->createOne();

        (new DeleteUserJob($user))->handle();

        assertSoftDeleted('users', ['id' => $user->getKey()]);
    });
});

describe('the settings warning', function () {
    it('tells a Pro user their licence goes with the account', function () {
        Feature::define('songrank-pro', true);

        Livewire::actingAs(proUser())
            ->test(Billing::class)
            ->assertSee('Deleting your account ends it');
    });

    it('says nothing of the sort to a free user', function () {
        Feature::define('songrank-pro', true);

        Livewire::actingAs(User::factory()->createOne())
            ->test(Billing::class)
            ->assertDontSee('Deleting your account ends it');
    });
});

describe('licence lifecycle after deletion', function () {
    it('keeps a detached licence out of anyone else billing page', function () {
        $deleted = User::factory()->createOne();
        ProLicense::factory()->active()->for($deleted)->create();
        (new DeleteUserJob($deleted))->handle();

        $other = User::factory()->createOne();

        expect($other->proLicenses()->forBillingPage()->first())->toBeNull()
            ->and($other->is_pro)->toBeFalse();
    });

    it('does not resurrect a refunded licence', function () {
        $user = User::factory()->createOne();
        $license = ProLicense::factory()->refunded()->for($user)->create();

        (new DeleteUserJob($user))->handle();

        expect($license->fresh()->status)->toBe(ProLicenseStatus::REFUNDED);
    });
});
