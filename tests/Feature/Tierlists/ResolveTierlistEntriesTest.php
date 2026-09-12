<?php

use App\Actions\Tierlists\ResolveTierlistEntries;
use App\Enums\TierlistType;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Models\Track;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

describe('artist entries', function () {
    it('creates an artist it has never seen', function () {
        $resolved = resolveEntries(TierlistType::ARTIST, [artistEntry()]);

        $artist = Artist::where('artist_id', 'artist-id')->firstOrFail();

        expect($artist->artist_name)->toBe('Radiohead')
            ->and($artist->artist_img)->toBe('https://example.test/artist.png')
            ->and($resolved->get('artist-id')->is($artist))->toBeTrue();
    });

    it('leaves an artist it already holds alone rather than rewriting it', function () {
        Artist::factory()->create([
            'artist_id' => 'artist-id',
            'artist_name' => 'Radiohead (old)',
            'artist_img' => 'https://example.test/existing.png',
        ]);

        resolveEntries(TierlistType::ARTIST, [artistEntry()]);

        $artist = Artist::where('artist_id', 'artist-id')->firstOrFail();

        expect(Artist::count())->toBe(1)
            ->and($artist->artist_name)->toBe('Radiohead (old)')
            ->and($artist->artist_img)->toBe('https://example.test/existing.png');
    });

    it('fills in the picture for an artist it only knew as a credit', function () {
        Artist::factory()->create([
            'artist_id' => 'artist-id',
            'artist_img' => null,
        ]);

        resolveEntries(TierlistType::ARTIST, [artistEntry()]);

        expect(Artist::where('artist_id', 'artist-id')->firstOrFail()->artist_img)
            ->toBe('https://example.test/artist.png');
    });
});
describe('album entries', function () {
    it('creates the album and the artist it credits', function () {
        $resolved = resolveEntries(TierlistType::ALBUM, [albumEntry()]);

        $album = Album::where('album_id', 'album-id')->firstOrFail();

        expect($album->name)->toBe('In Rainbows')
            ->and($album->release_date)->toBe('2007-10-10')
            ->and($album->total_tracks)->toBe(10)
            ->and($album->artist->artist_name)->toBe('Radiohead')
            ->and($resolved->get('album-id')->is($album))->toBeTrue();
    });

    it('points several albums at one shared artist row', function () {
        resolveEntries(TierlistType::ALBUM, [
            albumEntry(),
            albumEntry(['id' => 'kid-a-id', 'name' => 'Kid A']),
        ]);

        expect(Artist::where('artist_id', 'artist-id')->count())->toBe(1)
            ->and(Album::query()->pluck('artist_id')->unique())->toHaveCount(1);
    });

    it('reuses an artist that already exists, keeping its artwork', function () {
        $existing = Artist::factory()->create([
            'artist_id' => 'artist-id',
            'artist_img' => 'https://example.test/real-artist.png',
        ]);

        resolveEntries(TierlistType::ALBUM, [albumEntry()]);

        expect(Artist::count())->toBe(1)
            ->and($existing->fresh()->artist_img)->toBe('https://example.test/real-artist.png')
            ->and(Album::first()->artist_id)->toBe($existing->getKey());
    });

    it('still stores an album that credits nobody', function () {
        resolveEntries(TierlistType::ALBUM, [albumEntry(['artist_id' => null, 'artist_name' => null])]);

        expect(Album::where('album_id', 'album-id')->firstOrFail()->artist_id)->toBeNull()
            ->and(Artist::count())->toBe(0);
    });
});

describe('track entries', function () {
    it('creates the track and the artist it credits', function () {
        $resolved = resolveEntries(TierlistType::TRACK, [trackEntry()]);

        $track = Track::where('track_id', 'track-id')->firstOrFail();

        expect($track->name)->toBe('Nude')
            ->and($track->album_name)->toBe('In Rainbows')
            ->and($track->artist->artist_name)->toBe('Radiohead')
            ->and($resolved->get('track-id')->is($track))->toBeTrue();
    });

    it('keeps a track separate from the songs table', function () {
        resolveEntries(TierlistType::TRACK, [trackEntry()]);

        expect(Track::count())->toBe(1)
            ->and(Song::count())->toBe(0);
    });
});

describe('duplicates and empties', function () {
    it('collapses the same entry sent twice', function () {
        $resolved = resolveEntries(TierlistType::ARTIST, [artistEntry(), artistEntry()]);

        expect(Artist::count())->toBe(1)
            ->and($resolved)->toHaveCount(1);
    });

    it('resolves nothing from an empty bank', function () {
        expect(resolveEntries(TierlistType::ALBUM, []))->toBeEmpty()
            ->and(Album::count())->toBe(0);
    });
});

describe('query cost', function () {
    it('costs the same whether the bank holds five entries or fifty', function () {
        $small = countQueries(fn () => resolveEntries(TierlistType::ALBUM, albumEntries(5)));
        $large = countQueries(fn () => resolveEntries(TierlistType::ALBUM, albumEntries(50)));

        expect($large)->toBe($small);
    });

    it('does not rewrite albums it already holds on a second import', function () {
        $entries = albumEntries(10);

        resolveEntries(TierlistType::ALBUM, $entries);

        $writes = 0;
        DB::listen(function ($query) use (&$writes) {
            if (Str::startsWith(Str::lower($query->sql), ['insert', 'update'])) {
                $writes++;
            }
        });

        resolveEntries(TierlistType::ALBUM, $entries);

        expect($writes)->toBe(0)
            ->and(Album::count())->toBe(10);
    });
});
function resolveEntries(TierlistType $type, array $entries)
{
    return (new ResolveTierlistEntries)->handle($type, collect($entries));
}

function artistEntry(array $overrides = []): array
{
    return array_merge([
        'id' => 'artist-id',
        'name' => 'Radiohead',
        'cover' => 'https://example.test/artist.png',
    ], $overrides);
}

function albumEntry(array $overrides = []): array
{
    return array_merge([
        'id' => 'album-id',
        'name' => 'In Rainbows',
        'cover' => 'https://example.test/album.png',
        'artist_id' => 'artist-id',
        'artist_name' => 'Radiohead',
        'release_date' => '2007-10-10',
        'total_tracks' => 10,
        'album_type' => 'album',
    ], $overrides);
}

function trackEntry(array $overrides = []): array
{
    return array_merge([
        'id' => 'track-id',
        'name' => 'Nude',
        'cover' => 'https://example.test/album.png',
        'artist_id' => 'artist-id',
        'artist_name' => 'Radiohead',
        'album_name' => 'In Rainbows',
    ], $overrides);
}

function albumEntries(int $count): array
{
    return collect(range(1, $count))
        ->map(fn (int $i) => albumEntry([
            'id' => "album-id-{$i}",
            'name' => "Album {$i}",
            'artist_id' => "artist-id-{$i}",
            'artist_name' => "Artist {$i}",
        ]))
        ->all();
}

function countQueries(Closure $work): int
{
    DB::flushQueryLog();
    DB::enableQueryLog();

    $work();

    $count = count(DB::getQueryLog());

    DB::disableQueryLog();

    return $count;
}
