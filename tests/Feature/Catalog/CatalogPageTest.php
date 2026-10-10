<?php

use App\Livewire\Catalog\Catalog;
use App\Livewire\Catalog\CrateShow;
use App\Models\Artist;
use App\Models\Crate;
use App\Models\CrateItem;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

describe('who can open the catalog', function () {
    it('sends guests away', function () {
        get(route('catalog'))->assertRedirect(route('welcome'));
    });

    it('opens for anyone signed in', function () {
        actingAs(User::factory()->createOne());

        get(route('catalog'))->assertOk();
    });

    it('sends guests away from a crate', function () {
        get(route('catalog.crate', ['id' => Crate::factory()->create()->getKey()]))
            ->assertRedirect(route('welcome'));
    });

    it('keeps every crate private to its owner', function () {
        actingAs(User::factory()->createOne());

        get(route('catalog.crate', ['id' => Crate::factory()->create()->getKey()]))->assertNotFound();
    });

    it('404s on a crate that does not exist', function () {
        actingAs(User::factory()->createOne());

        get(route('catalog.crate', ['id' => 999]))->assertNotFound();
    });
});

describe('the catalog', function () {
    it('lists the top level of the viewer\'s own crates', function () {
        $user = proUser();
        $crate = Crate::factory()->for($user)->create();
        Crate::factory()->nestedIn($crate)->create();
        Crate::factory()->create();

        $crates = Livewire::actingAs($user)->test(Catalog::class)->instance()->crates;

        expect($crates->modelKeys())->toBe([$crate->getKey()]);
    });

    it('counts what is in each crate', function () {
        $user = proUser();
        $crate = Crate::factory()->for($user)->create();
        CrateItem::factory()->count(2)->for($crate)->create();
        Crate::factory()->nestedIn($crate)->create();

        $listed = Livewire::actingAs($user)->test(Catalog::class)->instance()->crates->sole();

        expect($listed)
            ->items_count->toBe(2)
            ->children_count->toBe(1);
    });

    it('links each crate to its page', function () {
        $crate = Crate::factory()->create();

        Livewire::actingAs($crate->user)
            ->test(Catalog::class)
            ->assertSee(route('catalog.crate', ['id' => $crate->getKey()]), escape: false);
    });
});

describe('making a crate', function () {
    it('adds the crate without leaving the page', function () {
        $user = User::factory()->createOne();

        $component = Livewire::actingAs($user)
            ->test(Catalog::class)
            ->call('startCreatingCrate')
            ->assertSet('creatingCrate', true)
            ->set('newCrateName', 'Foster the People')
            ->call('createCrate')
            ->assertNoRedirect()
            ->assertSet('creatingCrate', false)
            ->assertSet('newCrateName', '');

        $crate = $user->crates()->sole();

        expect($crate->name)->toBe('Foster the People')
            ->and($component->instance()->crates->modelKeys())->toBe([$crate->getKey()]);
    });

    it('suggests an artist for the name', function () {
        Artist::factory()->create(['artist_name' => 'Tame Impala']);

        Livewire::actingAs(User::factory()->createOne())
            ->test(Catalog::class)
            ->call('startCreatingCrate')
            ->assertSet('randomArtist', 'Tame Impala')
            ->assertSeeHtml('placeholder="Tame Impala"');
    });

    it('needs a name that fits', function (string $name, string $rule) {
        Livewire::actingAs(User::factory()->createOne())
            ->test(Catalog::class)
            ->set('newCrateName', $name)
            ->call('createCrate')
            ->assertHasErrors(['newCrateName' => $rule]);
    })->with([
        'empty' => ['', 'required'],
        'too long' => [str_repeat('a', 41), 'max'],
    ]);

    it('nests a crate from inside another on a Pro account', function () {
        $user = proUser();
        $parent = Crate::factory()->for($user)->create();

        $component = Livewire::actingAs($user)
            ->test(CrateShow::class, ['id' => $parent->getKey()])
            ->set('newCrateName', 'Supermodel')
            ->call('createCrate')
            ->assertNoRedirect();

        $nested = $parent->children()->sole();

        expect($nested->name)->toBe('Supermodel')
            ->and($component->instance()->crates->modelKeys())->toBe([$nested->getKey()]);
    });
});

