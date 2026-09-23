<?php

use App\Actions\Tierlists\MoveTierlistItem;
use App\Actions\Tierlists\Tiers\DestroyTier;
use App\Actions\Tierlists\Tiers\ReorderTiers;
use App\Models\Artist;
use App\Models\Tier;
use App\Models\Tierlist;
use App\Models\TierlistItem;
use Illuminate\Support\Facades\DB;

describe('moving an entry', function () {
    it('splices it in at the slot it was dropped on', function () {
        $board = board(3);
        [$first, $second, $third] = $board->entries->all();

        move($board->tierlist, $first, 0, $board->s);
        move($board->tierlist, $second, 1, $board->s);
        move($board->tierlist, $third, 1, $board->s);

        expect(positions($board->s))->toBe([$first->id, $third->id, $second->id]);
    });

    it('closes the gap in the tier it came from', function () {
        $board = board(3);
        [$first, $second, $third] = $board->entries->all();

        move($board->tierlist, $first, 0, $board->s);
        move($board->tierlist, $second, 1, $board->s);
        move($board->tierlist, $third, 2, $board->s);

        move($board->tierlist, $second, 0, $board->a);

        expect(positions($board->s))->toBe([$first->id, $third->id])
            ->and($board->s->items()->orderBy('position')->pluck('position')->all())->toBe([0, 1])
            ->and(positions($board->a))->toBe([$second->id]);
    });

    it('leaves positions gap free however far past the end it is dropped', function () {
        $board = board(2);
        [$first, $second] = $board->entries->all();

        move($board->tierlist, $first, 0, $board->s);
        move($board->tierlist, $second, 99, $board->s);

        expect($board->s->items()->orderBy('position')->pluck('position')->all())->toBe([0, 1])
            ->and(positions($board->s))->toBe([$first->id, $second->id]);
    });

    it('reorders within a tier without touching any other', function () {
        $board = board(3);
        [$first, $second, $third] = $board->entries->all();

        move($board->tierlist, $first, 0, $board->s);
        move($board->tierlist, $second, 1, $board->s);
        move($board->tierlist, $third, 0, $board->a);

        move($board->tierlist, $second, 0, $board->s);

        expect(positions($board->s))->toBe([$second->id, $first->id])
            ->and(positions($board->a))->toBe([$third->id]);
    });

});

describe('what a move costs', function () {
    it('costs the same on a crowded tier as on an empty one', function () {
        expect(queriesToMoveOutOf(2))->toBe(queriesToMoveOutOf(40));
    });
});
describe('deleting a tier', function () {
    it('closes the gap it left in the ordering', function () {
        $board = board();

        (new DestroyTier)->handle($board->tierlist, $board->s);

        expect($board->tierlist->fresh()->placementTiers()->pluck('position')->all())
            ->toBe([1, 2, 3, 4, 5]);
    });

    it('refuses to leave a board with nowhere to place anything', function () {
        $board = board();

        foreach ($board->tierlist->placementTiers()->slice(1) as $tier) {
            (new DestroyTier)->handle($board->tierlist->fresh(), $tier);
        }

        $last = $board->tierlist->fresh()->placementTiers()->sole();

        expect((new DestroyTier)->handle($board->tierlist->fresh(), $last))->toBeFalse()
            ->and($board->tierlist->fresh()->placementTiers())->toHaveCount(1);
    });
});

