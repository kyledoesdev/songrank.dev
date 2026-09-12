<?php

use App\Models\Tierlist;

/**
 * A tier list anyone may open: finished and public.
 */
function publicCompletedTierlist(array $attributes = []): Tierlist
{
    return Tierlist::factory()
        ->complete()
        ->public()
        ->create($attributes);
}

/*
| The entry arrays a Spotify search hands back, in the shape the tier list
| setup and resolution both expect.
*/

function artistEntry(array $overrides = []): array
{
    return array_merge([
        'id' => 'tame-impala-id',
        'name' => 'Tame Impala',
        'cover' => 'https://example.test/tame-impala.png',
    ], $overrides);
}

function albumEntry(array $overrides = []): array
{
    return array_merge([
        'id' => 'currents-id',
        'name' => 'Currents',
        'cover' => 'https://example.test/currents.png',
        'artist_id' => 'tame-impala-id',
        'artist_name' => 'Tame Impala',
        'release_date' => '2015-07-17',
        'total_tracks' => 13,
        'album_type' => 'album',
    ], $overrides);
}

function trackEntry(array $overrides = []): array
{
    return array_merge([
        'id' => 'less-i-know-id',
        'name' => 'The Less I Know The Better',
        'cover' => 'https://example.test/currents.png',
        'artist_id' => 'tame-impala-id',
        'artist_name' => 'Tame Impala',
        'album_name' => 'Currents',
    ], $overrides);
}