describe('a crate', function () {
    it('shows everything filed in it, each linking to its record', function () {
        $crate = Crate::factory()->create();
        $ranking = CrateItem::factory()->for($crate)->forRanking()->create()->entity;
        $tierlist = CrateItem::factory()->for($crate)->forTierlist()->create()->entity;
        $review = CrateItem::factory()->for($crate)->forReview()->create()->entity;

        $component = Livewire::actingAs($crate->user)
            ->test(CrateShow::class, ['id' => $crate->getKey()])
            ->assertSee(route('ranking', ['id' => $ranking->getKey()]), escape: false)
            ->assertSee(route('tierlist', ['id' => $tierlist->getKey()]), escape: false)
            ->assertSee(route('review.edit', ['id' => $review->getKey()]), escape: false);

        expect($component->instance()->items)->toHaveCount(3);
    });

    it('renders a finished ranking', function () {
        $crate = Crate::factory()->create();
        $ranking = CrateItem::factory()->for($crate)->forRanking()->create()->entity;
        $ranking->update(['is_ranked' => true, 'completed_at' => now()]);

        actingAs($crate->user);

        get(route('catalog.crate', ['id' => $crate->getKey()]))
            ->assertOk()
            ->assertSee(route('ranking', ['id' => $ranking->getKey()]), escape: false);
    });

    it('leads with the crates above it', function () {
        $user = proUser();
        $parent = Crate::factory()->for($user)->create();
        $crate = Crate::factory()->nestedIn($parent)->create();

        Livewire::actingAs($user)
            ->test(CrateShow::class, ['id' => $crate->getKey()])
            ->assertSeeHtmlInOrder([
                route('catalog'),
                route('catalog.crate', ['id' => $parent->getKey()]),
            ]);
    });

    it('lists the crates inside it, with room for more on a Pro account', function () {
        $user = proUser();
        $crate = Crate::factory()->for($user)->create();
        $nested = Crate::factory()->nestedIn($crate)->create();

        $component = Livewire::actingAs($user)
            ->test(CrateShow::class, ['id' => $crate->getKey()])
            ->assertSee('wire:click="startCreatingCrate"', escape: false);

        expect($component->instance()->crates->modelKeys())->toBe([$nested->getKey()]);
    });

    it('points a free account at Pro for nesting', function () {
        $crate = Crate::factory()->create();

        Livewire::actingAs($crate->user)
            ->test(CrateShow::class, ['id' => $crate->getKey()])
            ->assertSee(route('billing'), escape: false)
            ->assertDontSee('wire:click="startCreatingCrate"', escape: false);
    });

    it('still shows crates nested before Pro was revoked, without room for more', function () {
        $user = proUser();
        $crate = Crate::factory()->for($user)->create();
        $nested = Crate::factory()->nestedIn($crate)->create();

        $component = Livewire::actingAs(revokePro($user))
            ->test(CrateShow::class, ['id' => $crate->getKey()])
            ->assertDontSee('wire:click="startCreatingCrate"', escape: false);

        expect($component->instance()->crates->modelKeys())->toBe([$nested->getKey()])
            ->and($component->instance()->canAddCrate())->toBeFalse();
    });

    it('offers to fill an empty crate', function () {
        $crate = Crate::factory()->create();

        Livewire::actingAs($crate->user)
            ->test(CrateShow::class, ['id' => $crate->getKey()])
            ->assertSee("\$dispatch('open-add-items')", escape: false);
    });

    it('lets only its owner rearrange or empty it', function (string $method, array $arguments) {
        $crate = Crate::factory()->create();
        $item = CrateItem::factory()->for($crate)->create();
        CrateItem::factory()->for($crate)->create();

        $component = Livewire::actingAs($crate->user)->test(CrateShow::class, ['id' => $crate->getKey()]);

        actingAs(User::factory()->createOne());

        $component->call($method, $item->getKey(), ...$arguments)->assertForbidden();

        expect($item->fresh()->position)->toBe(0);
    })->with([
        'dragging an item' => ['moveItem', [1]],
        'taking an item out' => ['removeItem', []],
    ]);
});
