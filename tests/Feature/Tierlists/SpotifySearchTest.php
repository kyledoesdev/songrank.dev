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

        $album = (new SearchAlbums)->handle(searcher(), 'currents')->first();

        expect($album)->toBe([
            'id' => 'currents-id',
            'name' => 'Currents',
            'cover' => 'https://example.test/currents.png',
            'artist_id' => 'tame-impala-id',
            'artist_name' => 'Tame Impala',
            'release_date' => '2015-07-17',
            'total_tracks' => 13,
            'album_type' => 'album',
        ]);
    });

    it('drops results with no artwork, which would sit on the board as blank squares', function () {
        fakeSearch('albums', [
            spotifyAlbum(),
            spotifyAlbum(['id' => 'coverless-id', 'images' => []]),
        ]);

        $albums = (new SearchAlbums)->handle(searcher(), 'currents');

        expect($albums)->toHaveCount(1)
            ->and($albums->first()['id'])->toBe('currents-id');
    });

    it('returns nothing when Spotify falls over', function () {
        Http::fake([
            'https://accounts.spotify.com/*' => Http::response(['access_token' => 'fresh-token']),
            'https://api.spotify.com/v1/search*' => fn () => throw new Exception('spotify is down'),
        ]);

        expect((new SearchAlbums)->handle(searcher(), 'currents'))->toBeNull();
    });
});

describe('searching tracks', function () {
    it('maps a result onto the shape a track entry is stored from', function () {
        fakeSearch('tracks', [spotifyTrack()]);

        $track = (new SearchTracks)->handle(searcher(), 'the less i know')->first();

        expect($track)->toBe([
            'id' => 'less-i-know-id',
            'name' => 'The Less I Know The Better',
            'cover' => 'https://example.test/currents.png',
            'artist_id' => 'tame-impala-id',
            'artist_name' => 'Tame Impala',
            'album_name' => 'Currents',
        ]);
    });

    it('takes the artwork from the album the track belongs to', function () {
        fakeSearch('tracks', [spotifyTrack(['album' => ['name' => 'No Art', 'images' => []]])]);

        expect((new SearchTracks)->handle(searcher(), 'the less i know'))->toBeEmpty();
    });

});

describe('importing a discography', function () {
    it('returns every release the artist has', function () {
        fakeDiscography(1, [
            spotifyAlbum(),
            spotifyAlbum(['id' => 'patience-id', 'name' => 'Patience', 'album_type' => 'single']),
        ]);

        $albums = (new GetArtistAlbums)->handle(searcher(), 'tame-impala-id');

        expect($albums->pluck('id')->all())->toBe(['currents-id', 'patience-id'])
            ->and($albums->last()['album_type'])->toBe('single');
    });

    it('collapses the same record released into several markets', function () {
        fakeDiscography(1, [
            spotifyAlbum(),
            spotifyAlbum(['id' => 'currents-id-jp', 'name' => 'CURRENTS']),
            spotifyAlbum(['id' => 'currents-id-uk', 'name' => 'Currents']),
        ]);

        $albums = (new GetArtistAlbums)->handle(searcher(), 'tame-impala-id');

        expect($albums)->toHaveCount(1)
            ->and($albums->first()['id'])->toBe('currents-id');
    });

    it('walks every page of a long discography', function () {
        Http::fake([
            'https://accounts.spotify.com/*' => Http::response(['access_token' => 'fresh-token']),
            'https://api.spotify.com/v1/artists/*' => fn (Request $request) => Http::response([
                'total' => 51,
                'items' => str_contains($request->url(), 'offset=50')
                    ? [spotifyAlbum(['id' => 'slow-rush-id', 'name' => 'The Slow Rush'])]
                    : [spotifyAlbum()],
            ]),
        ]);

        $albums = (new GetArtistAlbums)->handle(searcher(), 'tame-impala-id');

        expect($albums->pluck('id')->all())->toBe(['currents-id', 'slow-rush-id']);
    });

    it('returns nothing when Spotify falls over', function () {
        Http::fake([
            'https://accounts.spotify.com/*' => Http::response(['access_token' => 'fresh-token']),
            'https://api.spotify.com/v1/artists/*' => fn () => throw new Exception('spotify is down'),
        ]);

        expect((new GetArtistAlbums)->handle(searcher(), 'tame-impala-id'))->toBeNull();
    });
});

function searcher(): User
{
    return User::factory()->createOne();
}
