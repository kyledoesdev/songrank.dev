<?php

use App\Enums\TierlistType;
use App\Livewire\Tierlist\Setup\AlbumSetup;
use App\Livewire\Tierlist\Setup\ArtistSetup;
use App\Livewire\Tierlist\Setup\TrackSetup;
use App\Livewire\Tierlist\TierlistSetup;
use App\Models\Artist;
use App\Models\Tierlist;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

describe('following a type link', function () {
    it('opens the setup on the type that was clicked', function () {
        actingAs(kyle());

        get(route('tierlists.create', ['type' => 'album']))->assertOk();

        Livewire::actingAs(kyle())
            ->withQueryParams(['type' => 'album'])
            ->test(TierlistSetup::class)
            ->assertSet('type', TierlistType::ALBUM);
    });

    it('falls back to artists when the link says something silly', function () {
        Livewire::actingAs(kyle())
            ->withQueryParams(['type' => 'nonsense'])
            ->test(TierlistSetup::class)
            ->assertSet('type', TierlistType::ARTIST);
    });
});

describe('reaching the page', function () {
    it('is not there at all while the feature is off', function () {
        actingAs(User::factory()->createOne());

        get(route('tierlists.create'))->assertNotFound();
    });

    it('opens for somebody the feature is on for', function () {
        actingAs(kyle());

        get(route('tierlists.create'))->assertOk();
    });

    it('sends a guest to log in', function () {
        get(route('tierlists.create'))->assertRedirect();
    });
});

describe('choosing a type', function () {
    it('starts on artists', function () {
        Livewire::actingAs(kyle())
            ->test(TierlistSetup::class)
            ->assertSet('type', TierlistType::ARTIST)
            ->call('setupComponent')
            ->assertReturned(ArtistSetup::class);
    });

    it('swaps in the album setup', function () {
        Livewire::actingAs(kyle())
            ->test(TierlistSetup::class)
            ->dispatch('switch-tierlist-type', type: 'album')
            ->assertSet('type', TierlistType::ALBUM)
            ->call('setupComponent')
            ->assertReturned(AlbumSetup::class);
    });

    it('swaps in the track setup', function () {
        Livewire::actingAs(kyle())
            ->test(TierlistSetup::class)
            ->dispatch('switch-tierlist-type', type: 'track')
            ->call('setupComponent')
            ->assertReturned(TrackSetup::class);
    });
});

describe('filling the board', function () {
    it('puts a search result on the board', function () {
        fakeSearch('artists', [spotifyArtist()]);

        $component = artistTierlistSetup()
            ->set('searchTerm', 'radiohead')
            ->call('search')
            ->call('addEntry', 'tame-impala-id');

        expect($component->get('bank'))->toHaveCount(1)
            ->and($component->get('bank')->first()['name'])->toBe('Tame Impala');
    });

    it('will not put the same entry on twice', function () {
        fakeSearch('artists', [spotifyArtist()]);

        $component = artistTierlistSetup()
            ->set('searchTerm', 'radiohead')
            ->call('search')
            ->call('addEntry', 'tame-impala-id')
            ->call('addEntry', 'tame-impala-id');

        expect($component->get('bank'))->toHaveCount(1);
    });

    it('ignores an entry that was never in the results', function () {
        $component = artistTierlistSetup()->call('addEntry', 'nonsense-id');

        expect($component->get('bank'))->toBeEmpty();
    });

    it('takes an entry back off the board', function () {
        fakeSearch('artists', [spotifyArtist()]);

        $component = artistTierlistSetup()
            ->set('searchTerm', 'radiohead')
            ->call('search')
            ->call('addEntry', 'tame-impala-id')
            ->call('removeEntry', 'tame-impala-id');

        expect($component->get('bank'))->toBeEmpty();
    });

    it('says nothing was found when nothing was', function () {
        fakeSearch('artists', []);

        $component = artistTierlistSetup()
            ->set('searchTerm', 'zzzzz')
            ->call('search');

        expect($component->get('searchResults'))->toBeEmpty();
    });
});

