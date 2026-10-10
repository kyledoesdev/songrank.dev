<?php

use App\Actions\Rankings\DestroyRanking;
use App\Actions\Reviews\DestroyReview;
use App\Jobs\DeleteUserJob;
use App\Livewire\Tierlist\Card;
use App\Models\Crate;
use App\Models\CrateItem;
use App\Models\Ranking;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertModelMissing;

describe('deleting something filed in crates', function () {
    it('takes a deleted ranking out of every crate', function () {
        $user = proUser();
        $ranking = Ranking::factory()->for($user)->create();
        $items = Crate::factory()->count(2)->for($user)->create()
            ->map(fn (Crate $crate) => CrateItem::factory()->for($crate)->forRanking($ranking)->create());

        (new DestroyRanking)->handle($user, $ranking);

        $items->each(fn (CrateItem $item) => assertModelMissing($item));
    });

    it('takes a deleted review out of its crate', function () {
        $item = CrateItem::factory()->forReview()->create();

        (new DestroyReview)->handle($item->entity);

        assertModelMissing($item);
    });

    it('takes a deleted tier list out of its crate', function () {
        $item = CrateItem::factory()->forTierlist()->create();

        Livewire::actingAs($item->crate->user)
            ->test(Card::class, ['tierlist' => $item->entity])
            ->call('destroy');

        assertModelMissing($item);
    });

    it('cleans up after a delete that skips the actions, like the admin panel\'s', function () {
        $item = CrateItem::factory()->forRanking()->create();

        $item->entity->delete();

        assertModelMissing($item);
    });

    it('closes the gap the record leaves', function () {
        $crate = Crate::factory()->create();
        $deleted = CrateItem::factory()->for($crate)->forRanking()->create();
        $after = CrateItem::factory()->for($crate)->forReview()->create();

        $deleted->entity->delete();

        expect($after->fresh()->position)->toBe(0);
    });
});

describe('deleting an account', function () {
    it('removes its crates and everything filed in them', function () {
        $user = proUser();
        $crate = Crate::factory()->for($user)->create();
        Crate::factory()->nestedIn($crate)->create();
        CrateItem::factory()->count(2)->for($crate)->create();
        $someoneElses = CrateItem::factory()->create();
        Notification::fake();

        (new DeleteUserJob($user))->handle();

        assertDatabaseCount('crates', 1);
        assertDatabaseCount('crate_items', 1);
        expect($someoneElses->fresh())->not->toBeNull();
    });
});
