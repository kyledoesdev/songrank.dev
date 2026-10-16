<?php

use Illuminate\Support\Facades\Http;

/*
| Faked Spotify responses, in the envelopes the search endpoints return.
|
| The defaults all describe Tame Impala's Currents, so a failing assertion
| reads like a record rather than like "album-1".
*/

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

/**
 * A public playlist holding the given tracks, answered the way the playlist
 * search reads it: the playlist itself, then its tracks a page at a time.
 */
function fakePlaylist(array $tracks, array $overrides = [], ?int $total = null): void
{
    $playlist = array_merge([
        'id' => 'psych-rock-id',
        'name' => 'Psych Rock',
        'description' => 'Everything Kevin Parker touched',
        'owner' => ['id' => 'playlist-owner-id', 'display_name' => 'Playlist Owner'],
        'images' => [['url' => 'https://example.test/psych-rock.png']],
    ], $overrides);

    Http::fake([
        'https://accounts.spotify.com/*' => Http::response(['access_token' => 'fresh-token']),
        "https://api.spotify.com/v1/playlists/{$playlist['id']}/tracks*" => Http::response([
            'items' => collect($tracks)->map(fn (array $track) => ['track' => $track])->all(),
        ]),
        "https://api.spotify.com/v1/playlists/{$playlist['id']}" => Http::response([
            ...$playlist,
            'tracks' => ['total' => $total ?? count($tracks)],
        ]),
    ]);
}

function spotifyArtist(array $overrides = []): array
{
    return array_merge([
        'id' => 'tame-impala-id',
        'name' => 'Tame Impala',
        'images' => [['url' => 'https://example.test/tame-impala.png']],
    ], $overrides);
}

function spotifyAlbum(array $overrides = []): array
{
    return array_merge([
        'id' => 'currents-id',
        'name' => 'Currents',
        'album_type' => 'album',
        'release_date' => '2015-07-17',
        'total_tracks' => 13,
        'images' => [['url' => 'https://example.test/currents.png']],
        'artists' => [['id' => 'tame-impala-id', 'name' => 'Tame Impala']],
    ], $overrides);
}

function spotifyTrack(array $overrides = []): array
{
    return array_merge([
        'id' => 'less-i-know-id',
        'name' => 'The Less I Know The Better',
        'artists' => [['id' => 'tame-impala-id', 'name' => 'Tame Impala', 'type' => 'artist']],
        'album' => [
            'name' => 'Currents',
            'images' => [['url' => 'https://example.test/currents.png']],
        ],
    ], $overrides);
}
