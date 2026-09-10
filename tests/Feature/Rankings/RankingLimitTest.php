<?php

use App\Livewire\SongRank\Setup\ArtistSetup;
use App\Livewire\SongRank\Setup\PlaylistSetup;
use App\Livewire\SongRank\Setup\ShowSetup;
use App\Models\Ranking;
use App\Models\User;
use App\Services\Billing\RankingAllowance;
use Laravel\Pennant\Feature;
use Livewire\Livewire;

describe('the ranking allowance', function () {
    it('caps free accounts at the configured limit', function () {
        $allowance = new RankingAllowance(userWithRankings(3));

        expect($allowance->used())->toBe(3)
            ->and($allowance->limit())->toBe(100)
            ->and($allowance->remaining())->toBe(97)
            ->and($allowance->exceeded())->toBeFalse()
            ->and($allowance->isUnlimited())->toBeFalse();
    });

    it('reports no ceiling for a Pro account', function () {
        $allowance = new RankingAllowance(proUser());

        expect($allowance->limit())->toBeNull()
            ->and($allowance->remaining())->toBeNull()
            ->and($allowance->isUnlimited())->toBeTrue()
            ->and($allowance->exceeded())->toBeFalse()
            ->and($allowance->percentUsed())->toBeNull();
    });

    it('is exceeded exactly at the limit, not before', function () {
        config()->set('billing.ranking_limits.free', 3);

        expect((new RankingAllowance(userWithRankings(2)))->exceeded())->toBeFalse()
            ->and((new RankingAllowance(userWithRankings(3)))->exceeded())->toBeTrue();
    });

    it('never reports negative headroom past the limit', function () {
        config()->set('billing.ranking_limits.free', 3);

        expect((new RankingAllowance(userWithRankings(5)))->remaining())->toBe(0);
    });

    it('counts in-progress rankings against the limit', function () {
        $user = User::factory()->createOne();
        Ranking::factory()->count(2)->for($user)->create(['is_ranked' => false]);

        expect((new RankingAllowance($user))->used())->toBe(2);
    });

    it('summarises usage for the billing page', function () {
        expect((new RankingAllowance(userWithRankings(3)))->summary())->toBe('3 of 100')
            ->and((new RankingAllowance(proUser()))->summary())->toBe('0 rankings');
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
