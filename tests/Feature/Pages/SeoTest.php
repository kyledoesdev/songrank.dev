<?php

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

describe('explore page seo', function () {
    test('sets meta description and schema', function () {
        get(route('explore'))
            ->assertOk()
            ->assertSee('<meta name="description" content="Browse public rankings, tier lists, and reviews from the SongRank community">', escape: false)
            ->assertSee('"@type":"CollectionPage"', escape: false);
    });
});

describe('leaderboards page seo', function () {
    test('sets meta description and schema', function () {
        get(route('leaderboards'))
            ->assertOk()
            ->assertSee('<meta name="description" content="See the most ranked artists, top creators, biggest rankings, and most reviewed music on SongRank">', escape: false)
            ->assertSee('"@type":"ItemList"', escape: false);
    });
});

describe('about page seo', function () {
    test('sets meta description', function () {
        get(route('about'))
            ->assertOk()
            ->assertSee('<meta name="description" content="Learn about SongRank, the best place to rank, review, and catalog your favorite music">', escape: false);
    });
});

describe('profile page seo', function () {
    test('sets meta description and profile schema', function () {
        $user = User::factory()->create(['name' => 'Taylor']);

        actingAs($user)->get(route('profile', ['id' => $user->spotify_id]))
            ->assertOk()
            ->assertSee('Taylor&#039;s profile on SongRank', escape: false)
            ->assertSee('"@type":"ProfilePage"', escape: false);
    });
});

describe('llms.txt link', function () {
    test('layout includes llms.txt link tag', function () {
        get(route('welcome'))
            ->assertOk()
            ->assertSee('<link rel="help" type="text/plain" href="/llms.txt">', escape: false);
    });
});
