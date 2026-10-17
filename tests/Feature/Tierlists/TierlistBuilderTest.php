<?php

use App\Livewire\Tierlist\TierlistBuilder;
use App\Models\Tierlist;
use App\Models\TierlistItem;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

describe('who gets the builder', function () {
    it('is what an owner sees while the board is unfinished', function () {
        $tierlist = Tierlist::factory()->for(User::factory())->create();

        actingAs($tierlist->user);

        get(route('tierlist', ['id' => $tierlist->getKey()]))
            ->assertOk()
            ->assertSeeLivewire(TierlistBuilder::class)
            ->assertSee('wire:click="confirmFinish"', escape: false);
    });

    it('gives way to the finished board once it is published', function () {
        $tierlist = Tierlist::factory()->for(User::factory())->complete()->create();

        actingAs($tierlist->user);

        get(route('tierlist', ['id' => $tierlist->getKey()]))
            ->assertOk()
            ->assertDontSeeLivewire(TierlistBuilder::class)
            ->assertDontSee('wire:click="confirmFinish"', escape: false);
    });

    it('refuses to build somebody else board', function () {
        $tierlist = Tierlist::factory()->create();

        Livewire::actingAs(User::factory()->createOne())
            ->test(TierlistBuilder::class, ['tierlist' => $tierlist])
            ->assertForbidden();
    });
});

describe('dragging', function () {
    it('places an entry into a tier', function () {
        $board = tierlistBuilder();

        $board->component->call('moveItem', $board->item->getKey(), 0, $board->tier->getKey());

        expect($board->item->fresh()->tier_id)->toBe($board->tier->getKey());
    });

    it('will not move an entry belonging to another board', function () {
        $board = tierlistBuilder();
        $theirs = TierlistItem::factory()->inTier(Tierlist::factory()->create()->bank)->create();

        $board->component->call('moveItem', $theirs->getKey(), 0, $board->tier->getKey())
            ->assertNotFound();

        expect($theirs->fresh()->tier_id)->not->toBe($board->tier->getKey());
    });

    it('will not drop an entry into a tier on another board', function () {
        $board = tierlistBuilder();
        $theirTier = Tierlist::factory()->create()->placementTiers()->first();

        $board->component->call('moveItem', $board->item->getKey(), 0, $theirTier->getKey())
            ->assertNotFound();

        expect($board->item->fresh()->tier_id)->not->toBe($theirTier->getKey());
    });
});

describe('tiers', function () {
    it('adds one to the bottom of the board', function () {
        $board = tierlistBuilder();

        $board->component->call('addTier');

        expect($board->tierlist->fresh()->placementTiers())->toHaveCount(count(config('tierlists.default_tiers')) + 1);
    });

    it('stops at the tier ceiling', function () {
        $defaults = count(config('tierlists.default_tiers'));
        config()->set('tierlists.max_tiers', $defaults);

        $board = tierlistBuilder();
        $board->component->call('addTier');

        expect($board->tierlist->fresh()->placementTiers())->toHaveCount($defaults);
    });

    it('renames and recolors one', function () {
        $board = tierlistBuilder();

        $board->component
            ->call('editTier', $board->tier->getKey())
            ->assertSet('tierName', $board->tier->name)
            ->set('tierName', 'God Tier')
            ->set('tierColor', '#123456')
            ->call('saveTier')
            ->assertSet('editingTierId', null);

        expect($board->tier->fresh()->name)->toBe('God Tier')
            ->and($board->tier->fresh()->color)->toBe('#123456');
    });

    it('rejects a color that is not a hex color', function () {
        $board = tierlistBuilder();

        $board->component
            ->call('editTier', $board->tier->getKey())
            ->set('tierColor', 'reddish')
            ->call('saveTier')
            ->assertHasErrors('tierColor');

        expect($board->tier->fresh()->color)->toBe('#FF7F7F');
    });

    it('rejects an empty name', function () {
        $board = tierlistBuilder();

        $board->component
            ->call('editTier', $board->tier->getKey())
            ->set('tierName', '')
            ->call('saveTier')
            ->assertHasErrors('tierName');
    });

    it('deletes one, handing its entries back to the bank', function () {
        $board = tierlistBuilder();

        $board->component
            ->call('moveItem', $board->item->getKey(), 0, $board->tier->getKey())
            ->call('deleteTier', $board->tier->getKey());

        expect($board->tierlist->fresh()->placementTiers())->toHaveCount(count(config('tierlists.default_tiers')) - 1)
            ->and($board->item->fresh()->tier_id)->toBe($board->tierlist->bank->getKey());
    });

    it('moves one up the board', function () {
        $board = tierlistBuilder();
        $second = $board->tierlist->placementTiers()->get(1);

        $board->component->call('moveTier', $second->getKey(), 'up');

        expect($board->tierlist->fresh()->placementTiers()->first()->is($second))->toBeTrue();
    });

    it('will not delete the bank', function () {
        $board = tierlistBuilder();

        $board->component->call('deleteTier', $board->tierlist->bank->getKey())
            ->assertNotFound();

        expect($board->tierlist->fresh()->bank)->not->toBeNull();
    });

    it('will not rename the bank', function () {
        $board = tierlistBuilder();

        $board->component->call('editTier', $board->tierlist->bank->getKey())
            ->assertNotFound();

        expect($board->tierlist->bank->fresh()->name)->toBe(config('tierlists.bank.name'));
    });

    it('will not shuffle the bank up the board', function () {
        $board = tierlistBuilder();

        $board->component->call('moveTier', $board->tierlist->bank->getKey(), 'down')
            ->assertNotFound();

        expect($board->tierlist->bank->fresh()->position)->toBe(0);
    });

    it('will not touch a tier on another board', function () {
        $board = tierlistBuilder();
        $theirTier = Tierlist::factory()->create()->placementTiers()->first();

        $board->component->call('deleteTier', $theirTier->getKey())
            ->assertNotFound();

        expect($theirTier->fresh())->not->toBeNull();
    });
});

