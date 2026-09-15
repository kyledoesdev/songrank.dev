<?php

use App\Actions\Tierlists\CompleteTierlist;
use App\Actions\Tierlists\DestroyTier;
use App\Actions\Tierlists\MoveTierlistItem;
use App\Actions\Tierlists\ReorderTiers;
use App\Actions\Tierlists\StoreTier;
use App\Actions\Tierlists\UpdateTier;
use App\Models\Tier;
use App\Models\Tierlist;
use App\Models\TierlistItem;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

describe('moving an entry', function () {
    it('places one out of the bank and into a tier', function () {
        $board = board();
        $item = $board->entries->first();

        move($board->tierlist, $item, 0, $board->s);

        expect($item->fresh()->tier_id)->toBe($board->s->getKey())
            ->and($item->fresh()->position)->toBe(0);
    });

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

    it('refuses an entry from somebody else board', function () {
        $board = board();
        $stranger = Tierlist::factory()->create();
        $theirs = TierlistItem::factory()->inTier($stranger->bank)->create();

        expect(fn () => move($board->tierlist, $theirs, 0, $board->s))
            ->toThrow(ModelNotFoundException::class);
    });
});

describe('what a move costs', function () {
    it('costs the same on a crowded tier as on an empty one', function () {
        expect(queriesToMoveOutOf(2))->toBe(queriesToMoveOutOf(40));
    });
});
describe('adding a tier', function () {
    it('appends it below the ones already there', function () {
        $board = board();

        $tier = (new StoreTier)->handle($board->tierlist, ['name' => 'SS', 'color' => '#ff00ff']);

        expect($board->tierlist->fresh()->placementTiers()->last()->is($tier))->toBeTrue()
            ->and($tier->position)->toBe(7);
    });

});

describe('editing a tier', function () {
    it('renames and recolours it', function () {
        $board = board();

        (new UpdateTier)->handle($board->s, ['name' => 'God Tier', 'color' => '#123456']);

        expect($board->s->fresh()->name)->toBe('God Tier')
            ->and($board->s->fresh()->color)->toBe('#123456');
    });

});

describe('deleting a tier', function () {
    it('hands whatever sat in it back to the bank', function () {
        $board = board(2);
        [$first, $second] = $board->entries->all();

        move($board->tierlist, $first, 0, $board->s);
        move($board->tierlist, $second, 1, $board->s);

        expect((new DestroyTier)->handle($board->tierlist, $board->s))->toBeTrue();

        expect($first->fresh()->tier_id)->toBe($board->tierlist->bank->getKey())
            ->and($second->fresh()->tier_id)->toBe($board->tierlist->bank->getKey())
            ->and(TierlistItem::count())->toBe(2);
    });

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

describe('finishing', function () {
    it('refuses while anything is still in the bank', function () {
        $board = board();

        expect((new CompleteTierlist)->handle($board->tierlist))->toBeFalse()
            ->and($board->tierlist->fresh()->is_complete)->toBeFalse();
    });

    it('publishes once the bank is empty', function () {
        $board = board();

        move($board->tierlist, $board->entries->first(), 0, $board->s);

        expect((new CompleteTierlist)->handle($board->tierlist->fresh()))->toBeTrue();

        $tierlist = $board->tierlist->fresh();

        expect($tierlist->is_complete)->toBeTrue()
            ->and($tierlist->getAttributes()['completed_at'])->not->toBeNull();
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
