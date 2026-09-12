<?php

use App\Actions\Tierlists\ResolveTierlistEntries;
use App\Enums\TierlistType;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Track;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

describe('artist entries', function () {
    it('creates an artist it has never seen', function () {
        $resolved = resolveEntries(TierlistType::ARTIST, [artistEntry()]);

        $artist = Artist::where('artist_id', 'tame-impala-id')->firstOrFail();

        expect($artist->artist_name)->toBe('Tame Impala')
            ->and($artist->artist_img)->toBe('https://example.test/tame-impala.png')
            ->and($resolved->get('tame-impala-id')->is($artist))->toBeTrue();
    });

    it('leaves an artist it already holds alone rather than rewriting it', function () {
        Artist::factory()->create([
            'artist_id' => 'tame-impala-id',
            'artist_name' => 'Tame Impala (old)',
            'artist_img' => 'https://example.test/existing.png',
        ]);

        resolveEntries(TierlistType::ARTIST, [artistEntry()]);

        $artist = Artist::where('artist_id', 'tame-impala-id')->firstOrFail();

        expect(Artist::count())->toBe(1)
            ->and($artist->artist_name)->toBe('Tame Impala (old)')
            ->and($artist->artist_img)->toBe('https://example.test/existing.png');
    });

    it('fills in the picture for an artist it only knew as a credit', function () {
        Artist::factory()->create([
            'artist_id' => 'tame-impala-id',
            'artist_img' => null,
        ]);

        resolveEntries(TierlistType::ARTIST, [artistEntry()]);

        expect(Artist::where('artist_id', 'tame-impala-id')->firstOrFail()->artist_img)
            ->toBe('https://example.test/tame-impala.png');
    });
});
describe('album entries', function () {
    it('creates the album and the artist it credits', function () {
        $resolved = resolveEntries(TierlistType::ALBUM, [albumEntry()]);

        $album = Album::where('album_id', 'currents-id')->firstOrFail();

        expect($album->name)->toBe('Currents')
            ->and($album->release_date)->toBe('2015-07-17')
            ->and($album->total_tracks)->toBe(13)
            ->and($album->artist->artist_name)->toBe('Tame Impala')
            ->and($resolved->get('currents-id')->is($album))->toBeTrue();
    });

    it('points several albums at one shared artist row', function () {
        resolveEntries(TierlistType::ALBUM, [
            albumEntry(),
            albumEntry(['id' => 'lonerism-id', 'name' => 'Lonerism']),
        ]);

        expect(Artist::where('artist_id', 'tame-impala-id')->count())->toBe(1)
            ->and(Album::query()->pluck('artist_id')->unique())->toHaveCount(1);
    });

    it('reuses an artist that already exists, keeping its artwork', function () {
        $existing = Artist::factory()->create([
            'artist_id' => 'tame-impala-id',
            'artist_img' => 'https://example.test/tame-impala-real.png',
        ]);

        resolveEntries(TierlistType::ALBUM, [albumEntry()]);

        expect(Artist::count())->toBe(1)
            ->and($existing->fresh()->artist_img)->toBe('https://example.test/tame-impala-real.png')
            ->and(Album::first()->artist_id)->toBe($existing->getKey());
    });

    it('still stores an album that credits nobody', function () {
        resolveEntries(TierlistType::ALBUM, [albumEntry(['artist_id' => null, 'artist_name' => null])]);

        expect(Album::where('album_id', 'currents-id')->firstOrFail()->artist_id)->toBeNull()
            ->and(Artist::count())->toBe(0);
    });
});

describe('track entries', function () {
    it('creates the track and the artist it credits', function () {
        $resolved = resolveEntries(TierlistType::TRACK, [trackEntry()]);

        $track = Track::where('track_id', 'less-i-know-id')->firstOrFail();

        expect($track->name)->toBe('The Less I Know The Better')
            ->and($track->album_name)->toBe('Currents')
            ->and($track->artist->artist_name)->toBe('Tame Impala')
            ->and($resolved->get('less-i-know-id')->is($track))->toBeTrue();
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

function albumEntries(int $count): array
{
    return collect(range(1, $count))
        ->map(fn (int $i) => albumEntry([
            'id' => "currents-id-{$i}",
            'name' => "Album {$i}",
            'artist_id' => "tame-impala-id-{$i}",
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
