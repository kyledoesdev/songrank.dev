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
        'artists' => [['id' => 'tame-impala-id', 'name' => 'Tame Impala']],
        'album' => [
            'name' => 'Currents',
            'images' => [['url' => 'https://example.test/currents.png']],
        ],
    ], $overrides);
}
