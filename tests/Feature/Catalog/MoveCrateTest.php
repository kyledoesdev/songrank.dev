<?php

use App\Actions\Catalog\MoveCrate;
use App\Livewire\Catalog\CrateSettings;
use App\Models\Crate;
use App\Models\User;
use Livewire\Livewire;

describe('moving a crate', function () {
    it('lifts a nested crate up to the top level', function () {
        $user = proUser();
        $parent = Crate::factory()->for($user)->create();
        $nested = Crate::factory()->nestedIn($parent)->create();

        expect((new MoveCrate)->handle($user, $nested, null))->toBeTrue()
            ->and($nested->fresh())
            ->parent_id->toBeNull()
            ->depth->toBe(0)
            ->position->toBe(1)
            ->and($parent->fresh()->children)->toBeEmpty();
    });

    it('drops a top-level crate inside another', function () {
        $user = proUser();
        [$parent, $crate] = Crate::factory()->count(2)->for($user)->create();

        expect((new MoveCrate)->handle($user, $crate, $parent))->toBeTrue()
            ->and($crate->fresh())
            ->parent_id->toBe($parent->getKey())
            ->depth->toBe(1)
            ->position->toBe(0);
    });

    it('joins the end of its new list', function () {
        $user = proUser();
        [$crate, $parent] = Crate::factory()->count(2)->for($user)->create();
        Crate::factory()->count(2)->nestedIn($parent)->create();

        (new MoveCrate)->handle($user, $crate, $parent);

        expect($crate->fresh()->position)->toBe(2);
    });

    it('closes the gap it leaves behind', function () {
        $user = proUser();
        [$crate, $second, $parent] = Crate::factory()->count(3)->for($user)->create();

        (new MoveCrate)->handle($user, $crate, $parent);

        expect($second->fresh()->position)->toBe(0)
            ->and($parent->fresh()->position)->toBe(1);
    });

    it('treats a move to where it already is as done', function () {
        $user = proUser();
        $crate = Crate::factory()->for($user)->create();

        expect((new MoveCrate)->handle($user, $crate, null))->toBeTrue()
            ->and($crate->fresh()->position)->toBe(0);
    });
});

describe('moving a crate with crates inside', function () {
    beforeEach(function () {
        config(['billing.catalog_limits.pro.depth' => 3]);
    });

    it('brings everything inside along, a level deeper', function () {
        $user = proUser();
        $target = Crate::factory()->for($user)->create();
        $crate = Crate::factory()->for($user)->create();
        $nested = Crate::factory()->nestedIn($crate)->create();

        (new MoveCrate)->handle($user, $crate, $target);

        expect($crate->fresh()->depth)->toBe(1)
            ->and($nested->fresh())
            ->depth->toBe(2)
            ->parent_id->toBe($crate->getKey());
    });

    it('brings everything inside back up a level', function () {
        $user = proUser();
        $top = Crate::factory()->for($user)->create();
        $crate = Crate::factory()->nestedIn($top)->create();
        $nested = Crate::factory()->nestedIn($crate)->create();

        (new MoveCrate)->handle($user, $crate, null);

        expect($crate->fresh()->depth)->toBe(0)
            ->and($nested->fresh()->depth)->toBe(1);
    });
});

describe('moves that are refused', function () {
    it('will not put a crate inside itself', function () {
        $user = proUser();
        $crate = Crate::factory()->for($user)->create();

        expect((new MoveCrate)->handle($user, $crate, $crate))->toBeFalse()
            ->and($crate->fresh()->parent_id)->toBeNull();
    });

    it('will not put a crate inside one of its own', function () {
        $user = proUser();
        $crate = Crate::factory()->for($user)->create();
        $nested = Crate::factory()->nestedIn($crate)->create();

        expect((new MoveCrate)->handle($user, $crate, $nested))->toBeFalse()
            ->and($crate->fresh()->parent_id)->toBeNull();
    });

    it('will not push anything past the depth limit', function () {
        $user = proUser();
        $target = Crate::factory()->for($user)->create();
        $crate = Crate::factory()->for($user)->create();
        $nested = Crate::factory()->nestedIn($crate)->create();

        expect((new MoveCrate)->handle($user, $crate, $target))->toBeFalse()
            ->and($crate->fresh()->parent_id)->toBeNull()
            ->and($nested->fresh()->depth)->toBe(1);
    });
});