describe('publishing', function () {
    it('will not publish while the bank still holds something', function () {
        $board = tierlistBuilder();

        $board->component->call('finish')->assertNoRedirect();

        expect($board->tierlist->fresh()->is_complete)->toBeFalse();
    });

    it('publishes once everything is placed', function () {
        $board = tierlistBuilder();

        $board->component
            ->call('moveItem', $board->item->getKey(), 0, $board->tier->getKey())
            ->call('finish')
            ->assertRedirect(route('tierlist', ['id' => $board->tierlist->getKey()]));

        expect($board->tierlist->fresh())
            ->is_complete->toBeTrue()
            ->completed_at->not->toBeNull();
    });

});

describe('a tab left open past publishing', function () {
    it('stops a free owner rearranging the board once it is published', function () {
        $board = tierlistBuilder();

        $board->tierlist->update(['is_complete' => true, 'completed_at' => now()]);

        $board->component
            ->call('moveItem', $board->item->getKey(), 0, $board->tier->getKey())
            ->assertForbidden();

        expect($board->item->fresh()->tier_id)->toBe($board->tierlist->bank->getKey());
    });

    it('stops a free owner changing the tiers once it is published', function () {
        $board = tierlistBuilder();
        $tiers = $board->tierlist->placementTiers()->count();

        $board->tierlist->update(['is_complete' => true, 'completed_at' => now()]);

        $board->component->call('addTier')->assertForbidden();

        expect($board->tierlist->fresh()->placementTiers())->toHaveCount($tiers);
    });

    it('lets a Pro owner keep rearranging a published board', function () {
        $user = proUser();
        $tierlist = Tierlist::factory()->for($user)->complete()->create();
        $item = $tierlist->items->first();
        $tier = $tierlist->placementTiers()->first();

        Livewire::actingAs($user)
            ->test(TierlistBuilder::class, ['tierlist' => $tierlist])
            ->call('moveItem', $item->getKey(), 0, $tier->getKey())
            ->assertOk();

        expect($item->fresh()->tier_id)->toBe($tier->getKey());
    });
});

describe('the players toggle', function () {
    it('is off to begin with, so the board is artwork', function () {
        tierlistBuilder()->component
            ->assertSet('showEmbeds', false)
            ->assertDontSee('open.spotify.com/embed');
    });

    it('swaps the artwork for players when switched on', function () {
        tierlistBuilder()->component
            ->set('showEmbeds', true)
            ->assertSee('open.spotify.com/embed', escape: false);
    });
});

/**
 * A mounted builder over a board with one entry in the bank, plus its top tier.
 */
function tierlistBuilder(): object
{
    $user = User::factory()->createOne();
    $tierlist = Tierlist::factory()->for($user)->create();
    $item = TierlistItem::factory()->inTier($tierlist->bank)->create();

    return (object) [
        'tierlist' => $tierlist,
        'tier' => $tierlist->placementTiers()->first(),
        'item' => $item,
        'component' => Livewire::actingAs($user)->test(TierlistBuilder::class, ['tierlist' => $tierlist]),
    ];
}
