<?php

use App\Enums\ReviewType;
use App\Livewire\Leaderboards;
use App\Livewire\Navigation;
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
            ->assertSeeLivewire(Leaderboards::class);
    });

    test('authenticated users can view the leaderboards page', function () {
        actingAs(User::factory()->createOne())
            ->get(route('leaderboards'))
            ->assertOk();
    });

    test('leaderboard entries are visible on the page', function () {
        $artist = Artist::factory()->create();
        $user = User::factory()->createOne();

        $ranking = publicCompletedRanking($artist, ['user_id' => $user->getKey()]);

        Livewire::test(Leaderboards::class)
            ->assertSee($artist->cover(), escape: false)
            ->assertSee(route('profile', ['id' => $user->spotify_id]), escape: false)
            ->assertSee(route('ranking', ['id' => $ranking->getKey()]), escape: false);
    });

    test('guests see the leaderboards link in the navigation', function () {
        Livewire::test(Navigation::class)->assertSee(route('leaderboards'), escape: false);
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

        $start = $bigger->songs()->count() + 1;

        foreach (range($start, $start + 4) as $rank) {
            Song::factory()->create([
                'ranking_id' => $bigger->getKey(),
                'rank' => $rank,
            ]);
        }

        $songs = $bigger->songs()->count();

        Livewire::test(Leaderboards::class)
            ->assertViewHas('biggestRankings', fn ($entries) => $entries->pluck('name')->all() === ['Bigger Ranking', 'Smaller Ranking']
                && $entries->first()['count'] == $songs
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

        Song::factory()->count(Ranking::MAX_SONGS - $within->songs()->count())->create([
            'ranking_id' => $within->getKey(),
            'artist_id' => $artist->getKey(),
        ]);

        Song::factory()->count(Ranking::MAX_SONGS + 1 - $beyond->songs()->count())->create([
            'ranking_id' => $beyond->getKey(),
            'artist_id' => $artist->getKey(),
        ]);

        Livewire::test(Leaderboards::class)
            ->assertViewHas('biggestRankings', fn ($entries) => $entries->pluck('name')->all() === ['Within Limit Ranking']
                && $entries->first()['count'] == Ranking::MAX_SONGS);
    });
});

describe('review leaderboards', function () {
    test('are visible to guests', function () {
        Livewire::test(Leaderboards::class)
            ->assertSee("tab === 'reviews'", escape: false)
            ->assertViewHas('topReviewedArtists')
            ->assertViewHas('topReviewedAlbums')
            ->assertViewHas('topReviewedTracks');
    });

    test('appear under their own tab for signed-in users', function () {
        Livewire::actingAs(User::factory()->createOne())
            ->test(Leaderboards::class)
            ->assertSee("tab === 'reviews'", escape: false);
    });

    test('artists are ordered by public published review count', function () {
        $first = Artist::factory()->create(['artist_name' => 'Most Reviewed']);
        $second = Artist::factory()->create(['artist_name' => 'Less Reviewed']);

        reviewOf($first);
        reviewOf($first);
        reviewOf($second);

        Livewire::test(Leaderboards::class)
            ->assertViewHas('topReviewedArtists', fn ($entries) => $entries->pluck('name')->all() === ['Most Reviewed', 'Less Reviewed']
                && $entries->first()['count'] == 2);
    });

    test('albums credit their artist', function () {
        $artist = Artist::factory()->create(['artist_name' => 'Album Artist']);
        $album = Album::factory()->create(['name' => 'Great Album', 'artist_id' => $artist->getKey()]);

        reviewOf($album);

        Livewire::test(Leaderboards::class)
            ->assertViewHas('topReviewedAlbums', fn ($entries) => $entries->first()['name'] === 'Great Album'
                && $entries->first()['subtitle'] === 'Album Artist');
    });

    test('tracks are ordered by review count', function () {
        $first = Track::factory()->create(['name' => 'Big Single']);
        $second = Track::factory()->create(['name' => 'Deep Cut']);

        reviewOf($second);
        reviewOf($first);
        reviewOf($first);

        Livewire::test(Leaderboards::class)
            ->assertViewHas('topReviewedTracks', fn ($entries) => $entries->pluck('name')->all() === ['Big Single', 'Deep Cut']);
    });

    test('drafts and private reviews do not count', function () {
        $album = Album::factory()->create();

        Review::factory()->public()->create(['subject_id' => $album->getKey()]);
        Review::factory()->published()->create(['subject_id' => $album->getKey()]);

        Livewire::test(Leaderboards::class)
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
