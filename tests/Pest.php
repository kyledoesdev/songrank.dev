<?php

use App\Contracts\Rankable;
use App\Enums\RankingType;
use App\Models\Artist;
use App\Models\ProLicense;
use App\Models\Ranking;
use App\Models\Song;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Filament');

pest()->extend(TestCase::class)
    ->in('Platform');

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function publicCompletedRanking(?Rankable $source = null, array $attributes = []): Ranking
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

function kyle(): User
{
    return User::factory()->createOne([
        'name' => 'Kyle',
        'is_dev' => true,
    ]);
}

/**
 * A user holding an active, paid Pro license.
 */
function proUser(array $attributes = []): User
{
    $user = User::factory()->createOne($attributes);

    ProLicense::factory()->active()->for($user)->create();

    /* ProLicenseObserver has since flipped is_pro on the row, not on this instance. */
    return $user->fresh();
}

/**
 * A user sitting on exactly $count rankings, for allowance assertions.
 */
function userWithRankings(int $count, array $attributes = []): User
{
    $user = User::factory()->createOne($attributes);

    Ranking::factory()->count($count)->for($user)->create();

    return $user;
}

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
