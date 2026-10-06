<?php

use App\Actions\Catalog\AddCrateItem;
use App\Models\Crate;
use App\Models\CrateItem;
use App\Models\Ranking;
use App\Models\Review;
use App\Models\Tierlist;

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
