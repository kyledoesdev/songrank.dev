<?php

use App\Actions\Billing\GrantProLicense;
use App\Actions\Billing\RevokeProLicense;
use App\Enums\Billing\ProLicenseSource;
use App\Enums\Billing\ProLicenseStatus;
use App\Models\ProLicense;
use App\Models\User;

use function Pest\Laravel\assertDatabaseHas;

describe('the is_pro projection', function () {
    it('starts false', function () {
        expect(User::factory()->createOne()->is_pro)->toBeFalse();
    });

    it('flips on when an active licence appears', function () {
        $user = User::factory()->createOne();

        ProLicense::factory()->active()->for($user)->create();

        assertDatabaseHas('users', ['id' => $user->getKey(), 'is_pro' => true]);
    });

    it('stays off for a licence that never completed', function () {
        $user = User::factory()->createOne();

        ProLicense::factory()->pending()->for($user)->create();

        assertDatabaseHas('users', ['id' => $user->getKey(), 'is_pro' => false]);
    });

    it('flips off again when the licence is revoked', function () {
        $user = User::factory()->createOne();
        $licence = ProLicense::factory()->active()->for($user)->create();

        app(RevokeProLicense::class)->handle($licence, ProLicenseStatus::REVOKED);

        assertDatabaseHas('users', ['id' => $user->getKey(), 'is_pro' => false]);
    });

    it('flips off when the licence is deleted', function () {
        $user = User::factory()->createOne();
        $licence = ProLicense::factory()->active()->for($user)->create();

        $licence->delete();

        assertDatabaseHas('users', ['id' => $user->getKey(), 'is_pro' => false]);
    });

    it('stays on while any one licence is still active', function () {
        $user = User::factory()->createOne();
        ProLicense::factory()->refunded()->for($user)->create();
        ProLicense::factory()->active()->for($user)->create();

        assertDatabaseHas('users', ['id' => $user->getKey(), 'is_pro' => true]);
    });

    it('follows a licence reassigned to another user', function () {
        $original = User::factory()->createOne();
        $recipient = User::factory()->createOne();
        $licence = ProLicense::factory()->active()->for($original)->create();

        $licence->update(['user_id' => $recipient->getKey()]);

        assertDatabaseHas('users', ['id' => $original->getKey(), 'is_pro' => false]);
        assertDatabaseHas('users', ['id' => $recipient->getKey(), 'is_pro' => true]);
    });

    it('is what isPro() reads', function () {
        $user = User::factory()->createOne();

        expect($user->is_pro)->toBeFalse();

        app(GrantProLicense::class)->handle($user, ProLicenseSource::PURCHASE);

        expect($user->fresh()->is_pro)->toBeTrue();
    });
});