describe('reordering tiers', function () {
    it('swaps a tier with the one above it', function () {
        $board = board();

        (new ReorderTiers)->handle($board->tierlist, $board->a, 'up');

        expect($board->tierlist->fresh()->placementTiers()->pluck('name')->all())
            ->toBe(['A', 'S', 'B', 'C', 'D', 'F']);
    });

    it('swaps a tier with the one below it', function () {
        $board = board();

        (new ReorderTiers)->handle($board->tierlist, $board->s, 'down');

        expect($board->tierlist->fresh()->placementTiers()->pluck('name')->all())
            ->toBe(['A', 'S', 'B', 'C', 'D', 'F']);
    });

    it('takes whatever sat in the tier along with it', function () {
        $board = board();
        $item = $board->entries->first();

        move($board->tierlist, $item, 0, $board->a);
        (new ReorderTiers)->handle($board->tierlist, $board->a, 'up');

        $top = $board->tierlist->fresh()->placementTiers()->first();

        expect($top->name)->toBe('A')
            ->and($top->items->first()->is($item))->toBeTrue();
    });

    it('does nothing at the top of the board', function () {
        $board = board();

        expect((new ReorderTiers)->handle($board->tierlist, $board->s, 'up'))->toBeFalse()
            ->and($board->tierlist->fresh()->placementTiers()->first()->name)->toBe('S');
    });

    it('does nothing at the bottom of the board', function () {
        $board = board();
        $bottom = $board->tierlist->placementTiers()->last();

        expect((new ReorderTiers)->handle($board->tierlist, $bottom, 'down'))->toBeFalse();
    });

});

describe('reading order', function () {
    it('reads the top tier left to right, then down', function () {
        $tierlist = Tierlist::factory()->create();
        [$s, $a] = $tierlist->placementTiers()->take(2)->all();

        $second = namedEntry('second', $tierlist, $s, position: 1);
        $first = namedEntry('first', $tierlist, $s, position: 0);
        $third = namedEntry('third', $tierlist, $a, position: 0);

        $ranked = $tierlist->fresh()->rankedItems();

        expect($ranked->pluck('id')->all())->toBe([$first->id, $second->id, $third->id]);
    });

    it('leaves unplaced entries out of the reading order', function () {
        $tierlist = Tierlist::factory()->create();

        TierlistItem::factory()->inTier($tierlist->bank)->create();
        $placed = TierlistItem::factory()->inTier($tierlist->placementTiers()->first())->create();

        expect($tierlist->fresh()->rankedItems()->pluck('id')->all())->toBe([$placed->id]);
    });
});

/**
 * A list with $entries sitting in the bank, plus its top two tiers to hand.
 */
function board(int $entries = 1): object
{
    $tierlist = Tierlist::factory()->create();
    $tiers = $tierlist->placementTiers();

    return (object) [
        'tierlist' => $tierlist,
        's' => $tiers->first(),
        'a' => $tiers->get(1),
        'entries' => collect(range(1, $entries))->map(
            fn (int $i) => TierlistItem::factory()->inTier($tierlist->bank, $i - 1)->create()
        ),
    ];
}

function move(Tierlist $tierlist, TierlistItem $item, int $position, Tier $tier): void
{
    (new MoveTierlistItem)->handle($tierlist, [
        'item_id' => $item->getKey(),
        'position' => $position,
        'tier_id' => $tier->getKey(),
    ]);
}

/**
 * @return array<int, int> item ids in board order
 */
function positions(Tier $tier): array
{
    return $tier->items()->orderBy('position')->pluck('id')->all();
}

/**
 * How many queries it takes to pull the last entry out of a tier holding $size
 * of them. Flat is the whole point: positions are shifted by two bulk updates
 * rather than rewritten row by row.
 */
function namedEntry(string $name, Tierlist $tierlist, Tier $tier, int $position): TierlistItem
{
    return TierlistItem::factory()
        ->inTier($tier, $position)
        ->for(Artist::factory()->create(['artist_name' => $name]), 'entryable')
        ->create();
}

function queriesToMoveOutOf(int $size): int
{
    $tierlist = Tierlist::factory()->create();
    $tier = $tierlist->placementTiers()->first();

    $items = collect(range(0, $size - 1))->map(
        fn (int $i) => TierlistItem::factory()->inTier($tier, $i)->create()
    );

    DB::flushQueryLog();
    DB::enableQueryLog();

    (new MoveTierlistItem)->handle($tierlist, [
        'item_id' => $items->last()->getKey(),
        'position' => 0,
        'tier_id' => $tierlist->bank->getKey(),
    ]);

    $count = count(DB::getQueryLog());

    DB::disableQueryLog();

    return $count;
}
