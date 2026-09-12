<?php

use App\Models\Album;
use App\Models\Artist;
use App\Models\Tierlist;
use App\Models\TierlistItem;
use App\Models\Track;
use App\Models\User;

use function Pest\Laravel\actingAs;

describe('the explore feed', function () {
    it('shows only lists that are public and finished', function () {
        $explorable = publicCompletedTierlist();
        Tierlist::factory()->complete()->create();
        Tierlist::factory()->public()->create();

        expect(Tierlist::query()->forExplorePage()->pluck('id')->all())->toBe([$explorable->id]);
    });

    it('puts the most recently finished list first', function () {
        $older = publicCompletedTierlist(['completed_at' => now()->subWeek()]);
        $newer = publicCompletedTierlist(['completed_at' => now()]);

        expect(Tierlist::query()->forExplorePage()->pluck('id')->all())
            ->toBe([$newer->id, $older->id]);
    });

    it('searches the list name', function () {
        $match = publicCompletedTierlist(['name' => 'Best Psych Rock Records']);
        publicCompletedTierlist(['name' => 'Something Else']);

        expect(Tierlist::query()->forExplorePage('psych rock')->pluck('id')->all())->toBe([$match->id]);
    });

    it('searches the source a list came from', function () {
        $artist = Artist::factory()->create(['artist_name' => 'Local Natives']);
        $match = Tierlist::factory()
            ->complete()
            ->public()
            ->fromArtist($artist)
            ->create(['name' => 'Untitled']);

        publicCompletedTierlist(['name' => 'Untitled Too']);

        expect(Tierlist::query()->forExplorePage('local natives')->pluck('id')->all())->toBe([$match->id]);
    });

    it('counts the entries on each list', function () {
        $tierlist = publicCompletedTierlist();
        TierlistItem::factory()->count(3)->inTier($tierlist->bank)->create();

        expect(Tierlist::query()->forExplorePage()->first()->items_count)->toBe(3);
    });

    it('reports how many lists are explorable', function () {
        publicCompletedTierlist();
        publicCompletedTierlist();
        Tierlist::factory()->create();

        expect(Tierlist::query()->explorableCount())->toBe(2);
    });
});

describe('the profile feed', function () {
    it('shows the owner everything they have made', function () {
        $user = User::factory()->createOne();
        Tierlist::factory()->count(2)->for($user)->create();
        Tierlist::factory()->create();

        actingAs($user);

        expect(Tierlist::query()->forProfilePage($user)->get())->toHaveCount(2);
    });

    it('shows a visitor only the public finished ones', function () {
        $owner = User::factory()->createOne();
        $visible = Tierlist::factory()->for($owner)->complete()->public()->create();
        Tierlist::factory()->for($owner)->create();

        actingAs(User::factory()->createOne());

        expect(Tierlist::query()->forProfilePage($owner)->pluck('id')->all())->toBe([$visible->id]);
    });

    it('floats unfinished lists to the top for the owner', function () {
        $user = User::factory()->createOne();
        Tierlist::factory()->for($user)->complete()->create();
        $inProgress = Tierlist::factory()->for($user)->create();

        actingAs($user);

        expect(Tierlist::query()->forProfilePage($user)->first()->id)->toBe($inProgress->id);
    });
});

describe('card previews', function () {
    it('loads only the top placement tier, never the bank', function () {
        $tierlist = publicCompletedTierlist();
        TierlistItem::factory()->inTier($tierlist->bank)->create();
        TierlistItem::factory()->inTier($tierlist->placementTiers()->first())->create();

        $loaded = Tierlist::query()->withTopTier()->find($tierlist->getKey());

        expect($loaded->tiers)->toHaveCount(1)
            ->and($loaded->tiers->first()->name)->toBe('S')
            ->and($loaded->tiers->first()->items)->toHaveCount(1);
    });

    it('caps how many entries a preview pulls back', function () {
        $tierlist = publicCompletedTierlist();
        $tier = $tierlist->placementTiers()->first();

        foreach (range(0, 7) as $position) {
            TierlistItem::factory()->inTier($tier, $position)->create();
        }

        $loaded = Tierlist::query()->withTopTier(items: 5)->find($tierlist->getKey());

        expect($loaded->tiers->first()->items)->toHaveCount(5);
    });
});

describe('catalog aggregates', function () {
    it('counts an album that appears on a public finished list', function () {
        $tierlisted = Album::factory()->create();
        Album::factory()->create();

        TierlistItem::factory()
            ->inTier(publicCompletedTierlist()->placementTiers()->first())
            ->for($tierlisted, 'entryable')
            ->create();

        expect(Album::query()->tierlisted()->pluck('id')->all())->toBe([$tierlisted->id]);
    });

    it('ignores a track that only appears on an unfinished list', function () {
        $track = Track::factory()->create();

        TierlistItem::factory()
            ->inTier(Tierlist::factory()->create()->bank)
            ->for($track, 'entryable')
            ->create();

        expect(Track::query()->tierlisted()->get())->toBeEmpty();
    });

    it('ignores a track that only appears on a private list', function () {
        $track = Track::factory()->create();

        TierlistItem::factory()
            ->inTier(Tierlist::factory()->complete()->create()->placementTiers()->first())
            ->for($track, 'entryable')
            ->create();

        expect(Track::query()->tierlisted()->get())->toBeEmpty();
    });

    it('rounds the public count to the nearest twenty five', function () {
        $tier = publicCompletedTierlist()->placementTiers()->first();

        foreach (range(0, 12) as $position) {
            TierlistItem::factory()
                ->inTier($tier, $position)
                ->for(Album::factory()->create(), 'entryable')
                ->create();
        }

        expect(Album::query()->tierlistedAlbumCount())->toBe(25);
    });
});
describe('the dashboard feed', function () {
    it('shows a user their own lists only', function () {
        $user = User::factory()->createOne();
        Tierlist::factory()->for($user)->create();
        Tierlist::factory()->create();

        expect(Tierlist::query()->forDashboard($user)->get())->toHaveCount(1);
    });

});
