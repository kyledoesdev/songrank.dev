<?php

use App\Actions\Catalog\AddCrateItem;
use App\Enums\CrateEntityType;
use App\Livewire\Catalog\AddToCrateModal;
use App\Livewire\Catalog\CrateShow;
use App\Models\Crate;
use App\Models\CrateItem;
use App\Models\Ranking;
use App\Models\Review;
use App\Models\Tierlist;
use App\Models\User;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

describe('filing something in a crate', function () {
    it('adds it to the end of the crate', function () {
        $crate = Crate::factory()->create();
        $ranking = Ranking::factory()->for($crate->user)->create();
        $review = Review::factory()->for($crate->user)->create();

        (new AddCrateItem)->handle($crate, $ranking);
        $item = (new AddCrateItem)->handle($crate, $review);

        expect($item->position)->toBe(1)
            ->and(crateContents($crate))->toBe([crateEntry($ranking), crateEntry($review)]);
    });

    it('records the full class name as the entity type', function () {
        $crate = Crate::factory()->create();

        $item = (new AddCrateItem)->handle($crate, Tierlist::factory()->for($crate->user)->create());

        expect($item->entity_type)->toBe(Tierlist::class);
    });

    it('files the same record only once per crate', function () {
        $crate = Crate::factory()->create();
        $ranking = Ranking::factory()->for($crate->user)->create();

        $first = (new AddCrateItem)->handle($crate, $ranking);
        $second = (new AddCrateItem)->handle($crate, $ranking);

        expect($second->is($first))->toBeTrue()
            ->and($crate->items()->count())->toBe(1);
    });

    it('files the same record in as many crates as you like', function () {
        $user = proUser();
        $ranking = Ranking::factory()->for($user)->create();
        [$byArtist, $byYear] = Crate::factory()->count(2)->for($user)->create();

        (new AddCrateItem)->handle($byArtist, $ranking);
        (new AddCrateItem)->handle($byYear, $ranking);

        expect($ranking->crateItems()->pluck('crate_id')->all())->toBe([$byArtist->getKey(), $byYear->getKey()]);
    });

    it('takes records that are not finished yet', function () {
        $crate = Crate::factory()->create();
        $ranking = Ranking::factory()->for($crate->user)->create(['is_ranked' => false]);
        $tierlist = Tierlist::factory()->for($crate->user)->create(['is_complete' => false]);
        $draft = Review::factory()->for($crate->user)->create(['is_published' => false]);

        collect([$ranking, $tierlist, $draft])->each(fn ($entity) => (new AddCrateItem)->handle($crate, $entity));

        expect(crateContents($crate))->toBe([crateEntry($ranking), crateEntry($tierlist), crateEntry($draft)]);
    });
});

describe('reading a crate', function () {
    it('loads what each card reads up front', function () {
        $crate = Crate::factory()->create();
        CrateItem::factory()->for($crate)->forRanking()->create();
        CrateItem::factory()->for($crate)->forTierlist()->create();
        CrateItem::factory()->for($crate)->forReview()->create();

        [$ranking, $tierlist, $review] = CrateItem::query()->forCratePage($crate)->get()->pluck('entity');

        expect($ranking->relationLoaded('source'))->toBeTrue()
            ->and($ranking->songs_count)->toBe(10)
            ->and($tierlist->relationLoaded('tiers'))->toBeTrue()
            ->and($tierlist->items_count)->not->toBeNull()
            ->and($review->relationLoaded('subject'))->toBeTrue()
            ->and($review->comments_count)->toBe(0);
    });

    it('leaves out anything deleted without taking itself out', function () {
        $crate = Crate::factory()->create();
        $kept = CrateItem::factory()->for($crate)->forRanking()->create();
        $orphan = CrateItem::factory()->for($crate)->forRanking()->create();

        Ranking::withoutEvents(fn () => $orphan->entity->delete());

        expect(CrateItem::query()->forCratePage($crate)->pluck('id')->all())->toBe([$kept->getKey()]);
    });
});

