<?php

use App\Actions\CompleteSongRankProcess;
use App\Livewire\Explorer\RankingsFeed;
use App\Models\Artist;
use App\Models\Playlist;
use App\Models\Ranking;
use App\Models\RankingSortingState;
use App\Models\Show;
use Livewire\Livewire;

use function Pest\Laravel\get;

describe('ranking visibility', function () {
    test('public completed rankings are visible', function () {
        $ranking = publicCompletedRanking();

        get(route('explore'))->assertOk();

        expect(exploredRankingKeys())->toBe([$ranking->getKey()]);
    });

    test('public uncompleted rankings are hidden', function () {
        $public = publicCompletedRanking();

        Ranking::factory()->create([
            'is_public' => true,
            'is_ranked' => false,
            'completed_at' => null,
        ]);

        expect(exploredRankingKeys())->toBe([$public->getKey()]);
    });

    test('private completed rankings are hidden', function () {
        $public = publicCompletedRanking();

        Ranking::factory()->create([
            'is_public' => false,
            'is_ranked' => true,
            'completed_at' => now(),
        ]);

        expect(exploredRankingKeys())->toBe([$public->getKey()]);
    });

    test('private uncompleted rankings are hidden', function () {
        $public = publicCompletedRanking();

        Ranking::factory()->create([
            'is_public' => false,
            'is_ranked' => false,
            'completed_at' => null,
        ]);

        expect(exploredRankingKeys())->toBe([$public->getKey()]);
    });
});

describe('explorable rankings counter', function () {
    test('shows a live count of explorable rankings', function () {
        publicCompletedRanking();
        publicCompletedRanking();

        Ranking::factory()->create([
            'is_public' => false,
            'is_ranked' => true,
            'completed_at' => now(),
        ]);

        Ranking::factory()->create([
            'is_public' => true,
            'is_ranked' => false,
            'completed_at' => null,
        ]);

        Livewire::test(RankingsFeed::class)
            ->assertViewHas('totalRankings', 2);
    });

    test('completing a ranking busts the counter cache', function () {
        cache()->put(Ranking::EXPLORE_COUNT_CACHE_KEY, 99);

        $ranking = Ranking::factory()->create([
            'is_public' => true,
            'is_ranked' => false,
            'completed_at' => null,
        ]);

        RankingSortingState::create(['ranking_id' => $ranking->getKey()]);

        (new CompleteSongRankProcess)->handle($ranking, [
            'finalSongIds' => $ranking->songs()->pluck('id')->all(),
        ]);

        expect(cache()->has(Ranking::EXPLORE_COUNT_CACHE_KEY))->toBeFalse();
    });
});

describe('searching', function () {
    test('can search rankings by artist name', function () {
        $match = Artist::factory()->create(['artist_name' => 'Matching Artist']);
        $other = Artist::factory()->create(['artist_name' => 'Other Artist']);

        $matchingRanking = publicCompletedRanking($match, ['name' => 'Matching Ranking']);

        publicCompletedRanking($other, ['name' => 'Other Ranking']);

        expect(exploredRankingKeys('Matching Artist'))->toBe([$matchingRanking->getKey()]);
    });

    test('can search rankings by playlist name', function () {
        $playlist = Playlist::factory()->create(['name' => 'Chill Vibes Playlist']);

        $matchingRanking = publicCompletedRanking($playlist, ['name' => 'Matching Playlist Ranking']);

        publicCompletedRanking(attributes: ['name' => 'Other Ranking']);

        expect(exploredRankingKeys('Chill Vibes'))->toBe([$matchingRanking->getKey()]);
    });

    test('can search rankings by show name', function () {
        $show = Show::factory()->createOne(['name' => 'True Crime Weekly']);

        $matchingRanking = publicCompletedRanking($show, ['name' => 'Matching Show Ranking']);

        publicCompletedRanking(attributes: ['name' => 'Other Ranking']);

        expect(exploredRankingKeys('True Crime Weekly'))->toBe([$matchingRanking->getKey()]);
    });
});

/**
 * The rankings the explore feed lists, after searching for $search when given.
 *
 * @return list<int>
 */
function exploredRankingKeys(?string $search = null): array
{
    $feed = Livewire::test(RankingsFeed::class);

    if (filled($search)) {
        $feed->set('search', $search)->call('performSearch');
    }

    return $feed->instance()->rankings->modelKeys();
}
