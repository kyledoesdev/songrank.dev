<?php

use App\Enums\TierlistType;
use App\Models\Tierlist;
use App\Models\User;

describe('the free allowance', function () {
    it('lets a new account make one list of every type', function () {
        $user = User::factory()->createOne();

        expect($user->canCreateTierlist(TierlistType::ARTIST))->toBeTrue()
            ->and($user->canCreateTierlist(TierlistType::ALBUM))->toBeTrue()
            ->and($user->canCreateTierlist(TierlistType::TRACK))->toBeTrue();
    });

    it('stops at the limit for that type', function () {
        $user = userWithTierlists(1, TierlistType::ARTIST);

        expect($user->canCreateTierlist(TierlistType::ARTIST))->toBeFalse();
    });

    it('counts each type on its own, so one spent allowance does not spend the others', function () {
        $user = userWithTierlists(1, TierlistType::ARTIST);

        expect($user->canCreateTierlist(TierlistType::ARTIST))->toBeFalse()
            ->and($user->canCreateTierlist(TierlistType::ALBUM))->toBeTrue()
            ->and($user->canCreateTierlist(TierlistType::TRACK))->toBeTrue();
    });

    it('caps how many entries one of its lists may hold', function () {
        expect(User::factory()->createOne()->tierlistItemLimit())->toBe(100);
    });
});

describe('the pro allowance', function () {
    it('has no limit on any type', function () {
        $user = proUser();

        expect($user->tierlistLimit(TierlistType::ARTIST))->toBeNull()
            ->and($user->tierlistLimit(TierlistType::ALBUM))->toBeNull()
            ->and($user->tierlistLimit(TierlistType::TRACK))->toBeNull();
    });

    it('keeps letting them make more once past what a free account could', function () {
        $user = proUser();

        Tierlist::factory()->count(5)->for($user)->create();

        expect($user->canCreateTierlist(TierlistType::ARTIST))->toBeTrue();
    });

    it('allows a bigger board', function () {
        expect(proUser()->tierlistItemLimit())->toBe(500);
    });
});

describe('counting', function () {
    it('counts only that user lists', function () {
        $user = userWithTierlists(1, TierlistType::ARTIST);
        Tierlist::factory()->count(3)->create();

        expect($user->tierlistCount(TierlistType::ARTIST))->toBe(1);
    });

    it('counts unfinished lists too, since they occupy a slot all the same', function () {
        $user = User::factory()->createOne();
        Tierlist::factory()->for($user)->create(['is_complete' => false]);

        expect($user->tierlistCount(TierlistType::ARTIST))->toBe(1)
            ->and($user->canCreateTierlist(TierlistType::ARTIST))->toBeFalse();
    });

    it('frees the slot again once a list is deleted', function () {
        $user = userWithTierlists(1, TierlistType::ARTIST);

        $user->tierlists()->first()->delete();

        expect($user->canCreateTierlist(TierlistType::ARTIST))->toBeTrue();
    });
});
