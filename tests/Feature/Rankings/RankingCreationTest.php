<?php

use App\Enums\RankingType;
use App\Livewire\SongRank\Setup\ArtistSetup;
use App\Livewire\SongRank\Setup\PlaylistSetup;
use App\Livewire\SongRank\Setup\ShowSetup;
use App\Livewire\SongRank\SongRankSetup;
use App\Models\Artist;
use App\Models\Playlist;
use App\Models\Ranking;
use App\Models\Show;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

use function Pest\Laravel\get;

beforeEach(function () {
    Artist::factory()->create();
    User::factory()->createOne();
});

describe('ranking setup access', function () {
    test('unauthenticated user cannot access ranking setup', function () {
        get(route('dashboard'))->assertRedirect(route('welcome'));
    });
});

describe('creating rankings', function () {
    test('can create a ranking for an artist', function () {
        expect(Artist::where('artist_id', 'test-artist-id')->exists())->toBeFalse();
        expect(Ranking::where('name', 'Local Natives List')->exists())->toBeFalse();

        Livewire::actingAs(User::factory()->createOne())
            ->test(ArtistSetup::class)
            ->set('selectedArtist', [
                'id' => 'test-artist-id',
                'name' => 'Local Natives',
                'cover' => 'https://api.dicebear.com/7.x/initials/svg?seed=testing',
            ])
            ->set('selectedTracks', collect([
                [
                    'id' => 'ceilings-id',
                    'name' => 'Ceilings',
                    'cover' => 'https://api.dicebear.com/7.x/initials/svg?seed=ceilings',
                    'uuid' => str()->uuid()->toString(),
                    'album_name' => 'Hummingbird',
                    'album_id' => 'hummingbird-id',
                    'album_type' => 'album',
                ],
                [
                    'id' => 'sun-hands-id',
                    'name' => 'Sun Hands',
                    'cover' => 'https://api.dicebear.com/7.x/initials/svg?seed=sun_hands',
                    'uuid' => str()->uuid()->toString(),
                    'album_name' => 'Gorilla Manor',
                    'album_id' => 'gorilla-manor-id',
                    'album_type' => 'album',
                ],
                [
                    'id' => 'featherweight-id',
                    'name' => 'Featherweight',
                    'cover' => 'https://api.dicebear.com/7.x/initials/svg?seed=featherweight',
                    'uuid' => str()->uuid()->toString(),
                    'album_name' => 'Time will wait for no one',
                    'album_id' => 'time-will-wait-for-no-one-id',
                    'album_type' => 'album',
                ],
            ]))
            ->set('form.name', 'Local Natives List')
            ->set('form.is_public', true)
            ->call('beginRanking')
            ->assertHasNoErrors();

        $artist = Artist::where('artist_id', 'test-artist-id')->first();
        $ranking = Ranking::where('name', 'Local Natives List')->first();

        expect($artist)->not->toBeNull();
        expect($artist->artist_name)->toBe('Local Natives');

        expect($ranking)->not->toBeNull();
        expect($ranking->songs()->count())->toBe(3);

        expect($ranking->type)->toBe(RankingType::ARTIST);
        expect($ranking->source)->toBeInstanceOf(Artist::class);
        expect($ranking->source->is($artist))->toBeTrue();
    });

    test('can create a ranking for a playlist', function () {
        expect(Playlist::where('playlist_id', 'test-playlist-id')->exists())->toBeFalse();
        expect(Ranking::where('name', 'Test Playlist List')->exists())->toBeFalse();

        Livewire::actingAs(User::factory()->createOne())
            ->test(PlaylistSetup::class)
            ->set('selectedPlaylist', [
                'id' => 'test-playlist-id',
                'name' => 'Playlist Name',
                'description' => 'Playlist Description',
                'creator' => [
                    'display_name' => 'test-creator-name',
                    'id' => 'test-creator-id',
                ],
                'cover' => 'playlist-cover',
                'track_count' => 3,
            ])
            ->set('selectedTracks', collect([
                [
                    'id' => 'ceilings-id',
                    'name' => 'Ceilings',
                    'cover' => 'https://api.dicebear.com/7.x/initials/svg?seed=ceilings',
                    'uuid' => str()->uuid()->toString(),
                    'artist_id' => 'local-natives-id',
                    'artist_name' => 'Local Natives',
                    'is_podcast' => false,
                ],
                [
                    'id' => 'sun-hands-id',
                    'name' => 'Sun Hands',
                    'cover' => 'https://api.dicebear.com/7.x/initials/svg?seed=sun_hands',
                    'uuid' => str()->uuid()->toString(),
                    'artist_id' => 'local-natives-id',
                    'artist_name' => 'Local Natives',
                    'is_podcast' => false,
                ],
                [
                    'id' => 'featherweight-id',
                    'name' => 'Featherweight',
                    'cover' => 'https://api.dicebear.com/7.x/initials/svg?seed=featherweight',
                    'uuid' => str()->uuid()->toString(),
                    'artist_id' => 'local-natives-id',
                    'artist_name' => 'Local Natives',
                    'is_podcast' => false,
                ],
            ]))
            ->set('form.name', 'Test Playlist List')
            ->set('form.is_public', true)
            ->call('beginRanking')
            ->assertHasNoErrors();

        $playlist = Playlist::where('playlist_id', 'test-playlist-id')->first();
        $ranking = Ranking::where('name', 'Test Playlist List')->first();

        expect($playlist)->not->toBeNull();
        expect($playlist->creator_id)->toBe('test-creator-id');
        expect($playlist->creator_name)->toBe('test-creator-name');
        expect($playlist->name)->toBe('Playlist Name');
        expect($playlist->description)->toBe('Playlist Description');
        expect($playlist->cover)->toBe('playlist-cover');
        expect($playlist->track_count)->toBe(3);

        expect($ranking)->not->toBeNull();
        expect($ranking->songs()->count())->toBe(3);

        $localNatives = Artist::where('artist_id', 'local-natives-id')->firstOrFail();

        expect($ranking->songs()->pluck('artist_id')->unique()->all())->toBe([$localNatives->getKey()]);

        expect($ranking->type)->toBe(RankingType::PLAYLIST);
        expect($ranking->source)->toBeInstanceOf(Playlist::class);
        expect($ranking->source->is($playlist))->toBeTrue();
    });

    test('can create a ranking for a show', function () {
        expect(Show::where('show_id', 'test-show-id')->exists())->toBeFalse();
        expect(Ranking::where('name', 'Test Show List')->exists())->toBeFalse();

        Livewire::actingAs(User::factory()->createOne())
            ->test(ShowSetup::class)
            ->set('selectedShow', [
                'id' => 'test-show-id',
                'name' => 'Test Show',
                'publisher' => 'Test Publisher',
                'publisher_image' => 'https://api.dicebear.com/7.x/initials/svg?seed=publisher',
                'description' => 'A test show',
                'cover' => 'show-cover',
                'episode_count' => 3,
                'data' => [],
            ])
            ->set('selectedTracks', collect([
                [
                    'id' => 'episode-1-id',
                    'name' => 'Episode 1',
                    'cover' => 'https://api.dicebear.com/7.x/initials/svg?seed=ep1',
                    'uuid' => str()->uuid()->toString(),
                ],
                [
                    'id' => 'episode-2-id',
                    'name' => 'Episode 2',
                    'cover' => 'https://api.dicebear.com/7.x/initials/svg?seed=ep2',
                    'uuid' => str()->uuid()->toString(),
                ],
                [
                    'id' => 'episode-3-id',
                    'name' => 'Episode 3',
                    'cover' => 'https://api.dicebear.com/7.x/initials/svg?seed=ep3',
                    'uuid' => str()->uuid()->toString(),
                ],
            ]))
            ->set('form.name', 'Test Show List')
            ->set('form.is_public', true)
            ->call('beginRanking')
            ->assertHasNoErrors();

        $show = Show::where('show_id', 'test-show-id')->first();
        $ranking = Ranking::where('name', 'Test Show List')->first();

        expect($show)->not->toBeNull();
        expect($show->name)->toBe('Test Show');
        expect($show->publisher)->toBe('Test Publisher');

        expect($ranking)->not->toBeNull();
        expect($ranking->songs()->count())->toBe(3);

        expect($ranking->type)->toBe(RankingType::SHOW);
        expect($ranking->source)->toBeInstanceOf(Show::class);
        expect($ranking->source->is($show))->toBeTrue();
    });

    test('a ranking whose source no longer exists still resolves its type', function () {
        $ranking = Ranking::factory()->createOne(['source_id' => null]);

        expect($ranking->type)->toBe(RankingType::ARTIST);
        expect($ranking->source)->toBeNull();
    });
});

