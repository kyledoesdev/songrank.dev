<?php

use App\Livewire\SongRank\Setup\ArtistSetup;
use App\Models\Artist;
use App\Models\Ranking;
use App\Models\Song;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

describe('the featured tracks toggle', function () {
    test('is off by default so featured tracks stay out of the ranking', function () {
        artistSetup()
            ->set('includeFeaturedTracks', false)
            ->call('beginRanking')
            ->assertHasNoErrors();

        $ranking = Ranking::where('name', 'Test List')->firstOrFail();

        expect($ranking->songs()->count())->toBe(2);
        expect($ranking->songs()->pluck('title')->sort()->values()->all())->toBe(['Own One', 'Own Two']);
        expect($ranking->songs()->where('featured_artist', true)->count())->toBe(0);
    });

    test('pulls featured tracks into the ranking once switched on', function () {
        artistSetup()
            ->call('beginRanking')
            ->assertHasNoErrors();

        $ranking = Ranking::where('name', 'Test List')->firstOrFail();

        expect($ranking->songs()->count())->toBe(3);

        $featured = $ranking->songs()->where('featured_artist', true)->get();

        expect($featured)->toHaveCount(1);
        expect($featured->first()->title)->toBe('Guest Verse');
    });

    test('reports whether the artist has any featured tracks at all', function () {
        artistSetup()
            ->call('hasFeaturedTracks')
            ->assertReturned(true)
            ->set('featuredTracks', collect())
            ->call('hasFeaturedTracks')
            ->assertReturned(false);
    });

    test('removing a featured track keeps it out even when the toggle is on', function () {
        $component = artistSetup();

        $featuredUuid = $component->get('featuredTracks')->first()['uuid'];

        $component
            ->call('removeTrack', $featuredUuid)
            ->call('beginRanking')
            ->assertHasNoErrors();

        $ranking = Ranking::where('name', 'Test List')->firstOrFail();

        expect($ranking->songs()->count())->toBe(2);
        expect($ranking->songs()->where('featured_artist', true)->count())->toBe(0);
    });

    test('the quick filters reach into the featured list too', function () {
        $component = artistSetup()
            ->call('removeTracksMatching', 'guest');

        expect($component->get('removedTrackUuids'))->toHaveCount(1);

        $component->call('beginRanking')->assertHasNoErrors();

        expect(Ranking::where('name', 'Test List')->firstOrFail()->songs()->count())->toBe(2);
    });
});

describe('featured track storage', function () {
    test('stores a featured track under its primary artist', function () {
        expect(Artist::where('artist_id', 'other-artist-id')->exists())->toBeFalse();

        artistSetup()
            ->call('beginRanking')
            ->assertHasNoErrors();

        $primaryArtist = Artist::where('artist_id', 'other-artist-id')->firstOrFail();
        $guestVerse = Song::where('spotify_song_id', 'guest-verse-id')->firstOrFail();

        expect($primaryArtist->artist_name)->toBe('Other Artist');
        expect($primaryArtist->artist_img)->toBeNull();
        expect($guestVerse->featured_artist)->toBeTrue();
        expect($guestVerse->artist_id)->toBe($primaryArtist->getKey());
    });

    test('keeps owned tracks pointed at the ranked artist', function () {
        artistSetup()
            ->call('beginRanking')
            ->assertHasNoErrors();

        $rankedArtist = Artist::where('artist_id', 'ranked-artist-id')->firstOrFail();
        $ownTrack = Song::where('spotify_song_id', 'own-one-id')->firstOrFail();

        expect($ownTrack->featured_artist)->toBeFalse();
        expect($ownTrack->artist_id)->toBe($rankedArtist->getKey());
    });

    test('leaves the ranking pointed at the ranked artist, not the primary artist', function () {
        artistSetup()
            ->call('beginRanking')
            ->assertHasNoErrors();

        expect(Ranking::where('name', 'Test List')->firstOrFail()->source->artist_id)->toBe('ranked-artist-id');
    });
});

