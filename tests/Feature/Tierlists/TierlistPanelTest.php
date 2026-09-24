<?php

use App\Enums\TierlistType;
use App\Livewire\Tierlist\TierlistPanel;
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
