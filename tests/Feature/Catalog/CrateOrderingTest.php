<?php

use App\Actions\Catalog\MoveCrateItem;
use App\Actions\Catalog\RemoveCrateItem;
use App\Actions\Catalog\ReorderCrate;
use App\Actions\Catalog\UpdateCatalogSort;
use App\Enums\CatalogSort;
use App\Livewire\Catalog\Catalog;
use App\Livewire\Catalog\CrateShow;
use App\Models\Crate;
use App\Models\CrateItem;
use App\Models\ProLicense;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

describe('ordering items', function () {
    it('moves an item up its crate', function () {
        [$crate, $first, $second, $third] = crateOfThree();

        (new MoveCrateItem)->handle($crate, ['item_id' => $third->getKey(), 'position' => 0]);

        expect(crateContents($crate))->toBe([crateEntry($third->entity), crateEntry($first->entity), crateEntry($second->entity)])
            ->and($crate->items()->pluck('position')->all())->toBe([0, 1, 2]);
    });

    it('moves an item down its crate', function () {
        [$crate, $first, $second, $third] = crateOfThree();

        (new MoveCrateItem)->handle($crate, ['item_id' => $first->getKey(), 'position' => 2]);

        expect(crateContents($crate))->toBe([crateEntry($second->entity), crateEntry($third->entity), crateEntry($first->entity)])
            ->and($crate->items()->pluck('position')->all())->toBe([0, 1, 2]);
    });

    it('treats a slot past the end as the end', function () {
        [$crate, $first, $second, $third] = crateOfThree();

        (new MoveCrateItem)->handle($crate, ['item_id' => $first->getKey(), 'position' => 99]);

        expect($first->fresh()->position)->toBe(2);
    });

    it('is open to a free account', function () {
        [$crate, $first] = crateOfThree();

        expect($crate->user->is_pro)->toBeFalse();

        (new MoveCrateItem)->handle($crate, ['item_id' => $first->getKey(), 'position' => 1]);

        expect($first->fresh()->position)->toBe(1);
    });

    it('only moves items inside the crate it was given', function () {
        [$crate] = crateOfThree();

        (new MoveCrateItem)->handle($crate, ['item_id' => CrateItem::factory()->create()->getKey(), 'position' => 0]);
    })->throws(ModelNotFoundException::class);

    it('closes the gap a removed item leaves', function () {
        [$crate, $first, $second, $third] = crateOfThree();

        (new RemoveCrateItem)->handle($second);

        expect(crateContents($crate))->toBe([crateEntry($first->entity), crateEntry($third->entity)])
            ->and($crate->items()->pluck('position')->all())->toBe([0, 1]);
    });
});

describe('ordering crates by hand', function () {
    it('moves a crate to another slot in its list', function () {
        $user = proUser();
        $crates = cratesNamed($user, ['Torches', 'Supermodel', 'Paradise State of Mind']);

        (new ReorderCrate)->handle($user, $crates['Paradise State of Mind'], 0);

        expect(crateNamesShown($user))->toBe(['Paradise State of Mind', 'Torches', 'Supermodel']);
    });

    it('switches the account to a custom order', function () {
        $user = proUser();
        $crates = cratesNamed($user, ['Torches', 'Supermodel']);

        (new ReorderCrate)->handle($user, $crates['Supermodel'], 0);

        expect($user->fresh()->catalogSort())->toBe(CatalogSort::CUSTOM);
    });

    it('only shifts its own list', function () {
        $user = proUser();
        $crates = cratesNamed($user, ['Torches', 'Supermodel']);
        $nested = Crate::factory()->count(2)->nestedIn($crates['Torches'])->create();

        (new ReorderCrate)->handle($user, $crates['Supermodel'], 0);

        expect($nested->fresh()->pluck('position')->all())->toBe([0, 1]);
    });
});

describe('the order crates are shown in', function () {
    it('is alphabetical by default', function () {
        $user = proUser();
        cratesNamed($user, ['Torches', 'Supermodel', 'Paradise State of Mind']);

        expect($user->catalogSort())->toBe(CatalogSort::ALPHABETICAL)
            ->and(crateNamesShown($user))->toBe(['Paradise State of Mind', 'Supermodel', 'Torches']);
    });

    it('follows the positions once a Pro account picks a custom order', function () {
        $user = proUser();
        cratesNamed($user, ['Torches', 'Supermodel', 'Paradise State of Mind']);

        $user->preferences()->update(['catalog_sort' => CatalogSort::CUSTOM]);

        expect(crateNamesShown($user))->toBe(['Torches', 'Supermodel', 'Paradise State of Mind']);
    });

    it('goes back to alphabetical when a Pro account switches back', function () {
        $user = proUser();
        $crates = cratesNamed($user, ['Torches', 'Supermodel']);
        (new ReorderCrate)->handle($user, $crates['Torches'], 1);

        (new UpdateCatalogSort)->handle($user, CatalogSort::ALPHABETICAL);

        expect(crateNamesShown($user))->toBe(['Supermodel', 'Torches'])
            ->and($crates['Torches']->fresh()->position)->toBe(1);
    });

    it('is always alphabetical on a free account', function () {
        $user = User::factory()->createOne();
        cratesNamed($user, ['Torches', 'Supermodel']);

        $user->preferences()->update(['catalog_sort' => CatalogSort::CUSTOM]);

        expect($user->fresh()->catalogSort())->toBe(CatalogSort::ALPHABETICAL)
            ->and(crateNamesShown($user))->toBe(['Supermodel', 'Torches']);
    });

    it('falls back to alphabetical when Pro is revoked, and keeps the custom order for its return', function () {
        $user = proUser();
        $crates = cratesNamed($user, ['Torches', 'Supermodel', 'Paradise State of Mind']);
        (new ReorderCrate)->handle($user, $crates['Torches'], 2);

        $user = revokePro($user);

        expect(crateNamesShown($user))->toBe(['Paradise State of Mind', 'Supermodel', 'Torches']);

        ProLicense::factory()->active()->for($user)->create();

        expect(crateNamesShown($user))->toBe(['Supermodel', 'Paradise State of Mind', 'Torches']);
    });
});