describe('ranking setup searching', function () {
    test('cannot search for an artist with an empty search term', function () {
        Http::fake();

        Livewire::actingAs(User::factory()->createOne())
            ->test(ArtistSetup::class)
            ->set('searchTerm', '')
            ->call('search')
            ->assertSet('searchedArtists', null)
            ->assertSet('selectedTracks', fn ($tracks) => $tracks->isEmpty());

        Http::assertNothingSent();
    });

    test('cannot search for a playlist with an invalid URL', function () {
        Http::fake();

        Livewire::actingAs(User::factory()->createOne())
            ->test(PlaylistSetup::class)
            ->set('searchTerm', 'abcdxyz')
            ->call('search')
            ->assertSet('selectedPlaylist', [])
            ->assertSet('selectedTracks', fn ($tracks) => $tracks->isEmpty());

        Http::assertNothingSent();
    });

    test('can search for a playlist with a valid URL', function () {
        fakePlaylist([
            spotifyTrack(),
            spotifyTrack(['id' => 'eventually-id', 'name' => 'Eventually']),
        ]);

        Livewire::actingAs(User::factory()->createOne())
            ->test(PlaylistSetup::class)
            ->set('searchTerm', 'https://open.spotify.com/playlist/psych-rock-id?si=shared-link')
            ->call('search')
            ->assertSet('selectedPlaylist.id', 'psych-rock-id')
            ->assertSet('selectedTracks', fn ($tracks) => $tracks->pluck('id')->all() === ['less-i-know-id', 'eventually-id']);
    });

    test('cannot search for a show with an invalid URL', function () {
        Http::fake();

        Livewire::actingAs(User::factory()->createOne())
            ->test(ShowSetup::class)
            ->set('searchTerm', 'abcdxyz')
            ->call('search')
            ->assertSet('selectedShow', [])
            ->assertSet('selectedTracks', fn ($tracks) => $tracks->isEmpty());

        Http::assertNothingSent();
    });
});

