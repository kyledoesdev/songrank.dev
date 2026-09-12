<?php

use App\Actions\Spotify\GetArtistAlbums;
use App\Actions\Spotify\SearchAlbums;
use App\Actions\Spotify\SearchTracks;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

describe('searching albums', function () {
    it('maps a result onto the shape an album entry is stored from', function () {
        fakeSearch('albums', [spotifyAlbum()]);

        $album = (new SearchAlbums)->handle(searcher(), 'in rainbows')->first();

        expect($album)->toBe([
            'id' => 'album-id',
            'name' => 'In Rainbows',
            'cover' => 'https://example.test/album.png',
            'artist_id' => 'artist-id',
            'artist_name' => 'Radiohead',
            'release_date' => '2007-10-10',
            'total_tracks' => 10,
            'album_type' => 'album',
        ]);
    });

    it('drops results with no artwork, which would sit on the board as blank squares', function () {
        fakeSearch('albums', [
            spotifyAlbum(),
            spotifyAlbum(['id' => 'coverless-id', 'images' => []]),
        ]);

        $albums = (new SearchAlbums)->handle(searcher(), 'in rainbows');

        expect($albums)->toHaveCount(1)
            ->and($albums->first()['id'])->toBe('album-id');
    });

    it('asks Spotify for albums', function () {
        fakeSearch('albums', [spotifyAlbum()]);

        (new SearchAlbums)->handle(searcher(), 'in rainbows');

        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'type=album'));
    });

    it('returns nothing when Spotify falls over', function () {
        Http::fake([
            'https://accounts.spotify.com/*' => Http::response(['access_token' => 'fresh-token']),
            'https://api.spotify.com/v1/search*' => fn () => throw new Exception('spotify is down'),
        ]);

        expect((new SearchAlbums)->handle(searcher(), 'in rainbows'))->toBeNull();
    });
});

describe('searching tracks', function () {
    it('maps a result onto the shape a track entry is stored from', function () {
        fakeSearch('tracks', [spotifyTrack()]);

        $track = (new SearchTracks)->handle(searcher(), 'nude')->first();

        expect($track)->toBe([
            'id' => 'track-id',
            'name' => 'Nude',
            'cover' => 'https://example.test/album.png',
            'artist_id' => 'artist-id',
            'artist_name' => 'Radiohead',
            'album_name' => 'In Rainbows',
        ]);
    });

    it('takes the artwork from the album the track belongs to', function () {
        fakeSearch('tracks', [spotifyTrack(['album' => ['name' => 'No Art', 'images' => []]])]);

        expect((new SearchTracks)->handle(searcher(), 'nude'))->toBeEmpty();
    });

    it('asks Spotify for tracks', function () {
        fakeSearch('tracks', [spotifyTrack()]);

        (new SearchTracks)->handle(searcher(), 'nude');

        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'type=track'));
    });
});

describe('importing a discography', function () {
    it('returns every release the artist has', function () {
        fakeDiscography(1, [
            spotifyAlbum(),
            spotifyAlbum(['id' => 'ep-id', 'name' => 'Airbag EP', 'album_type' => 'single']),
        ]);

        $albums = (new GetArtistAlbums)->handle(searcher(), 'artist-id');

        expect($albums->pluck('id')->all())->toBe(['album-id', 'ep-id'])
            ->and($albums->last()['album_type'])->toBe('single');
    });

    it('collapses the same record released into several markets', function () {
        fakeDiscography(1, [
            spotifyAlbum(),
            spotifyAlbum(['id' => 'album-id-jp', 'name' => 'IN RAINBOWS']),
            spotifyAlbum(['id' => 'album-id-uk', 'name' => 'In Rainbows']),
        ]);

        $albums = (new GetArtistAlbums)->handle(searcher(), 'artist-id');

        expect($albums)->toHaveCount(1)
            ->and($albums->first()['id'])->toBe('album-id');
    });

    it('walks every page of a long discography', function () {
        Http::fake([
            'https://accounts.spotify.com/*' => Http::response(['access_token' => 'fresh-token']),
            'https://api.spotify.com/v1/artists/*' => fn (Request $request) => Http::response([
                'total' => 51,
                'items' => str_contains($request->url(), 'offset=50')
                    ? [spotifyAlbum(['id' => 'late-id', 'name' => 'A Moon Shaped Pool'])]
                    : [spotifyAlbum()],
            ]),
        ]);

        $albums = (new GetArtistAlbums)->handle(searcher(), 'artist-id');

        expect($albums->pluck('id')->all())->toBe(['album-id', 'late-id']);
    });

    it('returns nothing when Spotify falls over', function () {
        Http::fake([
            'https://accounts.spotify.com/*' => Http::response(['access_token' => 'fresh-token']),
            'https://api.spotify.com/v1/artists/*' => fn () => throw new Exception('spotify is down'),
        ]);

        expect((new GetArtistAlbums)->handle(searcher(), 'artist-id'))->toBeNull();
    });
});

function searcher(): User
{
    return User::factory()->createOne();
}

function fakeSearch(string $key, array $items): void
{
    Http::fake([
        'https://accounts.spotify.com/*' => Http::response(['access_token' => 'fresh-token']),
        'https://api.spotify.com/v1/search*' => Http::response([$key => ['items' => $items]]),
    ]);
}

function fakeDiscography(int $total, array $items): void
{
    Http::fake([
        'https://accounts.spotify.com/*' => Http::response(['access_token' => 'fresh-token']),
        'https://api.spotify.com/v1/artists/*' => Http::response([
            'total' => $total,
            'items' => $items,
        ]),
    ]);
}

function spotifyAlbum(array $overrides = []): array
{
    return array_merge([
        'id' => 'album-id',
        'name' => 'In Rainbows',
        'album_type' => 'album',
        'release_date' => '2007-10-10',
        'total_tracks' => 10,
        'images' => [['url' => 'https://example.test/album.png']],
        'artists' => [['id' => 'artist-id', 'name' => 'Radiohead']],
    ], $overrides);
}

function spotifyTrack(array $overrides = []): array
{
    return array_merge([
        'id' => 'track-id',
        'name' => 'Nude',
        'artists' => [['id' => 'artist-id', 'name' => 'Radiohead']],
        'album' => [
            'name' => 'In Rainbows',
            'images' => [['url' => 'https://example.test/album.png']],
        ],
    ], $overrides);
}
