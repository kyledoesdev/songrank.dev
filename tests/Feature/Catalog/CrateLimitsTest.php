<?php

use App\Actions\Catalog\StoreCrate;
use App\Models\Crate;
use App\Models\User;

describe('the free allowance', function () {
    it('allows five crates', function () {
        expect(User::factory()->createOne()->crateLimit())->toBe(5)
            ->and(userWithCrates(4)->canCreateCrate())->toBeTrue()
            ->and(userWithCrates(5)->canCreateCrate())->toBeFalse();
    });

    it('refuses a sixth crate', function () {
        $user = userWithCrates(5);

        expect((new StoreCrate)->handle($user, ['name' => 'One Too Many']))->toBeNull()
            ->and($user->crates()->count())->toBe(5);
    });

    it('counts nested crates against the same five', function () {
        $user = User::factory()->createOne();
        $parent = Crate::factory()->for($user)->create();
        Crate::factory()->count(4)->nestedIn($parent)->create();

        expect($user->canCreateCrate())->toBeFalse();
    });

    it('frees a slot when a crate is deleted', function () {
        $user = userWithCrates(5);

        $user->crates()->first()->delete();

        expect($user->canCreateCrate())->toBeTrue();
    });

    it('only counts that user\'s crates', function () {
        Crate::factory()->count(5)->create();

        expect(User::factory()->createOne()->canCreateCrate())->toBeTrue();
    });

    it('follows the billing config', function () {
        config(['billing.catalog_limits.free.folders' => 8]);

        expect(userWithCrates(7)->canCreateCrate())->toBeTrue();
    });
});

describe('nesting on a free account', function () {
    it('is not allowed at all', function () {
        $user = User::factory()->createOne();
        $parent = Crate::factory()->for($user)->create();

        expect($user->crateDepthLimit())->toBe(1)
            ->and($user->canNestIn($parent))->toBeFalse()
            ->and((new StoreCrate)->handle($user, ['name' => 'Nested'], $parent))->toBeNull()
            ->and($parent->children)->toBeEmpty();
    });
});

describe('the pro allowance', function () {
    it('has no limit on crates', function () {
        $user = proUser();
        Crate::factory()->count(6)->for($user)->create();

        expect($user->crateLimit())->toBeNull()
            ->and($user->canCreateCrate())->toBeTrue();
    });

    it('nests one level deep', function () {
        $user = proUser();
        $parent = Crate::factory()->for($user)->create();

        expect($user->canNestIn($parent))->toBeTrue()
            ->and((new StoreCrate)->handle($user, ['name' => 'Nested'], $parent))->not->toBeNull();
    });

    it('does not nest two levels deep', function () {
        $user = proUser();
        $nested = Crate::factory()->nestedIn(Crate::factory()->for($user)->create())->create();

        expect($user->canNestIn($nested))->toBeFalse()
            ->and((new StoreCrate)->handle($user, ['name' => 'Too Deep'], $nested))->toBeNull();
    });

    it('never nests inside someone else\'s crate', function () {
        $user = proUser();
        $theirs = Crate::factory()->create();

        expect($user->canNestIn($theirs))->toBeFalse()
            ->and((new StoreCrate)->handle($user, ['name' => 'Intruder'], $theirs))->toBeNull();
    });
});

describe('losing pro', function () {
    it('keeps every crate exactly where it was', function () {
        $user = proUser();
        $parent = Crate::factory()->for($user)->create();
        Crate::factory()->count(6)->for($user)->create();
        $nested = Crate::factory()->nestedIn($parent)->create();

        $user = revokePro($user);

        expect($user->crates()->count())->toBe(8)
            ->and($nested->fresh()->parent_id)->toBe($parent->getKey());
    });

    it('stops new crates until the account is back under the limit', function () {
        $user = proUser();
        Crate::factory()->count(6)->for($user)->create();

        $user = revokePro($user);

        expect($user->canCreateCrate())->toBeFalse()
            ->and((new StoreCrate)->handle($user, ['name' => 'Over the Limit']))->toBeNull();
    });

    it('stops new nested crates', function () {
        $user = proUser();
        $parent = Crate::factory()->for($user)->create();

        $user = revokePro($user);

        expect($user->canNestIn($parent))->toBeFalse();
    });
});
