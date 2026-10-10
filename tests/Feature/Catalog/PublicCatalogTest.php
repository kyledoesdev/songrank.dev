<?php

use App\Livewire\Catalog\CatalogModal;
use App\Livewire\Profile\Settings;
use App\Models\Crate;
use App\Models\CrateItem;
use App\Models\Ranking;
use App\Models\Review;
use App\Models\Tierlist;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

describe('choosing who sees the catalog', function () {
    it('keeps a catalog private to begin with', function () {
        expect(User::factory()->createOne()->hasPublicCatalog())->toBeFalse();
    });

    it('opens it up from settings', function () {
        $user = User::factory()->createOne();

        Livewire::actingAs($user)
            ->test(Settings::class)
            ->call('updateSetting', 'catalog_is_public', true);

        expect($user->fresh()->hasPublicCatalog())->toBeTrue();
    });
});

describe('the profile', function () {
    it('offers a public catalog to anyone, signed in or not', function () {
        $owner = catalogOwner(public: true);

        get(route('profile', ['id' => $owner->spotify_id]))->assertSeeLivewire(CatalogModal::class);

        actingAs(User::factory()->createOne());

        get(route('profile', ['id' => $owner->spotify_id]))->assertSeeLivewire(CatalogModal::class);
    });

    it('keeps a private catalog off the profile, its owner\'s view included', function () {
        $owner = catalogOwner(public: false);

        actingAs($owner);

        get(route('profile', ['id' => $owner->spotify_id]))->assertDontSeeLivewire(CatalogModal::class);
    });
});

describe('browsing a catalog', function () {
    it('will not open a private catalog for anyone else', function () {
        Livewire::actingAs(User::factory()->createOne())
            ->test(CatalogModal::class, ['user' => catalogOwner(public: false)])
            ->assertNotFound();
    });

    it('opens on the top level', function () {
        $owner = catalogOwner(public: true);
        $crate = Crate::factory()->for($owner)->create();
        Crate::factory()->nestedIn($crate)->create();
        Crate::factory()->create();

        $crates = Livewire::test(CatalogModal::class, ['user' => $owner])->instance()->crates;

        expect($crates->modelKeys())->toBe([$crate->getKey()]);
    });

    it('steps into a crate and back out again', function () {
        $owner = catalogOwner(public: true);
        $parent = Crate::factory()->for($owner)->create();
        $nested = Crate::factory()->nestedIn($parent)->create();

        $component = Livewire::test(CatalogModal::class, ['user' => $owner])
            ->call('showCrate', $parent->getKey());

        expect($component->instance()->crate->is($parent))->toBeTrue()
            ->and($component->instance()->crates->modelKeys())->toBe([$nested->getKey()]);

        $component->call('showTopLevel');

        expect($component->instance()->crate)->toBeNull()
            ->and($component->instance()->crates->modelKeys())->toBe([$parent->getKey()]);
    });

    it('shows a visitor only what they could open anyway', function () {
        [$owner, $crate, $public] = crateOfMixedRecords();

        $component = Livewire::test(CatalogModal::class, ['user' => $owner]);

        expect($component->instance()->crates->sole()->items_count)->toBe(1);

        $component
            ->call('showCrate', $crate->getKey())
            ->assertSee($public->url(), escape: false);

        expect($component->instance()->items->pluck('entity_id')->all())->toBe([$public->getKey()]);
    });

    it('shows its owner everything', function () {
        [$owner, $crate] = crateOfMixedRecords();

        $component = Livewire::actingAs($owner)->test(CatalogModal::class, ['user' => $owner]);

        expect($component->instance()->crates->sole()->items_count)->toBe(4);

        $component->call('showCrate', $crate->getKey());

        expect($component->instance()->items)->toHaveCount(4);
    });

    it('will not step into a crate from another catalog', function () {
        Livewire::test(CatalogModal::class, ['user' => catalogOwner(public: true)])
            ->call('showCrate', Crate::factory()->create()->getKey())
            ->assertNotFound();
    });

    it('stops opening crates once the catalog goes private', function () {
        $owner = catalogOwner(public: true);
        $crate = Crate::factory()->for($owner)->create();

        $component = Livewire::actingAs(User::factory()->createOne())->test(CatalogModal::class, ['user' => $owner]);

        $owner->preferences->update(['catalog_is_public' => false]);

        $component->call('showCrate', $crate->getKey())->assertNotFound();
    });
});

describe('where a record lives', function () {
    it('links each record to its own page', function () {
        $ranking = Ranking::factory()->create();
        $tierlist = Tierlist::factory()->create();
        $review = Review::factory()->create();

        expect($ranking->url())->toBe(route('ranking', ['id' => $ranking->getKey()]))
            ->and($tierlist->url())->toBe(route('tierlist', ['id' => $tierlist->getKey()]))
            ->and($review->url())->toBe(route('review', ['id' => $review->getKey()]));
    });
});

/**
 * A Pro account, so its catalog can nest, with the catalog set either way.
 */
function catalogOwner(bool $public): User
{
    $user = proUser();

    $user->preferences->update(['catalog_is_public' => $public]);

    return $user->fresh();
}

/**
 * A public catalog's crate holding one public, finished ranking alongside an
 * unfinished ranking, a draft review and a finished but private tier list.
 *
 * @return array{0: User, 1: Crate, 2: Ranking}
 */
function crateOfMixedRecords(): array
{
    $owner = catalogOwner(public: true);
    $crate = Crate::factory()->for($owner)->create();
    $public = Ranking::factory()->for($owner)->create(['is_public' => true, 'is_ranked' => true, 'completed_at' => now()]);

    CrateItem::factory()->for($crate)->forRanking($public)->create();
    CrateItem::factory()->for($crate)->forRanking()->create();
    CrateItem::factory()->for($crate)->forReview()->create();
    CrateItem::factory()->for($crate)->forTierlist(Tierlist::factory()->for($owner)->complete()->create())->create();

    return [$owner, $crate, $public];
}