describe('the add items modal', function () {
    it('lists the viewer\'s own records, a tab for each kind', function () {
        $crate = Crate::factory()->create();
        $ranking = Ranking::factory()->for($crate->user)->create();
        $tierlist = Tierlist::factory()->for($crate->user)->create();
        $review = Review::factory()->for($crate->user)->create();
        Ranking::factory()->create();

        $component = Livewire::actingAs($crate->user)->test(AddToCrateModal::class, ['crate' => $crate]);

        expect(addItemsModalResults($component))->toBe([$ranking->getKey()]);

        $component->set('tab', CrateEntityType::TIERLIST->value);

        expect(addItemsModalResults($component))->toBe([$tierlist->getKey()]);

        $component->set('tab', CrateEntityType::REVIEW->value);

        expect(addItemsModalResults($component))->toBe([$review->getKey()]);
    });

    it('finds records by name', function () {
        $crate = Crate::factory()->create();
        $match = Ranking::factory()->for($crate->user)->create(['name' => 'Torches List']);
        Ranking::factory()->for($crate->user)->create(['name' => 'Supermodel List']);

        $component = Livewire::actingAs($crate->user)
            ->test(AddToCrateModal::class, ['crate' => $crate])
            ->set('search', 'Torches');

        expect(addItemsModalResults($component))->toBe([$match->getKey()]);
    });

    it('adds a record and tells the page', function () {
        $crate = Crate::factory()->create();
        $ranking = Ranking::factory()->for($crate->user)->create();

        $component = Livewire::actingAs($crate->user)
            ->test(AddToCrateModal::class, ['crate' => $crate])
            ->call('toggle', $ranking->getKey())
            ->assertDispatched('crate-items-updated');

        expect(crateContents($crate))->toBe([crateEntry($ranking)])
            ->and($component->instance()->isFiled($ranking))->toBeTrue();
    });

    it('takes a record back out on a second press', function () {
        $crate = Crate::factory()->create();
        $review = CrateItem::factory()->for($crate)->forReview()->create()->entity;

        Livewire::actingAs($crate->user)
            ->test(AddToCrateModal::class, ['crate' => $crate])
            ->set('tab', CrateEntityType::REVIEW->value)
            ->call('toggle', $review->getKey());

        expect(crateContents($crate))->toBe([]);
    });

    it('will not file someone else\'s record', function () {
        $crate = Crate::factory()->create();

        Livewire::actingAs($crate->user)
            ->test(AddToCrateModal::class, ['crate' => $crate])
            ->call('toggle', Ranking::factory()->create()->getKey())
            ->assertNotFound();
    });

    it('belongs to the crate\'s owner alone', function () {
        Livewire::actingAs(User::factory()->createOne())
            ->test(AddToCrateModal::class, ['crate' => Crate::factory()->create()])
            ->assertForbidden();
    });

});

describe('what each record shows in a list', function () {
    it('uses the artwork of what it is about', function () {
        $ranking = Ranking::factory()->create();
        $tierlist = Tierlist::factory()->fromArtist()->create();
        $review = Review::factory()->create();

        expect($ranking->cover())->toBe($ranking->source->cover())
            ->and($tierlist->cover())->toBe($tierlist->source->cover())
            ->and($review->cover())->toBe($review->subject->cover());
    });

    it('has no artwork for a tier list put together from search', function () {
        expect(Tierlist::factory()->create()->cover())->toBeNull();
    });

    it('is only marked as unfinished until it is done', function (string $model, string $finished) {
        expect($model::factory()->make([$finished => false])->progressLabel())->not->toBeNull()
            ->and($model::factory()->make([$finished => true])->progressLabel())->toBeNull();
    })->with([
        'a ranking' => [Ranking::class, 'is_ranked'],
        'a tier list' => [Tierlist::class, 'is_complete'],
        'a review' => [Review::class, 'is_published'],
    ]);
});

describe('taking something out on the crate page', function () {
    it('removes it from this crate only', function () {
        $user = proUser();
        [$crate, $other] = Crate::factory()->count(2)->for($user)->create();
        $item = CrateItem::factory()->for($crate)->forRanking()->create();
        $kept = CrateItem::factory()->for($other)->forRanking($item->entity)->create();

        Livewire::actingAs($user)
            ->test(CrateShow::class, ['id' => $crate->getKey()])
            ->call('removeItem', $item->getKey());

        expect(crateContents($crate))->toBe([])
            ->and($kept->fresh())->not->toBeNull();
    });

    it('will not remove an item from another crate', function () {
        $crate = Crate::factory()->create();

        Livewire::actingAs($crate->user)
            ->test(CrateShow::class, ['id' => $crate->getKey()])
            ->call('removeItem', CrateItem::factory()->create()->getKey())
            ->assertNotFound();
    });
});

/**
 * @return list<int>
 */
function addItemsModalResults(Testable $component): array
{
    return $component->instance()->results->pluck('id')->all();
}
