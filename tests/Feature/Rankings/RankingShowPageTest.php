<?php

use App\Models\Ranking;
use App\Models\RankingSortingState;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

describe('ranking show page access', function () {
    test('non-owner can view a public completed ranking', function () {
        $visitor = User::factory()->createOne();

        $ranking = publicCompletedRanking();

        actingAs($visitor)
            ->get(route('ranking', ['id' => $ranking->getKey()]))
            ->assertOk();
    });

    test('non-owner cannot view a private ranking', function () {
        $visitor = User::factory()->createOne();

        $ranking = Ranking::factory()->create([
            'is_public' => false,
            'is_ranked' => true,
        ]);

        actingAs($visitor)
            ->get(route('ranking', ['id' => $ranking->getKey()]))
            ->assertStatus(404);
    });

    test('owner can view their own private ranking', function () {
        $user = User::factory()->createOne();

        $ranking = Ranking::factory()->create([
            'user_id' => $user->getKey(),
            'is_public' => false,
            'is_ranked' => true,
        ]);

        actingAs($user)
            ->get(route('ranking', ['id' => $ranking->getKey()]))
            ->assertOk();
    });
});

describe('creator banner on the show page', function () {
    test('is shown to other users viewing the ranking', function () {
        $owner = User::factory()->createOne(['name' => 'Ranking Creator']);
        $visitor = User::factory()->createOne();

        $ranking = publicCompletedRanking(attributes: ['user_id' => $owner->getKey()]);

        actingAs($visitor)
            ->get(route('ranking', ['id' => $ranking->getKey()]))
            ->assertOk()
            ->assertSee('Ranked by')
            ->assertSee('Ranking Creator');
    });

    test('is shown to guests viewing the ranking', function () {
        $owner = User::factory()->createOne(['name' => 'Ranking Creator']);

        $ranking = publicCompletedRanking(attributes: ['user_id' => $owner->getKey()]);

        get(route('ranking', ['id' => $ranking->getKey()]))
            ->assertOk()
            ->assertSee('Ranked by')
            ->assertSee('Ranking Creator');
    });

    test('is hidden from the ranking creator', function () {
        $owner = User::factory()->createOne();

        $ranking = publicCompletedRanking(attributes: ['user_id' => $owner->getKey()]);

        actingAs($owner)
            ->get(route('ranking', ['id' => $ranking->getKey()]))
            ->assertOk()
            ->assertDontSee('Ranked by');
    });
});

describe('header actions on the show page', function () {
    test('show the edit link to the owner', function () {
        $owner = User::factory()->createOne();

        $ranking = publicCompletedRanking(attributes: ['user_id' => $owner->getKey()]);

        actingAs($owner)
            ->get(route('ranking', ['id' => $ranking->getKey()]))
            ->assertOk()
            ->assertSee(route('rank.edit', ['id' => $ranking->getKey()]));
    });

    test('hide the edit link from everyone else', function () {
        $ranking = publicCompletedRanking();

        actingAs(User::factory()->createOne())
            ->get(route('ranking', ['id' => $ranking->getKey()]))
            ->assertOk()
            ->assertDontSee(route('rank.edit', ['id' => $ranking->getKey()]));
    });
});

describe('sorting process on the show page', function () {
    test('shows the ranking name and a way home to an owner who has not finished', function () {
        $owner = User::factory()->createOne();

        $ranking = Ranking::factory()->create([
            'user_id' => $owner->getKey(),
            'name' => 'Unfinished Business',
            'is_ranked' => false,
        ]);

        RankingSortingState::create(['ranking_id' => $ranking->getKey()]);

        actingAs($owner)
            ->get(route('ranking', ['id' => $ranking->getKey()]))
            ->assertOk()
            ->assertSeeInOrder(['Unfinished Business', 'Progress will be saved automatically', route('dashboard')]);
    });
});