describe('dragging on the page', function () {
    it('drags items into order on a free account', function () {
        [$crate, $first, $second, $third] = crateOfThree();

        Livewire::actingAs($crate->user)
            ->test(CrateShow::class, ['id' => $crate->getKey()])
            ->call('moveItem', $third->getKey(), 0);

        expect(crateContents($crate))->toBe([crateEntry($third->entity), crateEntry($first->entity), crateEntry($second->entity)]);
    });

    it('will not drag an item from another crate', function () {
        [$crate] = crateOfThree();

        Livewire::actingAs($crate->user)
            ->test(CrateShow::class, ['id' => $crate->getKey()])
            ->call('moveItem', CrateItem::factory()->create()->getKey(), 0)
            ->assertNotFound();
    });

    it('drags crates into order on a Pro account', function () {
        $user = proUser();
        $crates = cratesNamed($user, ['Torches', 'Supermodel']);

        Livewire::actingAs($user)
            ->test(Catalog::class)
            ->call('reorderCrate', $crates['Supermodel']->getKey(), 0);

        expect(crateNamesShown($user))->toBe(['Supermodel', 'Torches']);
    });

    it('will not drag crates on a free account', function () {
        $user = User::factory()->createOne();
        $crates = cratesNamed($user, ['Torches', 'Supermodel']);

        Livewire::actingAs($user)
            ->test(Catalog::class)
            ->call('reorderCrate', $crates['Supermodel']->getKey(), 0)
            ->assertForbidden();
    });

    it('only drags crates within the list on the page', function () {
        $user = proUser();
        $crates = cratesNamed($user, ['Torches']);
        $nested = Crate::factory()->nestedIn($crates['Torches'])->create();

        Livewire::actingAs($user)
            ->test(Catalog::class)
            ->call('reorderCrate', $nested->getKey(), 0)
            ->assertNotFound();
    });

    it('drags the crates inside a crate on its page', function () {
        $user = proUser();
        $parent = Crate::factory()->for($user)->create();
        [$first, $second] = Crate::factory()->count(2)->nestedIn($parent)->create();

        Livewire::actingAs($user)
            ->test(CrateShow::class, ['id' => $parent->getKey()])
            ->call('reorderCrate', $second->getKey(), 0);

        expect($second->fresh()->position)->toBe(0)
            ->and($first->fresh()->position)->toBe(1);
    });

    it('only offers drag handles on crates in a custom order', function () {
        $user = proUser();
        cratesNamed($user, ['Torches']);

        Livewire::actingAs($user)
            ->test(Catalog::class)
            ->assertDontSeeHtml('wire:sort:handle')
            ->call('sortBy', CatalogSort::CUSTOM->value)
            ->assertSeeHtml('wire:sort:handle');
    });
});

describe('the sort switch', function () {
    it('switches a Pro account between orders', function () {
        $user = proUser();

        Livewire::actingAs($user)
            ->test(Catalog::class)
            ->call('sortBy', CatalogSort::CUSTOM->value);

        expect($user->fresh()->catalogSort())->toBe(CatalogSort::CUSTOM);
    });

    it('is not there for a free account', function () {
        Livewire::actingAs(User::factory()->createOne())
            ->test(Catalog::class)
            ->assertDontSee('wire:click="sortBy(', escape: false)
            ->call('sortBy', CatalogSort::CUSTOM->value)
            ->assertForbidden();
    });
});

/**
 * A crate holding a ranking, a tier list and a review, in that order.
 *
 * @return array{0: Crate, 1: CrateItem, 2: CrateItem, 3: CrateItem}
 */
function crateOfThree(): array
{
    $crate = Crate::factory()->create();

    return [
        $crate,
        CrateItem::factory()->for($crate)->forRanking()->create(),
        CrateItem::factory()->for($crate)->forTierlist()->create(),
        CrateItem::factory()->for($crate)->forReview()->create(),
    ];
}

/**
 * Top-level crates made in the order named, keyed by name.
 *
 * @param  list<string>  $names
 * @return array<string, Crate>
 */
function cratesNamed(User $user, array $names): array
{
    return collect($names)
        ->mapWithKeys(fn (string $name) => [$name => Crate::factory()->for($user)->create(['name' => $name])])
        ->all();
}
