<?php

use App\Livewire\Ranking\Ranking as RankingPage;
use App\Livewire\SongRank\SongRankProcess;
use App\Models\Ranking;
use App\Models\RankingSortingState;
use App\Models\User;
use Livewire\Livewire;

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

    test('nobody but its owner can open a ranking still being sorted', function () {
        $ranking = Ranking::factory()->create(['is_public' => true, 'is_ranked' => false]);

        RankingSortingState::create(['ranking_id' => $ranking->getKey()]);

        get(route('ranking', ['id' => $ranking->getKey()]))->assertNotFound();

        actingAs(User::factory()->createOne())
            ->get(route('ranking', ['id' => $ranking->getKey()]))
            ->assertNotFound();

        actingAs($ranking->user)
            ->get(route('ranking', ['id' => $ranking->getKey()]))
            ->assertOk();
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
        $owner = User::factory()->createOne();
        $ranking = publicCompletedRanking(attributes: ['user_id' => $owner->getKey(), 'comments_enabled' => false]);

        Livewire::actingAs(User::factory()->createOne())
            ->test(RankingPage::class, ['id' => $ranking->getKey()])
            ->assertSee(route('profile', ['id' => $owner->spotify_id]), escape: false);
    });

    test('is shown to guests viewing the ranking', function () {
        $owner = User::factory()->createOne();
        $ranking = publicCompletedRanking(attributes: ['user_id' => $owner->getKey(), 'comments_enabled' => false]);

        Livewire::test(RankingPage::class, ['id' => $ranking->getKey()])
            ->assertSee(route('profile', ['id' => $owner->spotify_id]), escape: false);
    });

    test('is hidden from the ranking creator', function () {
        $owner = User::factory()->createOne();
        $ranking = publicCompletedRanking(attributes: ['user_id' => $owner->getKey(), 'comments_enabled' => false]);

        Livewire::actingAs($owner)
            ->test(RankingPage::class, ['id' => $ranking->getKey()])
            ->assertDontSee(route('profile', ['id' => $owner->spotify_id]), escape: false);
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

describe('arriving at the show page', function () {
    test('opens from a link on another site', function (string $referrer) {
        $ranking = publicCompletedRanking();

        get(route('ranking', ['id' => $ranking->getKey()]), ['Referer' => $referrer])->assertOk();
    })->with([
        'a path with no route here' => 'https://www.reddit.com/r/music/comments/abc123/my_ranking',
        'a path one of our routes would match' => 'https://example.com/explore',
    ]);

    test('opens from a page of ours that no route matches', function () {
        $ranking = publicCompletedRanking();

        get(route('ranking', ['id' => $ranking->getKey()]), ['Referer' => url('/no-such-page')])->assertOk();
    });

    test('offers a way back to the explore page', function () {
        $ranking = publicCompletedRanking();

        get(route('ranking', ['id' => $ranking->getKey()]), ['Referer' => route('explore')])
            ->assertSee('onclick="history.back()"', escape: false);
    });

    test('offers no way back to another site', function () {
        $ranking = publicCompletedRanking();

        get(route('ranking', ['id' => $ranking->getKey()]), ['Referer' => 'https://example.com/explore'])
            ->assertOk()
            ->assertDontSee('onclick="history.back()"', escape: false);
    });
});

describe('sorting process on the show page', function () {
    test('gives an owner who has not finished the sorting process and a way home', function () {
        $owner = User::factory()->createOne();
        $ranking = Ranking::factory()->for($owner)->create(['is_ranked' => false]);

        RankingSortingState::create(['ranking_id' => $ranking->getKey()]);

        Livewire::actingAs($owner)
            ->test(RankingPage::class, ['id' => $ranking->getKey()])
            ->assertSeeLivewire(SongRankProcess::class)
            ->assertSee(route('dashboard'), escape: false);
    });
});
