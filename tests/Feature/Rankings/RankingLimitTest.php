<?php

use App\Livewire\SongRank\Setup\ArtistSetup;
use App\Livewire\SongRank\Setup\PlaylistSetup;
use App\Livewire\SongRank\Setup\ShowSetup;
use App\Models\Ranking;
use App\Models\User;
use Laravel\Pennant\Feature;
use Livewire\Livewire;

describe('the ranking allowance', function () {
    it('caps a free account at the configured limit', function () {
        $user = userWithRankings(3);

        expect($user->rankingLimit())->toBe(100)
            ->and($user->canCreateRanking())->toBeTrue();
    });

    it('reports no ceiling for a Pro account', function () {
        $user = proUser();

        expect($user->rankingLimit())->toBeNull()
            ->and($user->canCreateRanking())->toBeTrue();
    });

    it('stops exactly at the limit, not before', function () {
        config()->set('billing.ranking_limits.free', 3);

        expect(userWithRankings(2)->canCreateRanking())->toBeTrue()
            ->and(userWithRankings(3)->canCreateRanking())->toBeFalse();
    });

    it('keeps refusing past the limit', function () {
        config()->set('billing.ranking_limits.free', 3);

        expect(userWithRankings(5)->canCreateRanking())->toBeFalse();
    });

    it('counts in-progress rankings against the limit', function () {
        config()->set('billing.ranking_limits.free', 2);

        $user = User::factory()->createOne();
        Ranking::factory()->count(2)->for($user)->create(['is_ranked' => false]);

        expect($user->canCreateRanking())->toBeFalse();
    });

    it('never lets a Pro account be capped by the free limit', function () {
        config()->set('billing.ranking_limits.free', 1);

        $user = proUser();
        Ranking::factory()->count(50)->for($user)->create();

        expect($user->canCreateRanking())->toBeTrue();
    });
});

describe('the setup screen at the limit', function () {
    beforeEach(function () {
        Feature::define('songrank-pro', true);
        config()->set('billing.ranking_limits.free', 2);
    });

    it('replaces the search box with an upgrade prompt', function () {
        Livewire::actingAs(userWithRankings(2))
            ->test(ArtistSetup::class)
            ->assertSee("You've reached 2 rankings", escape: false)
            ->assertSee('Go Pro')
            ->assertDontSee('wire:keydown.enter="search"', escape: false);
    });

    it('shows the search box while there is headroom', function () {
        Livewire::actingAs(userWithRankings(1))
            ->test(ArtistSetup::class)
            ->assertSee('Choose a ranking type to get started')
            ->assertDontSee('Go Pro');
    });

    it('never blocks a Pro user', function () {
        $user = proUser();
        Ranking::factory()->count(5)->for($user)->create();

        Livewire::actingAs($user)
            ->test(ArtistSetup::class)
            ->assertSee('Choose a ranking type to get started')
            ->assertDontSee('Go Pro');
    });

    it('blocks every setup type, not just artists', function () {
        $user = userWithRankings(2);

        foreach ([ArtistSetup::class, PlaylistSetup::class, ShowSetup::class] as $component) {
            Livewire::actingAs($user)
                ->test($component)
                ->assertSee('Go Pro');
        }
    });

    it('does not block anyone while the feature is inactive', function () {
        Feature::define('songrank-pro', false);

        Livewire::actingAs(userWithRankings(50))
            ->test(ArtistSetup::class)
            ->assertSee('Choose a ranking type to get started')
            ->assertDontSee('Go Pro');
    });
});

describe('the search guard', function () {
    beforeEach(function () {
        Feature::define('songrank-pro', true);
        config()->set('billing.ranking_limits.free', 2);
    });

    it('refuses a search from a stale page without calling Spotify', function () {
        Livewire::actingAs(userWithRankings(2))
            ->test(ArtistSetup::class)
            ->set('searchTerm', 'Radiohead')
            ->call('search')
            ->assertSet('searchedArtists', null);
    });

    it('lets a search through while there is headroom', function () {
        Livewire::actingAs(userWithRankings(1))
            ->test(ArtistSetup::class)
            ->set('searchTerm', '')
            ->call('search')
            ->assertOk();
    });
});
