<?php

use App\Livewire\Tierlist\Setup\TrackSetup;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

describe('album grouping', function () {
    it('groups entries by album after a discography import', function () {
        fakeArtistDiscography(threeAlbumDiscography());

        $component = trackSetupWithDiscography();
        $albums = $component->instance()->albums();

        expect($albums)->toHaveCount(3);

        $currents = $albums->firstWhere('id', 'currents-id');

        expect($currents['name'])->toBe('Currents')
            ->and($currents['type'])->toBe('album')
            ->and($currents['track_count'])->toBe(3)
            ->and($currents['selected_count'])->toBe(3)
            ->and($currents['all_selected'])->toBeTrue()
            ->and($currents['none_selected'])->toBeFalse();
    });

    it('returns nothing when the bank has no album data', function () {
        fakeSearch('tracks', [spotifyTrack()]);

        $component = Livewire::actingAs(User::factory()->createOne())
            ->test(TrackSetup::class)
            ->set('searchTerm', 'test')
            ->call('search')
            ->call('addEntry', 'less-i-know-id');

        expect($component->instance()->albums())->toBeEmpty();
    });
});

describe('toggling albums', function () {
    it('removes all tracks from an album', function () {
        fakeArtistDiscography(threeAlbumDiscography());

        $component = trackSetupWithDiscography()
            ->call('toggleAlbum', 'currents-id');

        expect($component->get('bank'))->toHaveCount(3)
            ->and($component->get('removedEntries'))->toHaveCount(3)
            ->and($component->get('removedEntries')->pluck('id')->all())
            ->toBe(['let-it-happen', 'nangs', 'the-moment']);

        $currents = $component->instance()->albums()->firstWhere('id', 'currents-id');

        expect($currents['none_selected'])->toBeTrue()
            ->and($currents['selected_count'])->toBe(0);
    });

    it('restores all tracks from an album', function () {
        fakeArtistDiscography(threeAlbumDiscography());

        $component = trackSetupWithDiscography()
            ->call('toggleAlbum', 'currents-id')
            ->call('toggleAlbum', 'currents-id');

        expect($component->get('bank'))->toHaveCount(6)
            ->and($component->get('removedEntries'))->toBeEmpty();
    });

    it('restores only what fits when the bank is near its limit', function () {
        fakeArtistDiscography([
            spotifyDiscographyAlbum('album-a', 'Album A', 'album', [
                ['id' => 'a1', 'name' => 'A One'],
                ['id' => 'a2', 'name' => 'A Two'],
                ['id' => 'a3', 'name' => 'A Three'],
            ]),
            spotifyDiscographyAlbum('album-b', 'Album B', 'album', [
                ['id' => 'b1', 'name' => 'B One'],
                ['id' => 'b2', 'name' => 'B Two'],
            ]),
        ]);

        $component = trackSetupWithDiscography();

        expect($component->get('bank'))->toHaveCount(5);

        $component->call('toggleAlbum', 'album-a');

        expect($component->get('bank'))->toHaveCount(2)
            ->and($component->get('removedEntries'))->toHaveCount(3);

        $component->call('addEntry', 'b1');

        config()->set('billing.tierlist_item_limits.free', 3);

        $component->call('toggleAlbum', 'album-a');

        expect($component->get('bank'))->toHaveCount(3)
            ->and($component->get('removedEntries'))->toHaveCount(2);
    });
});

describe('quick filters', function () {
    it('removes entries matching a text pattern', function () {
        fakeArtistDiscography([
            spotifyDiscographyAlbum('album-a', 'Album A', 'album', [
                ['id' => 'original', 'name' => 'Something'],
                ['id' => 'remix-track', 'name' => 'Something (Remix)'],
                ['id' => 'live-track', 'name' => 'Something (Live From Mars)'],
            ]),
        ]);

        $component = trackSetupWithDiscography()
            ->call('removeEntriesMatching', 'remix');

        expect($component->get('bank'))->toHaveCount(2)
            ->and($component->get('removedEntries'))->toHaveCount(1)
            ->and($component->get('removedEntries')->first()['id'])->toBe('remix-track');
    });

    it('does nothing when no entries match', function () {
        fakeArtistDiscography(threeAlbumDiscography());

        $component = trackSetupWithDiscography()
            ->call('removeEntriesMatching', 'zzzzz');

        expect($component->get('bank'))->toHaveCount(6)
            ->and($component->get('removedEntries'))->toBeEmpty();
    });
});

function trackSetupWithDiscography()
{
    return Livewire::actingAs(User::factory()->createOne())
        ->test(TrackSetup::class)
        ->call('switchMode', 'artist')
        ->set('searchTerm', 'tame impala')
        ->call('search')
        ->call('loadDiscography', 'tame-impala-id');
}

function threeAlbumDiscography(): array
{
    return [
        spotifyDiscographyAlbum('currents-id', 'Currents', 'album', [
            ['id' => 'let-it-happen', 'name' => 'Let It Happen'],
            ['id' => 'nangs', 'name' => 'Nangs'],
            ['id' => 'the-moment', 'name' => 'The Moment'],
        ]),
        spotifyDiscographyAlbum('lonerism-id', 'Lonerism', 'album', [
            ['id' => 'be-above-it', 'name' => 'Be Above It'],
            ['id' => 'endors-toi', 'name' => 'Endors Toi'],
        ]),
        spotifyDiscographyAlbum('patience-id', 'Patience', 'single', [
            ['id' => 'patience', 'name' => 'Patience'],
        ]),
    ];
}

function spotifyDiscographyAlbum(string $id, string $name, string $type, array $tracks): array
{
    return [
        'id' => $id,
        'name' => $name,
        'album_type' => $type,
        'release_date' => '2015-07-17',
        'total_tracks' => count($tracks),
        'images' => [['url' => "https://example.test/{$id}.png"]],
        'artists' => [['id' => 'tame-impala-id', 'name' => 'Tame Impala']],
        'tracks' => [
            'items' => collect($tracks)->map(fn (array $track) => [
                'id' => $track['id'],
                'name' => $track['name'],
                'artists' => [['id' => 'tame-impala-id', 'name' => 'Tame Impala']],
            ])->all(),
        ],
    ];
}

function fakeArtistDiscography(array $albums): void
{
    Http::fake([
        'https://accounts.spotify.com/*' => Http::response(['access_token' => 'fresh-token']),
        'https://api.spotify.com/v1/search*' => Http::response([
            'artists' => ['items' => [spotifyArtist()]],
        ]),
        'https://api.spotify.com/v1/artists/*/albums*' => Http::response([
            'total' => count($albums),
            'items' => collect($albums)->map(fn (array $album) => [
                'id' => $album['id'],
                'name' => $album['name'],
                'album_type' => $album['album_type'],
                'release_date' => $album['release_date'],
                'total_tracks' => $album['total_tracks'],
                'images' => $album['images'],
                'artists' => $album['artists'],
            ])->all(),
        ]),
        'https://api.spotify.com/v1/albums*' => Http::response([
            'albums' => $albums,
        ]),
    ]);
}