describe('loading from spotify', function () {
    test('loads every song across an artist\'s albums, keeping one of each title', function () {
        fakeArtistAlbums([
            spotifyAlbumWithTracks('currents-id', 'album', [['let-it-happen-id', 'Let It Happen'], ['eventually-id', 'Eventually']]),
            spotifyAlbumWithTracks('single-id', 'single', [['let-it-happen-single-id', 'Let It Happen']]),
        ]);

        $component = Livewire::actingAs(User::factory()->createOne())
            ->test(ArtistSetup::class)
            ->set('searchedArtists', collect([['id' => 'tame-impala-id', 'name' => 'Tame Impala', 'cover' => 'https://example.test/tame-impala.png']]))
            ->call('loadArtistSongs', 'tame-impala-id')
            ->assertSet('selectedArtist.id', 'tame-impala-id');

        expect($component->get('selectedTracks')->pluck('id')->all())->toBe(['let-it-happen-id', 'eventually-id']);
    });

    test('loads a show and its episodes', function () {
        fakeShow([['episode-one-id', 'Episode One'], ['episode-two-id', 'Episode Two']]);

        $component = Livewire::actingAs(User::factory()->createOne())
            ->test(ShowSetup::class)
            ->set('searchTerm', 'https://open.spotify.com/show/song-exploder-id?si=shared-link')
            ->call('search')
            ->assertSet('selectedShow.id', 'song-exploder-id');

        expect($component->get('selectedTracks')->pluck('id')->all())->toBe(['episode-one-id', 'episode-two-id']);
    });

    test('turns down a playlist longer than a ranking can hold', function () {
        fakePlaylist([spotifyTrack()], total: Ranking::MAX_SONGS + 1);

        $component = Livewire::actingAs(User::factory()->createOne())
            ->test(PlaylistSetup::class)
            ->set('searchTerm', 'https://open.spotify.com/playlist/psych-rock-id')
            ->call('search')
            ->assertSet('selectedPlaylist', [])
            ->assertSet('selectedTracks', fn ($tracks) => $tracks->isEmpty());

        expect(alertsFrom($component)->pluck('icon')->all())->toBe(['error']);
    });

    test('loads the rest of a playlist and names the tracks Spotify has removed', function () {
        fakePlaylist([
            spotifyTrack(),
            spotifyTrack(['id' => 'gone-id', 'name' => 'Gone Missing', 'artists' => [['id' => null, 'name' => 'Ghost']]]),
        ]);

        $component = Livewire::actingAs(User::factory()->createOne())
            ->test(PlaylistSetup::class)
            ->set('searchTerm', 'https://open.spotify.com/playlist/psych-rock-id')
            ->call('search');

        $alert = alertsFrom($component)->sole();

        expect($component->get('selectedTracks')->pluck('id')->all())->toBe(['less-i-know-id'])
            ->and($alert['icon'])->toBe('error')
            ->and($alert['submessage'])->toContain('Gone Missing');
    });
});

