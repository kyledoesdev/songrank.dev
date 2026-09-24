<?php

use App\Livewire\SongRank\RankingPanel;
use App\Livewire\SongRank\Setup\ArtistSetup;
use App\Livewire\SongRank\SongRankSetup;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

describe('create page', function () {
    test('authenticated users can access the create page', function () {
        actingAs(User::factory()->createOne())
            ->get(route('rankings.create'))
            ->assertOk();
    });

    test('guests are redirected to login', function () {
        get(route('rankings.create'))
            ->assertRedirect();
    });

    test('type query parameter pre-selects the ranking type', function () {
        actingAs(User::factory()->createOne())
            ->get(route('rankings.create', ['type' => 'playlist']))
            ->assertOk()
            ->assertDontSee('Search for an artist');
    });

    test('defaults to artist setup without a type parameter', function () {
        Livewire::actingAs(User::factory()->createOne())
            ->test(SongRankSetup::class)
            ->assertSeeLivewire(ArtistSetup::class);
    });
});

describe('ranking panel', function () {
    test('shows create links for each ranking type', function () {
        Livewire::actingAs(User::factory()->createOne())
            ->test(RankingPanel::class)
            ->assertSee('Artist')
            ->assertSee('Playlist')
            ->assertSee('Podcast / Show');
    });

    test('shows upgrade prompt when ranking limit is reached', function () {
        config(['billing.ranking_limits.free' => 0]);

        Livewire::actingAs(User::factory()->createOne())
            ->test(RankingPanel::class)
            ->assertSee('go Pro for more');
    });
});
