<?php

use App\Enums\TierlistType;
use App\Livewire\Dashboard\InProgress;
use App\Livewire\Tierlist\TierlistPanel;
use App\Livewire\Tierlist\TierlistSetup;
use App\Models\Artist;
use App\Models\Ranking;
use App\Models\Tierlist;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

describe('the panel on the dashboard', function () {
    it('stays off the dashboard while the feature is off', function () {
        actingAs(User::factory()->createOne());

        get(route('dashboard'))->assertDontSee('Tier Lists');
    });

    it('appears for somebody the feature is on for, offering every type', function () {
        actingAs(kyle());

        get(route('dashboard'))
            ->assertSee('Tier Lists')
            ->assertSee(route('tierlists.create', ['type' => 'artist']), escape: false)
            ->assertSee(route('tierlists.create', ['type' => 'album']), escape: false)
            ->assertSee(route('tierlists.create', ['type' => 'track']), escape: false);
    });

});

describe('what the panel offers', function () {
    it('sends a spent type to billing instead of to the setup page', function () {
        $user = kyle();
        Tierlist::factory()->for($user)->count(3)->create(['type' => TierlistType::ARTIST->value]);

        Livewire::actingAs($user)
            ->test(TierlistPanel::class)
            ->assertDontSee(route('tierlists.create', ['type' => 'artist']), escape: false)
            ->assertSee(route('tierlists.create', ['type' => 'album']), escape: false)
            ->assertSee(route('billing'), escape: false);
    });

    it('shows how much of each allowance is used', function () {
        $user = kyle();
        Tierlist::factory()->for($user)->create(['type' => TierlistType::ALBUM->value]);

        $component = Livewire::actingAs($user)->test(TierlistPanel::class);

        expect($component->instance()->countFor(TierlistType::ALBUM))->toBe(1)
            ->and($component->instance()->countFor(TierlistType::ARTIST))->toBe(0);
    });

    it('says unlimited for a pro account', function () {
        $user = proUser(['is_dev' => true]);

        Livewire::actingAs($user)
            ->test(TierlistPanel::class)
            ->assertSee('Unlimited');
    });
});

describe('getting back to a list', function () {
    it('lists unfinished ones under pick up where you left off, not in the setup panel', function () {
        $user = kyle();
        $tierlist = Tierlist::factory()->for($user)->create(['name' => 'Psych Rock, Ranked']);

        Livewire::actingAs($user)
            ->test(TierlistPanel::class)
            ->assertDontSee('Psych Rock, Ranked');

        Livewire::actingAs($user)
            ->test(InProgress::class)
            ->assertSee('Psych Rock, Ranked')
            ->assertSee(route('tierlist', ['id' => $tierlist->getKey()]), escape: false);
    });

    it('keeps finished lists out of it, the way finished rankings are', function () {
        $user = kyle();
        Tierlist::factory()->for($user)->complete()->create(['name' => 'All Done']);

        Livewire::actingAs($user)
            ->test(InProgress::class)
            ->assertDontSee('All Done');
    });

    it('leaves other people lists out of it', function () {
        Tierlist::factory()->create(['name' => 'Somebody Elses']);

        Livewire::actingAs(kyle())
            ->test(InProgress::class)
            ->assertDontSee('Somebody Elses');
    });

    it('draws the artwork of wherever a list came from', function () {
        $user = kyle();
        $artist = Artist::factory()->create(['artist_img' => 'https://example.test/art.png']);

        Tierlist::factory()->for($user)->albums()->fromArtist($artist)->create();

        Livewire::actingAs($user)
            ->test(InProgress::class)
            ->assertSee('https://example.test/art.png', escape: false);
    });

    it('shows rankings and tier lists together', function () {
        $user = kyle();
        Ranking::factory()->for($user)->create(['name' => 'A Ranking', 'is_ranked' => false]);
        Tierlist::factory()->for($user)->create(['name' => 'A Tier List']);

        Livewire::actingAs($user)
            ->test(InProgress::class)
            ->assertSee('A Ranking')
            ->assertSee('A Tier List');
    });

    it('stays away entirely when there is nothing to pick up', function () {
        Livewire::actingAs(kyle())
            ->test(InProgress::class)
            ->assertDontSee('Pick up where you left off');
    });

    it('leaves tier lists out while the feature is off', function () {
        $user = User::factory()->createOne();
        Tierlist::factory()->for($user)->create(['name' => 'Hidden List']);
        Ranking::factory()->for($user)->create(['name' => 'A Ranking', 'is_ranked' => false]);

        Livewire::actingAs($user)
            ->test(InProgress::class)
            ->assertSee('A Ranking')
            ->assertDontSee('Hidden List');
    });
});

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