describe('ranking type selection', function () {
    test('the setup shell renders the artist setup by default', function () {
        Livewire::actingAs(User::factory()->createOne())
            ->test(SongRankSetup::class)
            ->assertSet('type', RankingType::ARTIST)
            ->assertSeeLivewire(ArtistSetup::class);
    });

    test('switching ranking type swaps the rendered setup component', function () {
        Livewire::actingAs(User::factory()->createOne())
            ->test(SongRankSetup::class)
            ->call('switchType', RankingType::PLAYLIST->value)
            ->assertSet('type', RankingType::PLAYLIST)
            ->assertSeeLivewire(PlaylistSetup::class)
            ->call('switchType', RankingType::SHOW->value)
            ->assertSet('type', RankingType::SHOW)
            ->assertSeeLivewire(ShowSetup::class);
    });

    test('the type selector locks once a source has been selected', function () {
        Livewire::actingAs(User::factory()->createOne())
            ->test(ArtistSetup::class)
            ->assertSeeHtml('switch-ranking-type')
            ->set('selectedArtist', [
                'id' => 'test-artist-id',
                'name' => 'Local Natives',
                'cover' => 'https://api.dicebear.com/7.x/initials/svg?seed=testing',
            ])
            ->assertDontSeeHtml('switch-ranking-type');
    });

    test('resetting the setup clears the selection and unlocks the type selector', function () {
        Livewire::actingAs(User::factory()->createOne())
            ->test(ArtistSetup::class)
            ->set('searchTerm', 'Local Natives')
            ->set('selectedArtist', [
                'id' => 'test-artist-id',
                'name' => 'Local Natives',
                'cover' => 'https://api.dicebear.com/7.x/initials/svg?seed=testing',
            ])
            ->call('resetSetup')
            ->assertSet('selectedArtist', null)
            ->assertSet('searchTerm', '')
            ->assertSeeHtml('switch-ranking-type');
    });
});

/**
 * An artist whose albums hold the given tracks, answered the way the artist's
 * song list is read: its albums a page at a time, then their tracks in batches.
 *
 * @param  list<array<string, mixed>>  $albums
 */
function fakeArtistAlbums(array $albums): void
{
    Http::fake([
        'https://accounts.spotify.com/*' => Http::response(['access_token' => 'fresh-token']),
        'https://api.spotify.com/v1/artists/*/albums*' => Http::response([
            'total' => count($albums),
            'items' => collect($albums)->map(fn (array $album) => ['id' => $album['id']])->all(),
        ]),
        'https://api.spotify.com/v1/albums*' => Http::response(['albums' => $albums]),
    ]);
}

/**
 * @param  list<array{0: string, 1: string}>  $tracks  id and name
 * @return array<string, mixed>
 */
function spotifyAlbumWithTracks(string $id, string $type, array $tracks): array
{
    return [
        'id' => $id,
        'name' => ucfirst(str_replace('-id', '', $id)),
        'album_type' => $type,
        'images' => [['url' => "https://example.test/{$id}.png"]],
        'tracks' => ['items' => collect($tracks)->map(fn (array $track) => ['id' => $track[0], 'name' => $track[1]])->all()],
    ];
}

/**
 * A podcast holding the given episodes, answered the way the show search reads
 * it: the show itself, then its episodes a page at a time.
 *
 * @param  list<array{0: string, 1: string}>  $episodes  id and name
 */
function fakeShow(array $episodes): void
{
    Http::fake([
        'https://accounts.spotify.com/*' => Http::response(['access_token' => 'fresh-token']),
        'https://api.spotify.com/v1/shows/song-exploder-id/episodes*' => Http::response([
            'items' => collect($episodes)->map(fn (array $episode) => [
                'id' => $episode[0],
                'name' => $episode[1],
                'images' => [['url' => "https://example.test/{$episode[0]}.png"]],
            ])->all(),
        ]),
        'https://api.spotify.com/v1/shows/song-exploder-id' => Http::response([
            'id' => 'song-exploder-id',
            'name' => 'Song Exploder',
            'description' => 'Musicians take apart their songs',
            'publisher' => 'Hrishikesh Hirway',
            'images' => [['url' => 'https://example.test/song-exploder.png']],
            'total_episodes' => count($episodes),
        ]),
    ]);
}
