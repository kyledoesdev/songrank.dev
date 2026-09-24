<?php

use App\Livewire\Tierlist\TierlistBuilder;
use App\Models\Tierlist;
use App\Models\TierlistItem;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

describe('who gets the builder', function () {
    it('is what an owner sees while the board is unfinished', function () {
        $tierlist = Tierlist::factory()->for(kyle())->create();

        actingAs($tierlist->user);

        get(route('tierlist', ['id' => $tierlist->getKey()]))
            ->assertOk()
            ->assertSee('Unranked')
            ->assertSee('Publish');
    });

    it('gives way to the finished board once it is published', function () {
        $tierlist = Tierlist::factory()->for(kyle())->complete()->create();

        actingAs($tierlist->user);

        get(route('tierlist', ['id' => $tierlist->getKey()]))
            ->assertOk()
            ->assertDontSee('Publish');
    });

    it('refuses to build somebody else board', function () {
        $tierlist = Tierlist::factory()->create();

        Livewire::actingAs(kyle())
            ->test(TierlistBuilder::class, ['tierlist' => $tierlist])
            ->assertForbidden();
    });
});

describe('dragging', function () {
    it('places an entry into a tier', function () {
        $board = builder();

        $board->component->call('moveItem', $board->item->getKey(), 0, $board->tier->getKey());

        expect($board->item->fresh()->tier_id)->toBe($board->tier->getKey());
    });

    it('will not move an entry belonging to another board', function () {
        $board = builder();
        $theirs = TierlistItem::factory()->inTier(Tierlist::factory()->create()->bank)->create();

        expect(fn () => $board->component->call('moveItem', $theirs->getKey(), 0, $board->tier->getKey()))
            ->toThrow(ModelNotFoundException::class);
    });

    it('will not drop an entry into a tier on another board', function () {
        $board = builder();
        $theirTier = Tierlist::factory()->create()->placementTiers()->first();

        expect(fn () => $board->component->call('moveItem', $board->item->getKey(), 0, $theirTier->getKey()))
            ->toThrow(ModelNotFoundException::class);
    });
});

describe('tiers', function () {
    it('adds one to the bottom of the board', function () {
        $board = builder();

        $board->component->call('addTier');

        expect($board->tierlist->fresh()->placementTiers())->toHaveCount(7);
    });

    it('stops at the tier ceiling', function () {
        config()->set('tierlists.max_tiers', 6);

        $board = builder();
        $board->component->call('addTier');

        expect($board->tierlist->fresh()->placementTiers())->toHaveCount(6);
    });

    it('renames and recolours one', function () {
        $board = builder();

        $board->component
            ->call('editTier', $board->tier->getKey())
            ->assertSet('tierName', 'S')
            ->set('tierName', 'God Tier')
            ->set('tierColor', '#123456')
            ->call('saveTier')
            ->assertSet('editingTierId', null);

        expect($board->tier->fresh()->name)->toBe('God Tier')
            ->and($board->tier->fresh()->color)->toBe('#123456');
    });

    it('rejects a colour that is not a hex colour', function () {
        $board = builder();

        $board->component
            ->call('editTier', $board->tier->getKey())
            ->set('tierColor', 'reddish')
            ->call('saveTier')
            ->assertHasErrors('tierColor');

        expect($board->tier->fresh()->color)->toBe('#FF7F7F');
    });

    it('rejects an empty name', function () {
        $board = builder();

        $board->component
            ->call('editTier', $board->tier->getKey())
            ->set('tierName', '')
            ->call('saveTier')
            ->assertHasErrors('tierName');
    });

    it('deletes one, handing its entries back to the bank', function () {
        $board = builder();

        $board->component
            ->call('moveItem', $board->item->getKey(), 0, $board->tier->getKey())
            ->call('deleteTier', $board->tier->getKey());

        expect($board->tierlist->fresh()->placementTiers())->toHaveCount(5)
            ->and($board->item->fresh()->tier_id)->toBe($board->tierlist->bank->getKey());
    });

    it('moves one up the board', function () {
        $board = builder();
        $second = $board->tierlist->placementTiers()->get(1);

        $board->component->call('moveTier', $second->getKey(), 'up');

        expect($board->tierlist->fresh()->placementTiers()->first()->name)->toBe('A');
    });

    it('will not delete the bank', function () {
        $board = builder();

        expect(fn () => $board->component->call('deleteTier', $board->tierlist->bank->getKey()))
            ->toThrow(ModelNotFoundException::class);

        expect($board->tierlist->fresh()->bank)->not->toBeNull();
    });

    it('will not rename the bank', function () {
        $board = builder();

        expect(fn () => $board->component->call('editTier', $board->tierlist->bank->getKey()))
            ->toThrow(ModelNotFoundException::class);
    });

    it('will not shuffle the bank up the board', function () {
        $board = builder();

        expect(fn () => $board->component->call('moveTier', $board->tierlist->bank->getKey(), 'down'))
            ->toThrow(ModelNotFoundException::class);

        expect($board->tierlist->bank->fresh()->position)->toBe(0);
    });

    it('will not touch a tier on another board', function () {
        $board = builder();
        $theirTier = Tierlist::factory()->create()->placementTiers()->first();

        expect(fn () => $board->component->call('deleteTier', $theirTier->getKey()))
            ->toThrow(ModelNotFoundException::class);
    });
});

describe('publishing', function () {
    it('will not publish while the bank still holds something', function () {
        $board = builder();

        $board->component->call('finish')->assertNoRedirect();

        expect($board->tierlist->fresh()->is_complete)->toBeFalse();
    });

    it('publishes once everything is placed', function () {
        $board = builder();

        $board->component
            ->call('moveItem', $board->item->getKey(), 0, $board->tier->getKey())
            ->call('finish')
            ->assertRedirect(route('tierlist', ['id' => $board->tierlist->getKey()]));

        expect($board->tierlist->fresh()->is_complete)->toBeTrue();
    });

});

describe('the players toggle', function () {
    it('is off to begin with, so the board is artwork', function () {
        builder()->component
            ->assertSet('showEmbeds', false)
            ->assertDontSee('open.spotify.com/embed');
    });

    it('swaps the artwork for players when switched on', function () {
        builder()->component
            ->set('showEmbeds', true)
            ->assertSee('open.spotify.com/embed', escape: false);
    });
});

/**
 * A mounted builder over a board with one entry in the bank, plus its top tier.
 */
function builder(): object
{
    $user = kyle();
    $tierlist = Tierlist::factory()->for($user)->create();
    $item = TierlistItem::factory()->inTier($tierlist->bank)->create();

    return (object) [
        'tierlist' => $tierlist,
        'tier' => $tierlist->placementTiers()->first(),
        'item' => $item,
        'component' => Livewire::actingAs($user)->test(TierlistBuilder::class, ['tierlist' => $tierlist]),
    ];
}
