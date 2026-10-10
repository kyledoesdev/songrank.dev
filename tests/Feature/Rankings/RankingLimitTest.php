<?php

use App\Livewire\SongRank\Setup\ArtistSetup;
use App\Livewire\SongRank\Setup\PlaylistSetup;
use App\Livewire\SongRank\Setup\ShowSetup;
use App\Models\Ranking;
use App\Models\User;
use Livewire\Features\SupportTesting\Testable;
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
        config()->set('billing.ranking_limits.free', 2);
    });

    it('replaces the search box with an upgrade prompt', function () {
        Livewire::actingAs(userWithRankings(2))
            ->test(ArtistSetup::class)
            ->assertSee(route('billing'), escape: false)
            ->assertDontSee('wire:keydown.enter="search"', escape: false);
    });

    it('shows the search box while there is headroom', function () {
        Livewire::actingAs(userWithRankings(1))
            ->test(ArtistSetup::class)
            ->assertDontSee(route('billing'), escape: false);
    });

    it('never blocks a Pro user', function () {
        $user = proUser();
        Ranking::factory()->count(5)->for($user)->create();

        Livewire::actingAs($user)
            ->test(ArtistSetup::class)
            ->assertDontSee(route('billing'), escape: false);
    });

    it('blocks every setup type, not just artists', function () {
        $user = userWithRankings(2);

        foreach ([ArtistSetup::class, PlaylistSetup::class, ShowSetup::class] as $component) {
            Livewire::actingAs($user)
                ->test($component)
                ->assertSee(route('billing'), escape: false);
        }
    });
});

describe('the search guard', function () {
    beforeEach(function () {
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

describe('starting a ranking', function () {
    beforeEach(function () {
        config()->set('billing.ranking_limits.free', 2);
    });

    it('will not start one past the limit, even without the confirmation', function (string $setup) {
        $user = userWithRankings(2);

        $component = rankingSetupReadyToBegin($setup, $user)
            ->call('beginRanking')
            ->assertNoRedirect();

        expect($user->rankings()->count())->toBe(2)
            ->and(alertsFrom($component)->pluck('icon')->all())->toBe(['error']);
    })->with('ranking setups');

    it('starts one while there is headroom', function (string $setup) {
        $user = userWithRankings(1);

        rankingSetupReadyToBegin($setup, $user)
            ->call('beginRanking')
            ->assertRedirect();

        expect($user->rankings()->count())->toBe(2);
    })->with('ranking setups');

    it('will not start one with a track count a ranking cannot take', function (int $tracks) {
        $user = User::factory()->createOne();

        $component = rankingSetupReadyToBegin(PlaylistSetup::class, $user, $tracks)
            ->call('beginRanking')
            ->assertNoRedirect();

        expect($user->rankings()->exists())->toBeFalse()
            ->and(alertsFrom($component)->pluck('icon')->all())->toBe(['error']);
    })->with([
        'too few to compare' => Ranking::MIN_SONGS - 1,
        'more than the cap' => Ranking::MAX_SONGS + 1,
    ]);

    it('asks before starting', function () {
        $component = rankingSetupReadyToBegin(PlaylistSetup::class, userWithRankings(1))
            ->call('confirmBeginRanking');

        expect(alertsFrom($component, 'confirm')->pluck('action')->all())->toBe(['beginRanking']);
    });

    it('will not ask once the limit is reached', function () {
        $component = rankingSetupReadyToBegin(PlaylistSetup::class, userWithRankings(2))
            ->call('confirmBeginRanking');

        expect(alertsFrom($component, 'confirm'))->toBeEmpty()
            ->and(alertsFrom($component)->pluck('icon')->all())->toBe(['error']);
    });
});

dataset('ranking setups', [
    'an artist' => ArtistSetup::class,
    'a playlist' => PlaylistSetup::class,
    'a show' => ShowSetup::class,
]);

/**
 * A setup screen with its source picked and $tracks tracks to rank, one call
 * away from starting.
 */
function rankingSetupReadyToBegin(string $setup, User $user, int $tracks = 3): Testable
{
    [$property, $source] = match ($setup) {
        ArtistSetup::class => ['selectedArtist', [
            'id' => 'local-natives-id',
            'name' => 'Local Natives',
            'cover' => 'https://example.test/local-natives.png',
        ]],
        PlaylistSetup::class => ['selectedPlaylist', [
            'id' => 'road-trip-id',
            'name' => 'Road Trip',
            'description' => 'Songs for the car',
            'creator' => ['display_name' => 'creator-name', 'id' => 'creator-id'],
            'cover' => 'https://example.test/road-trip.png',
            'track_count' => $tracks,
        ]],
        ShowSetup::class => ['selectedShow', [
            'id' => 'song-exploder-id',
            'name' => 'Song Exploder',
            'publisher' => 'Hrishikesh Hirway',
            'publisher_image' => 'https://example.test/publisher.png',
            'description' => 'Musicians take apart their songs',
            'cover' => 'https://example.test/song-exploder.png',
            'episode_count' => $tracks,
            'data' => [],
        ]],
    };

    return Livewire::actingAs($user)
        ->test($setup)
        ->set($property, $source)
        ->set('selectedTracks', collect(range(1, $tracks))->map(fn (int $number) => [
            'id' => "track-{$number}-id",
            'name' => "Track {$number}",
            'cover' => 'https://example.test/track.png',
            'uuid' => str()->uuid()->toString(),
            'album_name' => 'Hummingbird',
            'album_id' => 'hummingbird-id',
            'album_type' => 'album',
            'artist_id' => 'local-natives-id',
            'artist_name' => 'Local Natives',
            'is_podcast' => false,
        ]))
        ->set('form.name', 'Ready to Begin');
}
