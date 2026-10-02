<?php

use App\Enums\ReviewType;
use App\Livewire\Leaderboards;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Playlist;
use App\Models\Ranking;
use App\Models\Review;
use App\Models\Show;
use App\Models\Song;
use App\Models\Track;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

describe('page access', function () {
    test('guests can view the leaderboards page', function () {
        get(route('leaderboards'))
            ->assertOk()
            ->assertSee('Top Ranked Artists')
            ->assertSee('Top Creators')
            ->assertSee('Rankings with Most Songs');
    });

    test('authenticated users can view the leaderboards page', function () {
        actingAs(User::factory()->createOne())
            ->get(route('leaderboards'))
            ->assertOk()
            ->assertSee('Top Ranked Artists')
            ->assertSee('Top Creators')
            ->assertSee('Rankings with Most Songs');
    });

    test('leaderboard entries are visible on the page', function () {
        $artist = Artist::factory()->create(['artist_name' => 'Page Artist']);
        $user = User::factory()->createOne(['name' => 'Page Creator']);

        publicCompletedRanking($artist, [
            'user_id' => $user->getKey(),
            'name' => 'Page Ranking',
        ]);

        get(route('leaderboards'))
            ->assertOk()
            ->assertSee('Page Artist')
            ->assertSee('Page Creator')
            ->assertSee('Page Ranking');
    });

    test('guests see the leaderboards link in the navigation', function () {
        get(route('explore'))
            ->assertOk()
            ->assertSee('Leaderboards');
    });
});

describe('top ranked artists', function () {
    test('artists are ordered by public completed ranking count', function () {
        $first = Artist::factory()->create(['artist_name' => 'First Artist']);
        $second = Artist::factory()->create(['artist_name' => 'Second Artist']);

        publicCompletedRanking($first);
        publicCompletedRanking($first);
        publicCompletedRanking($second);

        Livewire::test(Leaderboards::class)
            ->assertViewHas('topArtists', fn ($entries) => $entries->pluck('name')->all() === ['First Artist', 'Second Artist']
                && $entries->first()['count'] == 2);
    });

    test('artists with only private or uncompleted rankings are not on the leaderboard', function () {
        $private = Artist::factory()->create(['artist_name' => 'Private Artist']);
        $unfinished = Artist::factory()->create(['artist_name' => 'Unfinished Artist']);

        Ranking::factory()->artist($private)->create([
            'is_public' => false,
            'is_ranked' => true,
            'completed_at' => now(),
        ]);

        Ranking::factory()->artist($unfinished)->create([
            'is_public' => true,
            'is_ranked' => false,
            'completed_at' => null,
        ]);

        Livewire::test(Leaderboards::class)
            ->assertViewHas('topArtists', fn ($entries) => $entries->isEmpty());
    });
});

describe('top creators', function () {
    test('creators are ordered by public completed ranking count and private-only creators are excluded', function () {
        $prolific = User::factory()->createOne(['name' => 'Prolific Creator']);
        $casual = User::factory()->createOne(['name' => 'Casual Creator']);
        $private = User::factory()->createOne(['name' => 'Private Creator']);

        publicCompletedRanking(attributes: ['user_id' => $prolific->getKey()]);
        publicCompletedRanking(attributes: ['user_id' => $prolific->getKey()]);
        publicCompletedRanking(attributes: ['user_id' => $casual->getKey()]);

        Ranking::factory()->create([
            'user_id' => $private->getKey(),
            'is_public' => false,
            'is_ranked' => true,
            'completed_at' => now(),
        ]);

        Livewire::test(Leaderboards::class)
            ->assertViewHas('topCreators', fn ($entries) => $entries->pluck('name')->all() === ['Prolific Creator', 'Casual Creator']);
    });

    test('sections are capped at ten entries with ties broken by name', function () {
        foreach (range('a', 'k') as $letter) {
            $user = User::factory()->createOne(['name' => "creator-{$letter}"]);

            publicCompletedRanking(attributes: ['user_id' => $user->getKey()]);
        }

        Livewire::test(Leaderboards::class)
            ->assertViewHas('topCreators', fn ($entries) => $entries->count() === 10
                && $entries->first()['name'] === 'creator-a'
                && ! $entries->contains('name', 'creator-k'));
    });
});

