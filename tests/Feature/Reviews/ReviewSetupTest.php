<?php

use App\Enums\ReviewType;
use App\Livewire\Reviews\ReviewSetup;
use App\Livewire\Reviews\Setup\AlbumSetup;
use App\Livewire\Reviews\Setup\ArtistSetup;
use App\Livewire\Reviews\Setup\TrackSetup;
use App\Models\Album;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

describe('reaching the page', function () {
    it('is not there at all while the feature is off', function () {
        actingAs(User::factory()->createOne());

        get(route('reviews.create'))->assertNotFound();
    });

    it('opens for somebody the feature is on for', function () {
        actingAs(kyle());

        get(route('reviews.create'))->assertOk();
    });

    it('sends a guest away', function () {
        get(route('reviews.create'))->assertRedirect();
    });
});

describe('choosing a type', function () {
    it('starts on albums', function () {
        Livewire::actingAs(kyle())
            ->test(ReviewSetup::class)
            ->assertSet('type', ReviewType::ALBUM)
            ->call('setupComponent')
            ->assertReturned(AlbumSetup::class);
    });

    it('opens on the type a link asks for', function () {
        Livewire::actingAs(kyle())
            ->withQueryParams(['type' => 'track'])
            ->test(ReviewSetup::class)
            ->assertSet('type', ReviewType::TRACK);
    });

    it('swaps in the artist setup', function () {
        Livewire::actingAs(kyle())
            ->test(ReviewSetup::class)
            ->dispatch('switch-review-type', type: 'artist')
            ->assertSet('type', ReviewType::ARTIST)
            ->call('setupComponent')
            ->assertReturned(ArtistSetup::class);
    });

    it('swaps in the track setup', function () {
        Livewire::actingAs(kyle())
            ->test(ReviewSetup::class)
            ->dispatch('switch-review-type', type: 'track')
            ->call('setupComponent')
            ->assertReturned(TrackSetup::class);
    });
});

describe('searching', function () {
    it('finds artists', function () {
        fakeSearch('artists', [spotifyArtist()]);

        Livewire::actingAs(kyle())
            ->test(ArtistSetup::class)
            ->set('searchTerm', 'tame impala')
            ->call('search')
            ->assertSee('Tame Impala')
            ->assertSee('https://open.spotify.com/artist/tame-impala-id');
    });

    it('finds albums', function () {
        fakeSearch('albums', [spotifyAlbum()]);

        Livewire::actingAs(kyle())
            ->test(AlbumSetup::class)
            ->set('searchTerm', 'currents')
            ->call('search')
            ->assertSee('Currents')
            ->assertSee('https://open.spotify.com/album/currents-id');
    });

    it('finds tracks', function () {
        fakeSearch('tracks', [spotifyTrack()]);

        Livewire::actingAs(kyle())
            ->test(TrackSetup::class)
            ->set('searchTerm', 'less i know')
            ->call('search')
            ->assertSee('The Less I Know The Better')
            ->assertSee('https://open.spotify.com/track/less-i-know-id');
    });

    it('asks for a search term before calling Spotify', function () {
        Http::fake();

        $component = Livewire::actingAs(kyle())
            ->test(AlbumSetup::class)
            ->call('search');

        expect(alertsFrom($component)->pluck('title'))->toContain('Please enter a valid search term.');

        Http::assertNothingSent();
    });

    it('says so when nothing comes back', function () {
        fakeSearch('albums', []);

        $component = Livewire::actingAs(kyle())
            ->test(AlbumSetup::class)
            ->set('searchTerm', 'zzzz')
            ->call('search');

        expect(alertsFrom($component)->pluck('title'))->toContain('Nothing found.');
    });
});

describe('choosing a subject', function () {
    it('takes the chosen result and clears the search', function () {
        albumSetupWithResults()
            ->call('chooseSubject', 'currents-id')
            ->assertSet('subject.id', 'currents-id')
            ->assertSet('searchResults', null)
            ->assertSet('searchTerm', '');
    });

    it('ignores an id that was not in the results', function () {
        albumSetupWithResults()
            ->call('chooseSubject', 'made-up-id')
            ->assertSet('subject', null);
    });

    it('lets the subject be put back', function () {
        albumSetupWithResults()
            ->call('chooseSubject', 'currents-id')
            ->call('clearSubject')
            ->assertSet('subject', null);
    });

    it('refuses something the user has already reviewed', function () {
        $user = kyle();
        $album = Album::factory()->createOne(['album_id' => 'currents-id']);
        Review::factory()->for($user)->createOne(['subject_id' => $album->getKey()]);

        $component = albumSetupWithResults($user)->call('chooseSubject', 'currents-id');

        $component->assertSet('subject', null);

        expect(alertsFrom($component)->pluck('title'))->toContain('You have already reviewed Currents.');
    });

    it('allows the subject again once that review is deleted', function () {
        $user = kyle();
        $album = Album::factory()->createOne(['album_id' => 'currents-id']);
        Review::factory()->for($user)->createOne(['subject_id' => $album->getKey()])->delete();

        albumSetupWithResults($user)
            ->call('chooseSubject', 'currents-id')
            ->assertSet('subject.id', 'currents-id');
    });

    it('does not count somebody else reviewing the same thing', function () {
        $album = Album::factory()->createOne(['album_id' => 'currents-id']);
        Review::factory()->createOne(['subject_id' => $album->getKey()]);

        albumSetupWithResults()
            ->call('chooseSubject', 'currents-id')
            ->assertSet('subject.id', 'currents-id');
    });

    it('starts over from scratch', function () {
        albumSetupWithResults()
            ->call('chooseSubject', 'currents-id')
            ->set('form.name', 'My Take')
            ->call('resetSetup')
            ->assertSet('subject', null)
            ->assertSet('searchResults', null)
            ->assertSet('form.name', '');
    });
});

function albumSetupWithResults(?User $user = null): Testable
{
    return Livewire::actingAs($user ?? kyle())
        ->test(AlbumSetup::class)
        ->set('searchResults', [albumEntry()]);
}
