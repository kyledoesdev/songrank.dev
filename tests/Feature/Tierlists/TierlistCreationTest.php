<?php

use App\Actions\Tierlists\StoreTierlist;
use App\Enums\TierlistType;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Playlist;
use App\Models\Tierlist;
use App\Models\Track;
use App\Models\User;

describe('the board a new list starts with', function () {
    it('comes with a bank and the default tiers', function () {
        $tierlist = storeTierlist();

        expect($tierlist->tiers)->toHaveCount(7)
            ->and($tierlist->bank)->not->toBeNull()
            ->and($tierlist->placementTiers()->pluck('name')->all())->toBe(['S', 'A', 'B', 'C', 'D', 'F']);
    });

    it('drops every entry into the bank, in the order they were picked', function () {
        $tierlist = storeTierlist(entries: [
            artistEntry(['id' => 'first-id', 'name' => 'First']),
            artistEntry(['id' => 'second-id', 'name' => 'Second']),
            artistEntry(['id' => 'third-id', 'name' => 'Third']),
        ]);

        $bank = $tierlist->bank->items;

        expect($bank)->toHaveCount(3)
            ->and($bank->pluck('position')->all())->toBe([0, 1, 2])
            ->and($bank->map(fn ($item) => $item->entryable->name())->all())
            ->toBe(['First', 'Second', 'Third'])
            ->and($tierlist->placementTiers()->every(fn ($tier) => $tier->items->isEmpty()))->toBeTrue();
    });

    it('starts unfinished and unpublished', function () {
        $tierlist = storeTierlist();

        expect($tierlist->is_complete)->toBeFalse()
            ->and($tierlist->getAttributes()['completed_at'])->toBeNull();
    });
});

describe('entries by type', function () {
    it('points an artist list at artist rows', function () {
        $tierlist = storeTierlist();

        expect($tierlist->bank->items->first()->entryable)->toBeInstanceOf(Artist::class)
            ->and(Artist::count())->toBe(1);
    });

    it('points an album list at album rows', function () {
        $tierlist = storeTierlist(TierlistType::ALBUM, [albumEntry()]);

        expect($tierlist->bank->items->first()->entryable)->toBeInstanceOf(Album::class)
            ->and(Album::where('album_id', 'currents-id')->exists())->toBeTrue();
    });

    it('points a track list at track rows', function () {
        $tierlist = storeTierlist(TierlistType::TRACK, [trackEntry()]);

        expect($tierlist->bank->items->first()->entryable)->toBeInstanceOf(Track::class)
            ->and(Track::where('track_id', 'less-i-know-id')->exists())->toBeTrue();
    });

    it('keeps the same entry from landing on the board twice', function () {
        $tierlist = storeTierlist(entries: [artistEntry(), artistEntry()]);

        expect($tierlist->bank->items)->toHaveCount(1);
    });
});

describe('naming', function () {
    it('keeps the name it was given', function () {
        expect(storeTierlist(attributes: ['name' => 'Psych Rock, Ranked'])->name)
            ->toBe('Psych Rock, Ranked');
    });

    it('borrows the name of the playlist the entries came from', function () {
        $playlist = Playlist::factory()->create(['name' => 'Late Night']);

        expect(storeTierlist(attributes: ['source' => $playlist])->name)
            ->toBe('Late Night Tier List');
    });

    it('falls back to the type when the entries came from nowhere', function () {
        expect(storeTierlist(TierlistType::ALBUM, [albumEntry()])->name)
            ->toBe('My Album Tier List');
    });

    it('shortens a name too long to sit on a card, the way a ranking does', function () {
        $name = storeTierlist(attributes: ['name' => str_repeat('a', 60)])->name;

        expect($name)->toBe(str_repeat('a', 30).'...');
    });
});

describe('provenance and settings', function () {
    it('remembers the artist whose discography seeded an album list', function () {
        $artist = Artist::factory()->create(['artist_name' => 'Tame Impala']);

        $tierlist = storeTierlist(TierlistType::ALBUM, [albumEntry()], ['source' => $artist]);

        expect($tierlist->source)->toBeInstanceOf(Artist::class)
            ->and($tierlist->source->is($artist))->toBeTrue();
    });

    it('records no source for a list assembled from search', function () {
        expect(storeTierlist()->source)->toBeNull();
    });

    it('stores the visibility and comment settings it was given', function () {
        $tierlist = storeTierlist(attributes: [
            'is_public' => true,
            'comments_enabled' => true,
            'comments_replies_enabled' => false,
        ]);

        expect($tierlist->is_public)->toBeTrue()
            ->and($tierlist->comments_enabled)->toBeTrue()
            ->and($tierlist->comments_replies_enabled)->toBeFalse();
    });

    it('keeps everything private and quiet by default', function () {
        $tierlist = storeTierlist();

        expect($tierlist->is_public)->toBeFalse()
            ->and($tierlist->comments_enabled)->toBeFalse()
            ->and($tierlist->comments_replies_enabled)->toBeFalse();
    });
});

function storeTierlist(
    TierlistType $type = TierlistType::ARTIST,
    ?array $entries = null,
    array $attributes = [],
    ?User $user = null,
): Tierlist {
    $tierlist = (new StoreTierlist)->handle(
        $user ?? User::factory()->createOne(),
        array_merge([
            'type' => $type,
            'entries' => collect($entries ?? [artistEntry()]),
        ], $attributes),
    );

    return $tierlist->fresh();
}