describe('rankings with most songs', function () {
    test('rankings are ordered by song count and credit their creator', function () {
        $creator = User::factory()->createOne(['name' => 'List Maker']);

        $bigger = publicCompletedRanking(attributes: [
            'user_id' => $creator->getKey(),
            'name' => 'Bigger Ranking',
        ]);

        publicCompletedRanking(Playlist::factory()->createOne(), ['name' => 'Smaller Ranking']);

        foreach (range(11, 15) as $rank) {
            Song::factory()->create([
                'ranking_id' => $bigger->getKey(),
                'rank' => $rank,
            ]);
        }

        Livewire::test(Leaderboards::class)
            ->assertViewHas('biggestRankings', fn ($entries) => $entries->pluck('name')->all() === ['Bigger Ranking', 'Smaller Ranking']
                && $entries->first()['count'] == 15
                && $entries->first()['subtitle'] === 'List Maker'
                && filled($entries->last()['image']));
    });

    test('show rankings are included', function () {
        $show = Show::factory()->createOne(['name' => 'A Podcast Show']);

        publicCompletedRanking($show, ['name' => 'Show Ranking']);

        publicCompletedRanking(attributes: ['name' => 'Music Ranking']);

        Livewire::test(Leaderboards::class)
            ->assertViewHas('biggestRankings', fn ($entries) => $entries->pluck('name')->all() === ['Music Ranking', 'Show Ranking']);
    });

    test('rankings with more than 500 songs are excluded', function () {
        $artist = Artist::factory()->create();

        $within = publicCompletedRanking(attributes: ['name' => 'Within Limit Ranking']);
        $beyond = publicCompletedRanking(attributes: ['name' => 'Beyond Limit Ranking']);

        Song::factory()->count(490)->create([
            'ranking_id' => $within->getKey(),
            'artist_id' => $artist->getKey(),
        ]);

        Song::factory()->count(491)->create([
            'ranking_id' => $beyond->getKey(),
            'artist_id' => $artist->getKey(),
        ]);

        Livewire::test(Leaderboards::class)
            ->assertViewHas('biggestRankings', fn ($entries) => $entries->pluck('name')->all() === ['Within Limit Ranking']
                && $entries->first()['count'] == 500);
    });
});

describe('review leaderboards', function () {
    test('are visible to guests', function () {
        get(route('leaderboards'))
            ->assertOk()
            ->assertSee("tab === 'reviews'", escape: false)
            ->assertSee('Top Reviewed Artists')
            ->assertSee('Top Reviewed Albums')
            ->assertSee('Top Reviewed Tracks');
    });

    test('appear under their own tab for users with the reviews feature', function () {
        actingAs(kyle())
            ->get(route('leaderboards'))
            ->assertOk()
            ->assertSee("tab === 'reviews'", escape: false)
            ->assertSee('Top Reviewed Artists')
            ->assertSee('Top Reviewed Albums')
            ->assertSee('Top Reviewed Tracks');
    });

    test('artists are ordered by public published review count', function () {
        $first = Artist::factory()->create(['artist_name' => 'Most Reviewed']);
        $second = Artist::factory()->create(['artist_name' => 'Less Reviewed']);

        reviewOf($first);
        reviewOf($first);
        reviewOf($second);

        Livewire::actingAs(kyle())
            ->test(Leaderboards::class)
            ->assertViewHas('topReviewedArtists', fn ($entries) => $entries->pluck('name')->all() === ['Most Reviewed', 'Less Reviewed']
                && $entries->first()['count'] == 2);
    });

    test('albums credit their artist', function () {
        $artist = Artist::factory()->create(['artist_name' => 'Album Artist']);
        $album = Album::factory()->create(['name' => 'Great Album', 'artist_id' => $artist->getKey()]);

        reviewOf($album);

        Livewire::actingAs(kyle())
            ->test(Leaderboards::class)
            ->assertViewHas('topReviewedAlbums', fn ($entries) => $entries->first()['name'] === 'Great Album'
                && $entries->first()['subtitle'] === 'Album Artist');
    });

    test('tracks are ordered by review count', function () {
        $first = Track::factory()->create(['name' => 'Big Single']);
        $second = Track::factory()->create(['name' => 'Deep Cut']);

        reviewOf($second);
        reviewOf($first);
        reviewOf($first);

        Livewire::actingAs(kyle())
            ->test(Leaderboards::class)
            ->assertViewHas('topReviewedTracks', fn ($entries) => $entries->pluck('name')->all() === ['Big Single', 'Deep Cut']);
    });

    test('drafts and private reviews do not count', function () {
        $album = Album::factory()->create();

        Review::factory()->public()->create(['subject_id' => $album->getKey()]);
        Review::factory()->published()->create(['subject_id' => $album->getKey()]);

        Livewire::actingAs(kyle())
            ->test(Leaderboards::class)
            ->assertViewHas('topReviewedAlbums', fn ($entries) => $entries->isEmpty());
    });
});

function reviewOf(Artist|Album|Track $subject): Review
{
    $type = match (true) {
        $subject instanceof Artist => ReviewType::ARTIST,
        $subject instanceof Album => ReviewType::ALBUM,
        $subject instanceof Track => ReviewType::TRACK,
    };

    return Review::factory()->ofType($type)->published()->public()->create([
        'subject_id' => $subject->getKey(),
    ]);
}
