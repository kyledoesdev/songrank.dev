<?php

use App\Enums\TierlistType;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Playlist;
use App\Models\Tier;
use App\Models\Tierlist;
use App\Models\TierlistItem;
use App\Models\Track;
use App\Models\User;

use function Pest\Laravel\actingAs;

describe('default tiers', function () {
    it('is born with a bank and the configured tier set', function () {
        $tierlist = Tierlist::factory()->create();

        expect($tierlist->tiers)->toHaveCount(count(config('tierlists.default_tiers')) + 1)
            ->and($tierlist->bank)->not->toBeNull()
            ->and($tierlist->bank->is_bank)->toBeTrue()
            ->and($tierlist->placementTiers()->pluck('name')->all())->toBe(['S', 'A', 'B', 'C', 'D', 'F']);
    });

    it('puts the bank at position zero and the tiers after it', function () {
        $tierlist = Tierlist::factory()->create();

        expect($tierlist->tiers->pluck('position')->all())->toBe([0, 1, 2, 3, 4, 5, 6])
            ->and($tierlist->tiers->first()->is_bank)->toBeTrue();
    });
});

describe('relationships', function () {
    it('belongs to the user who made it', function () {
        $user = User::factory()->createOne();

        $tierlist = Tierlist::factory()->for($user)->create();

        expect($tierlist->user->is($user))->toBeTrue();
    });

    it('resolves an artist entry through the morph map', function () {
        $artist = Artist::factory()->create();
        $item = placeEntry(Tierlist::factory()->create(), $artist);

        expect($item->fresh()->entryable)->toBeInstanceOf(Artist::class)
            ->and($item->entryable_type)->toBe(TierlistType::ARTIST->value);
    });

    it('resolves an album entry through the morph map', function () {
        $album = Album::factory()->create();
        $item = placeEntry(Tierlist::factory()->albums()->create(), $album);

        expect($item->fresh()->entryable)->toBeInstanceOf(Album::class)
            ->and($item->entryable_type)->toBe(TierlistType::ALBUM->value);
    });

    it('resolves a track entry through the morph map', function () {
        $track = Track::factory()->create();
        $item = placeEntry(Tierlist::factory()->tracks()->create(), $track);

        expect($item->fresh()->entryable)->toBeInstanceOf(Track::class)
            ->and($item->entryable_type)->toBe(TierlistType::TRACK->value);
    });

    it('has no source unless one is given', function () {
        expect(Tierlist::factory()->create()->source)->toBeNull();
    });

    it('remembers the playlist a track list was imported from', function () {
        $playlist = Playlist::factory()->create();

        $tierlist = Tierlist::factory()->tracks()->fromPlaylist($playlist)->create();

        expect($tierlist->source)->toBeInstanceOf(Playlist::class)
            ->and($tierlist->source->is($playlist))->toBeTrue();
    });
});

describe('the bank', function () {
    it('is empty on a fresh list', function () {
        expect(Tierlist::factory()->create()->bankIsEmpty())->toBeTrue();
    });

    it('is not empty while an entry is still unplaced', function () {
        $tierlist = Tierlist::factory()->create();

        TierlistItem::factory()->inTier($tierlist->bank)->create();

        expect($tierlist->fresh()->bankIsEmpty())->toBeFalse();
    });

    it('is empty again once that entry is placed', function () {
        $tierlist = Tierlist::factory()->create();
        $item = TierlistItem::factory()->inTier($tierlist->bank)->create();

        $item->update(['tier_id' => $tierlist->placementTiers()->first()->getKey()]);

        expect($tierlist->fresh()->bankIsEmpty())->toBeTrue();
    });
});

describe('reading order', function () {
    it('reads the top tier left to right, then down', function () {
        $tierlist = Tierlist::factory()->create();
        [$s, $a] = $tierlist->placementTiers()->take(2)->all();

        $second = namedEntry('second', $tierlist, $s, position: 1);
        $first = namedEntry('first', $tierlist, $s, position: 0);
        $third = namedEntry('third', $tierlist, $a, position: 0);

        $ranked = $tierlist->fresh()->rankedItems();

        expect($ranked->pluck('id')->all())->toBe([$first->id, $second->id, $third->id]);
    });

    it('leaves unplaced entries out of the reading order', function () {
        $tierlist = Tierlist::factory()->create();

        TierlistItem::factory()->inTier($tierlist->bank)->create();
        $placed = TierlistItem::factory()->inTier($tierlist->placementTiers()->first())->create();

        expect($tierlist->fresh()->rankedItems()->pluck('id')->all())->toBe([$placed->id]);
    });
});

describe('visibility', function () {
    it('is always visible to its owner, finished or not', function () {
        $tierlist = Tierlist::factory()->create();

        actingAs($tierlist->user);

        expect($tierlist->canBeSeen())->toBeTrue();
    });

    it('is visible to anyone once it is public and finished', function () {
        expect(publicCompletedTierlist()->canBeSeen())->toBeTrue();
    });

    it('is hidden from others while it is private', function () {
        $tierlist = Tierlist::factory()->complete()->create();

        actingAs(User::factory()->createOne());

        expect($tierlist->canBeSeen())->toBeFalse();
    });

    it('is hidden from others while it is unfinished', function () {
        $tierlist = Tierlist::factory()->public()->create();

        actingAs(User::factory()->createOne());

        expect($tierlist->canBeSeen())->toBeFalse();
    });
});

describe('completed at', function () {
    it('reads as in progress until it is finished', function () {
        expect(Tierlist::factory()->create()->completed_at)->toBe('In Progress');
    });

    it('reads as a human diff once it is finished', function () {
        expect(publicCompletedTierlist()->completed_at)->toContain('ago');
    });
});

/**
 * Drops a catalog record into a list's bank and hands back the item.
 */
function placeEntry(Tierlist $tierlist, $entry): TierlistItem
{
    return TierlistItem::factory()
        ->inTier($tierlist->bank)
        ->for($entry, 'entryable')
        ->create();
}

/**
 * An entry whose artist is named, so ordering assertions read clearly.
 */
function namedEntry(string $name, Tierlist $tierlist, Tier $tier, int $position): TierlistItem
{
    return TierlistItem::factory()
        ->inTier($tier, $position)
        ->for(Artist::factory()->create(['artist_name' => $name]), 'entryable')
        ->create();
}
