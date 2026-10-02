<?php

use App\Livewire\Dashboard\InProgress;
use App\Livewire\Tierlist\Card as TierlistCard;
use App\Livewire\Tierlist\TierlistPanel;
use App\Models\Artist;
use App\Models\Ranking;
use App\Models\Tierlist;
use App\Models\TierlistItem;
use App\Models\User;
use Livewire\Livewire;

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

    it('draws the same card as rankings and reviews, with what is placed so far', function () {
        $user = kyle();
        $tierlist = Tierlist::factory()->for($user)->create();
        $artist = Artist::factory()->create(['artist_img' => 'https://example.test/art.png']);

        TierlistItem::factory()
            ->inTier($tierlist->placementTiers()->first())
            ->create(['entryable_id' => $artist->getKey()]);

        Livewire::actingAs($user)
            ->test(InProgress::class)
            ->assertSeeLivewire(TierlistCard::class)
            ->assertSee('https://example.test/art.png', escape: false)
            ->assertSee('In Progress');
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