describe('lazy loading featured tracks', function () {
    test('featured tracks are only fetched once the toggle is switched on', function () {
        fakeAppearsOnApi();

        $component = Livewire::actingAs(User::factory()->createOne())
            ->test(ArtistSetup::class)
            ->set('selectedArtist', [
                'id' => 'ranked-artist-id',
                'name' => 'Ranked Artist',
                'cover' => 'https://example.test/artist.png',
            ])
            ->set('selectedTracks', collect([
                ownedTrack('own-one-id', 'Own One'),
                ownedTrack('own-two-id', 'Own Two'),
            ]))
            ->set('appearsOnCount', 1);

        expect($component->get('featuredTracks')->isEmpty())->toBeTrue();

        $component->set('includeFeaturedTracks', true);

        $featured = $component->get('featuredTracks');

        expect($featured)->toHaveCount(1);
        expect($featured->first()['name'])->toBe('Guest Verse');
        expect($featured->first()['featured_artist'])->toBeTrue();
        expect($featured->first()['primary_artist'])->toBe(['id' => 'other-artist-id', 'name' => 'Other Artist']);
    });

    test('does not refetch once the featured tracks are loaded', function () {
        Http::fake();

        artistSetup()->set('includeFeaturedTracks', true);

        Http::assertNothingSent();
    });

    test('skips compilation releases when walking the appears-on albums', function () {
        fakeAppearsOnApi();

        Livewire::actingAs(User::factory()->createOne())
            ->test(ArtistSetup::class)
            ->set('selectedArtist', [
                'id' => 'ranked-artist-id',
                'name' => 'Ranked Artist',
                'cover' => 'https://example.test/artist.png',
            ])
            ->set('selectedTracks', collect([
                ownedTrack('own-one-id', 'Own One'),
                ownedTrack('own-two-id', 'Own Two'),
            ]))
            ->set('appearsOnCount', 2)
            ->set('includeFeaturedTracks', true);

        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'hits-comp-id'));
    });

    test('walks a partial second album page when the artist appears on more than fifty releases', function () {
        Http::fake([
            'https://accounts.spotify.com/*' => Http::response(['access_token' => 'fresh-token']),
            'https://api.spotify.com/v1/artists/*' => fn (Request $request) => Http::response([
                'total' => 59,
                'items' => str_contains($request->url(), 'offset=50')
                    ? [['id' => 'second-page-album-id', 'album_type' => 'single']]
                    : [['id' => 'their-album-id', 'album_type' => 'single']],
            ]),
            'https://api.spotify.com/v1/albums*' => Http::response([
                'albums' => [
                    appearsOnAlbum('their-album-id', 'guest-verse-id', 'Guest Verse'),
                    appearsOnAlbum('second-page-album-id', 'second-verse-id', 'Second Verse'),
                ],
            ]),
        ]);

        $component = Livewire::actingAs(User::factory()->createOne())
            ->test(ArtistSetup::class)
            ->set('selectedArtist', [
                'id' => 'ranked-artist-id',
                'name' => 'Ranked Artist',
                'cover' => 'https://example.test/artist.png',
            ])
            ->set('selectedTracks', collect([
                ownedTrack('own-one-id', 'Own One'),
            ]))
            ->set('appearsOnCount', 59)
            ->set('includeFeaturedTracks', true);

        expect($component->get('featuredTracks')->pluck('name')->sort()->values()->all())
            ->toBe(['Guest Verse', 'Second Verse']);

        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'offset=50'));
    });

    test('loads featured tracks past the ranking cap and flags the overage', function () {
        fakeAppearsOnApi();

        $component = Livewire::actingAs(User::factory()->createOne())
            ->test(ArtistSetup::class)
            ->set('selectedArtist', [
                'id' => 'ranked-artist-id',
                'name' => 'Ranked Artist',
                'cover' => 'https://example.test/artist.png',
            ])
            ->set('selectedTracks', collect(range(1, Ranking::MAX_SONGS))->map(
                fn (int $i) => ownedTrack("own-{$i}-id", "Own {$i}")
            ))
            ->set('appearsOnCount', 2)
            ->set('includeFeaturedTracks', true);

        expect($component->get('featuredTracks'))->toHaveCount(1);

        $component
            ->assertSee('501 / 500')
            ->assertSee('Remove 1 to begin');
    });

    test('the featured card only renders when the artist has known appearances', function () {
        $component = artistSetup();

        $component->assertDontSee('Featured On');

        $component->set('appearsOnCount', 4)->assertSee('Featured On');
    });
});

