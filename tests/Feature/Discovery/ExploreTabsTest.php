<?php

use App\Livewire\Explorer\TierlistsFeed;
use App\Models\Tierlist;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

describe('explore tabs', function () {
    test('rankings tab is the default', function () {
        $user = kyle();
        publicCompletedRanking(attributes: ['user_id' => $user->getKey()]);

        actingAs($user)
            ->get(route('explore'))
            ->assertOk()
            ->assertSee("tab = 'rankings'", escape: false);
    });

    test('tier lists tab is visible when feature flag is active', function () {
        $user = kyle();

        actingAs($user)
            ->get(route('explore'))
            ->assertOk()
            ->assertSee('Tier Lists');
    });

    test('tier lists tab is hidden when feature flag is inactive', function () {
        $user = User::factory()->createOne();

        actingAs($user)
            ->get(route('explore'))
            ->assertOk()
            ->assertDontSee("tab = 'tierlists'", escape: false);
    });
});

describe('tier lists feed', function () {
    test('public completed tier lists are visible', function () {
        $tierlist = publicCompletedTierlist();

        Livewire::actingAs(kyle())
            ->test(TierlistsFeed::class)
            ->assertSee($tierlist->name);
    });

    test('private tier lists are hidden', function () {
        $tierlist = Tierlist::factory()->create([
            'is_public' => false,
            'is_complete' => true,
            'completed_at' => now(),
            'name' => 'Secret List',
        ]);

        Livewire::actingAs(kyle())
            ->test(TierlistsFeed::class)
            ->assertDontSee('Secret List');
    });

    test('incomplete tier lists are hidden', function () {
        $tierlist = Tierlist::factory()->create([
            'is_public' => true,
            'is_complete' => false,
            'name' => 'Unfinished List',
        ]);

        Livewire::actingAs(kyle())
            ->test(TierlistsFeed::class)
            ->assertDontSee('Unfinished List');
    });

    test('can search tier lists by name', function () {
        $match = publicCompletedTierlist(['name' => 'Best Albums Ever']);
        $other = publicCompletedTierlist(['name' => 'Other Tier List']);

        Livewire::actingAs(kyle())
            ->test(TierlistsFeed::class)
            ->set('search', 'Best Albums')
            ->call('performSearch')
            ->assertSee('Best Albums Ever')
            ->assertDontSee('Other Tier List');
    });

    test('load more increases visible results', function () {
        Livewire::actingAs(kyle())
            ->test(TierlistsFeed::class)
            ->assertSet('perPage', 12)
            ->call('loadMore')
            ->assertSet('perPage', 24);
    });
});
