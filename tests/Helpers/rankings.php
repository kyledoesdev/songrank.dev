<?php

use App\Contracts\SpotifyEntity;
use App\Enums\RankingType;
use App\Models\Artist;
use App\Models\Ranking;
use App\Models\Song;
use App\Models\User;
use Livewire\Features\SupportTesting\Testable;

/**
 * A ranking anyone may open: finished and public.
 */
function publicCompletedRanking(?SpotifyEntity $source = null, array $attributes = []): Ranking
{
    $factory = Ranking::factory();

    if ($source) {
        $factory = $factory->for($source, 'source');
    }

    return $factory->create(array_merge([
        'is_public' => true,
        'is_ranked' => true,
        'completed_at' => now(),
    ], $attributes));
}

/**
 * Song titles that name their own correct finishing position, so a completed
 * sort can be checked by reading the titles back in order.
 */
function expectedSongTitles(int $count): array
{
    return collect(range(1, $count))
        ->mapWithKeys(fn (int $i) => [$i => "Should be number {$i}"])
        ->all();
}

function algorithmRanking(User $user, string $name, array $expectedSongTitles): Ranking
{
    $artist = Artist::factory()->create([
        'artist_name' => 'Test Artist',
        'is_podcast' => false,
    ]);

    $ranking = Ranking::create([
        'user_id' => $user->getKey(),
        'type' => RankingType::ARTIST->value,
        'source_id' => $artist->getKey(),
        'name' => $name,
        'is_ranked' => false,
        'is_public' => true,
    ]);

    foreach ($expectedSongTitles as $title) {
        Song::factory()->create([
            'ranking_id' => $ranking->getKey(),
            'artist_id' => $artist->getKey(),
            'title' => $title,
            'rank' => 0,
        ]);
    }

    return $ranking;
}

/**
 * Answers every comparison correctly, using the number each title carries.
 */
function simulateRankingComparisons(Testable $component, int $maxComparisons): void
{
    for ($i = 0; $i < $maxComparisons; $i++) {
        $leftSong = $component->get('currentSong1');
        $rightSong = $component->get('currentSong2');

        if (empty($leftSong['title']) || empty($rightSong['title'])) {
            break;
        }

        preg_match('/(\d+)/', $leftSong['title'], $leftMatches);
        preg_match('/(\d+)/', $rightSong['title'], $rightMatches);

        $leftSongRank = (int) $leftMatches[1];
        $rightSongRank = (int) $rightMatches[1];

        $winningSongId = $leftSongRank < $rightSongRank
            ? $leftSong['id']
            : $rightSong['id'];

        $component->call('chooseSong', $winningSongId);
    }
}
