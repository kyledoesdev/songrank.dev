<?php

use App\Livewire\Tierlist\Setup\AlbumSetup;
use App\Livewire\Tierlist\Setup\TrackSetup;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Playlist;
use App\Models\Tierlist;
use App\Models\Track;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

describe('album setup', function () {
    it('searches albums by default', function () {
        fakeSearch('albums', [spotifyAlbum()]);

        $component = Livewire::actingAs(User::factory()->createOne())
            ->test(AlbumSetup::class)
            ->assertSet('mode', 'album')
            ->set('searchTerm', 'currents')
            ->call('search')
            ->call('addEntry', 'currents-id');

        expect($component->get('bank'))->toHaveCount(1);
    });

    it('imports a whole discography and remembers whose it was', function () {
        Http::fake([
            'https://accounts.spotify.com/*' => Http::response(['access_token' => 'fresh-token']),
            'https://api.spotify.com/v1/search*' => Http::response(['artists' => ['items' => [spotifyArtist()]]]),
            'https://api.spotify.com/v1/artists/*' => Http::response([
                'total' => 2,
                'items' => [
                    spotifyAlbum(),
                    spotifyAlbum(['id' => 'lonerism-id', 'name' => 'Lonerism']),
                ],
            ]),
        ]);

        Livewire::actingAs(User::factory()->createOne())
            ->test(AlbumSetup::class)
            ->call('switchMode', 'artist')
            ->set('searchTerm', 'radiohead')
            ->call('search')
            ->call('loadDiscography', 'tame-impala-id')
            ->call('addDiscography')
            ->set('form.name', '')
            ->call('startTierlist')
            ->assertRedirect();

        $tierlist = Tierlist::first();

        expect($tierlist->bank->items)->toHaveCount(2)
            ->and($tierlist->source)->toBeInstanceOf(Artist::class)
            ->and($tierlist->source->artist_name)->toBe('Tame Impala')
            ->and($tierlist->name)->toBe('Tame Impala Tier List')
            ->and(Album::count())->toBe(2);
    });
});

describe('track setup', function () {
    it('searches tracks by default', function () {
        fakeSearch('tracks', [spotifyTrack()]);

        $component = Livewire::actingAs(User::factory()->createOne())
            ->test(TrackSetup::class)
            ->assertSet('mode', 'track')
            ->set('searchTerm', 'the less i know')
            ->call('search')
            ->call('addEntry', 'less-i-know-id');

        expect($component->get('bank'))->toHaveCount(1);
    });

    it('rejects something that is not a playlist url', function () {
        $component = Livewire::actingAs(User::factory()->createOne())
            ->test(TrackSetup::class)
            ->call('switchMode', 'playlist')
            ->set('searchTerm', 'not a url')
            ->call('search');

        expect($component->get('bank'))->toBeEmpty()
            ->and($component->get('searchTerm'))->toBe('');
    });

    it('pours a playlist straight onto the board and remembers it', function () {
        fakePlaylist([
            spotifyTrack(['id' => 'one', 'name' => 'One']),
            spotifyTrack(['id' => 'two', 'name' => 'Two']),
        ], ['name' => 'Late Night']);

        Livewire::actingAs(User::factory()->createOne())
            ->test(TrackSetup::class)
            ->call('switchMode', 'playlist')
            ->set('searchTerm', 'https://open.spotify.com/playlist/psych-rock-id')
            ->call('search')
            ->set('form.name', '')
            ->call('startTierlist')
            ->assertRedirect();

        $tierlist = Tierlist::first();

        expect($tierlist->bank->items)->toHaveCount(2)
            ->and($tierlist->source)->toBeInstanceOf(Playlist::class)
            ->and($tierlist->name)->toBe('Late Night Tier List')
            ->and(Track::count())->toBe(2);
    });
});
