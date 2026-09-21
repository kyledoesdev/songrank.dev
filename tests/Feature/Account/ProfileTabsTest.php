<?php

use App\Models\Tierlist;
use App\Models\User;

use function Pest\Laravel\actingAs;

describe('profile tabs', function () {
    test('both tabs are visible when user has rankings and tier lists', function () {
        $owner = kyle();

        publicCompletedRanking(attributes: ['user_id' => $owner->getKey()]);
        publicCompletedTierlist(['user_id' => $owner->getKey()]);

        actingAs($owner)
            ->get(route('profile', ['id' => $owner->spotify_id]))
            ->assertOk()
            ->assertSee('Rankings')
            ->assertSee('Tier Lists');
    });

    test('no tab bar when user has only rankings', function () {
        $owner = kyle();

        $ranking = publicCompletedRanking(attributes: ['user_id' => $owner->getKey()]);

        actingAs($owner)
            ->get(route('profile', ['id' => $owner->spotify_id]))
            ->assertOk()
            ->assertSee($ranking->name)
            ->assertDontSee("tab = 'tierlists'", escape: false);
    });

    test('no tab bar when user has only tier lists', function () {
        $owner = kyle();

        $tierlist = publicCompletedTierlist(['user_id' => $owner->getKey()]);

        actingAs($owner)
            ->get(route('profile', ['id' => $owner->spotify_id]))
            ->assertOk()
            ->assertSee($tierlist->name)
            ->assertDontSee("tab = 'rankings'", escape: false);
    });

    test('empty state when user has no content', function () {
        $owner = kyle();

        actingAs($owner)
            ->get(route('profile', ['id' => $owner->spotify_id]))
            ->assertOk()
            ->assertSee("This user hasn't created anything yet.", escape: false);
    });

    test('empty state shows dashboard link for the profile owner', function () {
        $owner = kyle();

        actingAs($owner)
            ->get(route('profile', ['id' => $owner->spotify_id]))
            ->assertOk()
            ->assertSee('Go build something great!');
    });

    test('empty state hides dashboard link for visitors', function () {
        $owner = kyle();
        $visitor = User::factory()->createOne(['is_dev' => true]);

        actingAs($visitor)
            ->get(route('profile', ['id' => $owner->spotify_id]))
            ->assertOk()
            ->assertSee("This user hasn't created anything yet.", escape: false)
            ->assertDontSee('Go build something great!');
    });

    test('visitors see only public completed tier lists', function () {
        $owner = kyle();
        $visitor = User::factory()->createOne(['is_dev' => true]);

        $publicComplete = publicCompletedTierlist(['user_id' => $owner->getKey(), 'name' => 'Public Complete List']);

        Tierlist::factory()->create([
            'user_id' => $owner->getKey(),
            'name' => 'Private List',
            'is_public' => false,
            'is_complete' => true,
            'completed_at' => now(),
        ]);

        Tierlist::factory()->create([
            'user_id' => $owner->getKey(),
            'name' => 'In Progress List',
            'is_public' => true,
            'is_complete' => false,
        ]);

        actingAs($visitor)
            ->get(route('profile', ['id' => $owner->spotify_id]))
            ->assertOk()
            ->assertSee('Public Complete List')
            ->assertDontSee('Private List')
            ->assertDontSee('In Progress List');
    });

    test('owner sees their own in-progress and private tier lists', function () {
        $owner = kyle();

        publicCompletedTierlist(['user_id' => $owner->getKey(), 'name' => 'Complete List']);

        Tierlist::factory()->create([
            'user_id' => $owner->getKey(),
            'name' => 'My WIP List',
            'is_public' => true,
            'is_complete' => false,
        ]);

        actingAs($owner)
            ->get(route('profile', ['id' => $owner->spotify_id]))
            ->assertOk()
            ->assertSee('Complete List')
            ->assertSee('My WIP List');
    });

    test('tier list tab is hidden when the feature flag is inactive', function () {
        $owner = User::factory()->createOne();

        publicCompletedRanking(attributes: ['user_id' => $owner->getKey()]);
        publicCompletedTierlist(['user_id' => $owner->getKey()]);

        actingAs($owner)
            ->get(route('profile', ['id' => $owner->spotify_id]))
            ->assertOk()
            ->assertDontSee('Tier Lists');
    });
});
