<?php

use App\Models\Ranking;
use App\Models\Review;
use App\Models\Tierlist;
use App\Models\User;

use function Pest\Laravel\actingAs;

describe('profile page', function () {
    test('shows the users completed public rankings', function () {
        $user = User::factory()->createOne();

        $ranking = publicCompletedRanking(attributes: ['user_id' => $user->getKey()]);

        expect(profilePage($user, $user)->instance()->rankings->modelKeys())->toBe([$ranking->getKey()]);
    });

    test('shows unfinished rankings to the profile owner only', function () {
        $user = User::factory()->createOne();

        $ranking = Ranking::factory()->create([
            'user_id' => $user->getKey(),
            'is_public' => true,
            'is_ranked' => false,
            'completed_at' => null,
        ]);

        expect(profilePage($user, $user)->instance()->rankings->modelKeys())->toBe([$ranking->getKey()])
            ->and(profilePage($user, User::factory()->createOne())->instance()->rankings)->toBeEmpty();
    });

    test('shows private rankings to the profile owner only', function () {
        $user = User::factory()->createOne();

        $ranking = Ranking::factory()->create([
            'user_id' => $user->getKey(),
            'is_public' => false,
            'is_ranked' => true,
            'completed_at' => now(),
        ]);

        expect(profilePage($user, $user)->instance()->rankings->modelKeys())->toBe([$ranking->getKey()])
            ->and(profilePage($user, User::factory()->createOne())->instance()->rankings)->toBeEmpty();
    });
});

describe('the profile card', function () {
    test('counts finished rankings, private ones only for their owner', function () {
        $user = User::factory()->createOne();

        publicCompletedRanking(attributes: ['user_id' => $user->getKey()]);
        Ranking::factory()->for($user)->create(['is_public' => false, 'is_ranked' => true, 'completed_at' => now()]);
        Ranking::factory()->for($user)->create(['is_public' => true]);

        expect($user->completedRankingCount())->toBe(1);

        actingAs($user);
        expect($user->completedRankingCount())->toBe(2);

        actingAs(User::factory()->createOne());
        expect($user->completedRankingCount())->toBe(1);
    });

    test('counts finished tier lists, private ones only for their owner', function () {
        $user = User::factory()->createOne();

        publicCompletedTierlist(['user_id' => $user->getKey()]);
        Tierlist::factory()->for($user)->complete()->create();
        Tierlist::factory()->for($user)->public()->create();

        expect($user->completedTierlistCount())->toBe(1);

        actingAs($user);
        expect($user->completedTierlistCount())->toBe(2);

        actingAs(User::factory()->createOne());
        expect($user->completedTierlistCount())->toBe(1);
    });

    test('counts published reviews, private ones only for their owner', function () {
        $user = User::factory()->createOne();

        Review::factory()->for($user)->published()->public()->createOne();
        Review::factory()->for($user)->published()->createOne();
        Review::factory()->for($user)->public()->createOne();

        expect($user->publishedReviewCount())->toBe(1);

        actingAs($user);
        expect($user->publishedReviewCount())->toBe(2);

        actingAs(User::factory()->createOne());
        expect($user->publishedReviewCount())->toBe(1);
    });
});
