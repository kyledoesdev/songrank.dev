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

    it('opens for anyone signed in', function () {
        actingAs(User::factory()->createOne());

        get(route('reviews.create'))->assertOk();
    });

    it('sends a guest away', function () {
        get(route('reviews.create'))->assertRedirect();
    });
});

describe('choosing a type', function () {
    it('starts on albums', function () {
        Livewire::actingAs(User::factory()->createOne())
            ->test(ReviewSetup::class)
            ->assertSet('type', ReviewType::ALBUM)
            ->call('setupComponent')
            ->assertReturned(AlbumSetup::class);
    });

    it('opens on the type a link asks for', function () {
        Livewire::actingAs(User::factory()->createOne())
            ->withQueryParams(['type' => 'track'])
            ->test(ReviewSetup::class)
            ->assertSet('type', ReviewType::TRACK);
    });

    it('swaps in the artist setup', function () {
        Livewire::actingAs(User::factory()->createOne())
            ->test(ReviewSetup::class)
            ->dispatch('switch-review-type', type: 'artist')
            ->assertSet('type', ReviewType::ARTIST)
            ->call('setupComponent')
            ->assertReturned(ArtistSetup::class);
    });

    it('swaps in the track setup', function () {
        Livewire::actingAs(User::factory()->createOne())
            ->test(ReviewSetup::class)
            ->dispatch('switch-review-type', type: 'track')
            ->call('setupComponent')
            ->assertReturned(TrackSetup::class);
    });
});

describe('searching', function () {
    it('finds artists', function () {
        fakeSearch('artists', [spotifyArtist()]);

        Livewire::actingAs(User::factory()->createOne())
            ->test(ArtistSetup::class)
            ->set('searchTerm', 'tame impala')
            ->call('search')
            ->assertSet('searchResults', fn (array $results) => collect($results)->pluck('id')->all() === ['tame-impala-id'])
            ->assertSee('https://open.spotify.com/artist/tame-impala-id', escape: false);
    });

    it('finds albums', function () {
        fakeSearch('albums', [spotifyAlbum()]);

        Livewire::actingAs(User::factory()->createOne())
            ->test(AlbumSetup::class)
            ->set('searchTerm', 'currents')
            ->call('search')
            ->assertSet('searchResults', fn (array $results) => collect($results)->pluck('id')->all() === ['currents-id'])
            ->assertSee('https://open.spotify.com/album/currents-id', escape: false);
    });

    it('finds tracks', function () {
        fakeSearch('tracks', [spotifyTrack()]);

        Livewire::actingAs(User::factory()->createOne())
            ->test(TrackSetup::class)
            ->set('searchTerm', 'less i know')
            ->call('search')
            ->assertSet('searchResults', fn (array $results) => collect($results)->pluck('id')->all() === ['less-i-know-id'])
            ->assertSee('https://open.spotify.com/track/less-i-know-id', escape: false);
    });

    it('asks for a search term before calling Spotify', function () {
        Http::fake();

        $component = Livewire::actingAs(User::factory()->createOne())
            ->test(AlbumSetup::class)
            ->call('search');

        expect(alertsFrom($component)->pluck('icon')->all())->toBe(['error']);

        Http::assertNothingSent();
    });

    it('says so when nothing comes back', function () {
        fakeSearch('albums', []);

        $component = Livewire::actingAs(User::factory()->createOne())
            ->test(AlbumSetup::class)
            ->set('searchTerm', 'zzzz')
            ->call('search')
            ->assertSet('searchResults', fn (?array $results) => empty($results));

        expect(alertsFrom($component)->pluck('icon')->all())->toBe(['error']);
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
        $user = User::factory()->createOne();
        $album = Album::factory()->createOne(['album_id' => 'currents-id']);
        Review::factory()->for($user)->createOne(['subject_id' => $album->getKey()]);

        $component = albumSetupWithResults($user)->call('chooseSubject', 'currents-id');

        $component->assertSet('subject', null);

        expect(alertsFrom($component)->pluck('icon')->all())->toBe(['error']);
    });

    it('allows the subject again once that review is deleted', function () {
        $user = User::factory()->createOne();
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
    return Livewire::actingAs($user ?? User::factory()->createOne())
        ->test(AlbumSetup::class)
        ->set('searchResults', [albumEntry()]);
}