describe('filter modals with featured tracks loaded', function () {
    test('render once, so a filters button opens a single modal', function () {
        $component = artistSetup()->set('appearsOnCount', 1);

        $component->call('removeTrack', $component->get('selectedTracks')->first()['uuid']);

        $html = $component->html();

        expect(substr_count($html, '@open-album-modal.window'))->toBe(1);
        expect(substr_count($html, '@open-removed-tracks-modal.window'))->toBe(1);
        expect(substr_count($html, "\$dispatch('open-album-modal')"))->toBe(2);
    });
});

// -- Helpers --

function fakeAppearsOnApi(): void
{
    Http::fake([
        'https://accounts.spotify.com/*' => Http::response(['access_token' => 'fresh-token']),
        'https://api.spotify.com/v1/artists/*' => Http::response([
            'total' => 2,
            'items' => [
                ['id' => 'their-album-id', 'album_type' => 'single'],
                ['id' => 'hits-comp-id', 'album_type' => 'compilation'],
            ],
        ]),
        'https://api.spotify.com/v1/albums*' => Http::response([
            'albums' => [
                [
                    'id' => 'their-album-id',
                    'name' => 'Their Album',
                    'album_type' => 'single',
                    'artists' => [['id' => 'other-artist-id', 'name' => 'Other Artist']],
                    'images' => [['url' => 'https://example.test/their-album.png']],
                    'tracks' => [
                        'total' => 1,
                        'items' => [
                            [
                                'id' => 'guest-verse-id',
                                'name' => 'Guest Verse',
                                'artists' => [
                                    ['id' => 'other-artist-id', 'name' => 'Other Artist'],
                                    ['id' => 'ranked-artist-id', 'name' => 'Ranked Artist'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]),
    ]);
}

function artistSetup(): Testable
{
    return Livewire::actingAs(User::factory()->createOne())
        ->test(ArtistSetup::class)
        ->set('selectedArtist', [
            'id' => 'ranked-artist-id',
            'name' => 'Ranked Artist',
            'cover' => 'https://example.test/artist.png',
        ])
        ->set('selectedTracks', collect([
            ownedTrack('own-one-id', 'Own One'),
            ownedTrack('own-two-id', 'Own Two'),
        ]))
        ->set('featuredTracks', collect([
            [
                'id' => 'guest-verse-id',
                'name' => 'Guest Verse',
                'uuid' => str()->uuid()->toString(),
                'cover' => 'https://example.test/their-album.png',
                'album_name' => 'Their Album',
                'album_id' => 'their-album-id',
                'album_type' => 'single',
                'featured_artist' => true,
                'primary_artist' => ['id' => 'other-artist-id', 'name' => 'Other Artist'],
            ],
        ]))
        ->set('includeFeaturedTracks', true)
        ->set('form.name', 'Test List')
        ->set('form.is_public', true);
}

function appearsOnAlbum(string $albumId, string $trackId, string $trackName): array
{
    return [
        'id' => $albumId,
        'name' => 'Their Album',
        'album_type' => 'single',
        'artists' => [['id' => 'other-artist-id', 'name' => 'Other Artist']],
        'images' => [['url' => "https://example.test/{$albumId}.png"]],
        'tracks' => [
            'total' => 1,
            'items' => [
                [
                    'id' => $trackId,
                    'name' => $trackName,
                    'artists' => [
                        ['id' => 'other-artist-id', 'name' => 'Other Artist'],
                        ['id' => 'ranked-artist-id', 'name' => 'Ranked Artist'],
                    ],
                ],
            ],
        ],
    ];
}

function ownedTrack(string $id, string $name, string $albumId = 'own-album-id', string $albumName = 'Own Album'): array
{
    return [
        'id' => $id,
        'name' => $name,
        'uuid' => str()->uuid()->toString(),
        'cover' => 'https://example.test/own-album.png',
        'album_name' => $albumName,
        'album_id' => $albumId,
        'album_type' => 'album',
        'featured_artist' => false,
    ];
}