describe('moving from the settings popover', function () {
    it('moves a crate inside another', function () {
        $user = proUser();
        [$target, $crate] = Crate::factory()->count(2)->for($user)->create();

        Livewire::actingAs($user)
            ->test(CrateSettings::class, ['crate' => $crate])
            ->call('openSettings')
            ->set('destination', (string) $target->getKey())
            ->call('move')
            ->assertSet('open', false)
            ->assertDispatched('crates-updated');

        expect($crate->fresh()->parent_id)->toBe($target->getKey());
    });

    it('lifts a crate up to the top level', function () {
        $user = proUser();
        $crate = Crate::factory()->nestedIn(Crate::factory()->for($user)->create())->create();

        Livewire::actingAs($user)
            ->test(CrateSettings::class, ['crate' => $crate])
            ->set('destination', 'top')
            ->call('move');

        expect($crate->fresh()->parent_id)->toBeNull();
    });

    it('offers only the places it could actually go', function () {
        $user = proUser();
        $crate = Crate::factory()->for($user)->create();
        $open = Crate::factory()->for($user)->create();
        Crate::factory()->nestedIn($open)->create();
        Crate::factory()->create();

        $destinations = Livewire::actingAs($user)
            ->test(CrateSettings::class, ['crate' => $crate])
            ->instance()
            ->destinations;

        expect($destinations->modelKeys())->toBe([$open->getKey()]);
    });

    it('offers nowhere to a crate holding crates of its own', function () {
        $user = proUser();
        $crate = Crate::factory()->for($user)->create();
        Crate::factory()->nestedIn($crate)->create();
        Crate::factory()->for($user)->create();

        $destinations = Livewire::actingAs($user)
            ->test(CrateSettings::class, ['crate' => $crate])
            ->instance()
            ->destinations;

        expect($destinations)->toBeEmpty();
    });

    it('needs somewhere to go', function () {
        $user = proUser();
        $crate = Crate::factory()->for($user)->create();

        Livewire::actingAs($user)
            ->test(CrateSettings::class, ['crate' => $crate])
            ->call('move')
            ->assertHasErrors(['destination' => 'required']);
    });

    it('says so when a move cannot happen', function () {
        $user = proUser();
        $crate = Crate::factory()->for($user)->create();
        $nested = Crate::factory()->nestedIn($crate)->create();

        $component = Livewire::actingAs($user)
            ->test(CrateSettings::class, ['crate' => $crate])
            ->set('destination', (string) $nested->getKey())
            ->call('move');

        expect(alertsFrom($component)->pluck('icon')->all())->toBe(['error'])
            ->and($crate->fresh()->parent_id)->toBeNull();
    });

    it('will not move into someone else\'s crate', function () {
        $user = proUser();
        $crate = Crate::factory()->for($user)->create();

        Livewire::actingAs($user)
            ->test(CrateSettings::class, ['crate' => $crate])
            ->set('destination', (string) Crate::factory()->create()->getKey())
            ->call('move')
            ->assertNotFound();
    });

    it('is Pro only', function () {
        $user = User::factory()->createOne();
        [$target, $crate] = Crate::factory()->count(2)->for($user)->create();

        Livewire::actingAs($user)
            ->test(CrateSettings::class, ['crate' => $crate])
            ->set('destination', (string) $target->getKey())
            ->call('move')
            ->assertForbidden();
    });

    it('is not offered to a free account', function () {
        $crate = Crate::factory()->create();

        Livewire::actingAs($crate->user)
            ->test(CrateSettings::class, ['crate' => $crate])
            ->call('openSettings')
            ->assertDontSee('wire:click="move"', escape: false);
    });
});