describe('starting the list', function () {
    it('refuses to start with fewer than two entries', function () {
        fakeSearch('artists', [spotifyArtist()]);

        artistTierlistSetup()
            ->set('searchTerm', 'radiohead')
            ->call('search')
            ->call('addEntry', 'tame-impala-id')
            ->call('startTierlist')
            ->assertNoRedirect();

        expect(Tierlist::count())->toBe(0);
    });

    it('asks before it builds anything', function () {
        fakeSearch('artists', [
            spotifyArtist(),
            spotifyArtist(['id' => 'foster-the-people-id', 'name' => 'Foster the People']),
        ]);

        artistTierlistSetup()
            ->set('searchTerm', 'psych rock')
            ->call('search')
            ->call('addEntry', 'tame-impala-id')
            ->call('addEntry', 'foster-the-people-id')
            ->call('confirmStartTierlist')
            ->assertNoRedirect();

        expect(Tierlist::count())->toBe(0);
    });

    it('does not bother asking when there is nothing to build', function () {
        artistTierlistSetup()
            ->call('confirmStartTierlist')
            ->assertNoRedirect();

        expect(Tierlist::count())->toBe(0);
    });

    it('creates the list and sends you to the board', function () {
        fakeSearch('artists', [
            spotifyArtist(),
            spotifyArtist(['id' => 'foster-the-people-id', 'name' => 'Foster the People']),
        ]);

        $user = kyle();

        Livewire::actingAs($user)
            ->test(ArtistSetup::class)
            ->set('searchTerm', 'psych rock')
            ->call('search')
            ->call('addEntry', 'tame-impala-id')
            ->call('addEntry', 'foster-the-people-id')
            ->set('form.name', 'Heads')
            ->call('startTierlist')
            ->assertRedirect(route('tierlist', ['id' => Tierlist::first()->getKey()]));

        $tierlist = Tierlist::first();

        expect($tierlist->name)->toBe('Heads')
            ->and($tierlist->user_id)->toBe($user->getKey())
            ->and($tierlist->type)->toBe(TierlistType::ARTIST)
            ->and($tierlist->bank->items)->toHaveCount(2)
            ->and(Artist::count())->toBe(2);
    });
});

describe('the allowance', function () {
    it('blocks a search once the type allowance is spent', function () {
        $user = kyle();
        Tierlist::factory()->for($user)->count(3)->create(['type' => TierlistType::ARTIST->value]);

        $component = Livewire::actingAs($user)
            ->test(ArtistSetup::class)
            ->set('searchTerm', 'radiohead')
            ->call('search');

        expect($component->get('searchResults'))->toBeNull()
            ->and($component->instance()->tierlistLimitReached())->toBeTrue();
    });

    it('leaves the other types alone', function () {
        $user = kyle();
        Tierlist::factory()->for($user)->count(3)->create(['type' => TierlistType::ARTIST->value]);

        $component = Livewire::actingAs($user)->test(AlbumSetup::class);

        expect($component->instance()->tierlistLimitReached())->toBeFalse();
    });

    it('will not let a capped account start one from a stale page', function () {
        fakeSearch('artists', [
            spotifyArtist(),
            spotifyArtist(['id' => 'foster-the-people-id', 'name' => 'Foster the People']),
        ]);

        $user = kyle();

        $component = Livewire::actingAs($user)
            ->test(ArtistSetup::class)
            ->set('searchTerm', 'psych rock')
            ->call('search')
            ->call('addEntry', 'tame-impala-id')
            ->call('addEntry', 'foster-the-people-id');

        Tierlist::factory()->for($user)->count(3)->create(['type' => TierlistType::ARTIST->value]);

        $component->call('startTierlist')->assertNoRedirect();

        expect(Tierlist::count())->toBe(3);
    });

    it('stops the board filling past what the account allows', function () {
        config()->set('billing.tierlist_item_limits.free', 2);

        fakeSearch('artists', [
            spotifyArtist(['id' => 'one', 'name' => 'One']),
            spotifyArtist(['id' => 'two', 'name' => 'Two']),
            spotifyArtist(['id' => 'three', 'name' => 'Three']),
        ]);

        $component = artistTierlistSetup()
            ->set('searchTerm', 'heads')
            ->call('search')
            ->call('addEntry', 'one')
            ->call('addEntry', 'two')
            ->call('addEntry', 'three');

        expect($component->get('bank'))->toHaveCount(2);
    });
});

function artistTierlistSetup()
{
    return Livewire::actingAs(kyle())->test(ArtistSetup::class);
}
