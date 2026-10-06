<?php

use App\Actions\Catalog\MoveCrateItem;
use App\Actions\Catalog\SyncEntityCrates;
use App\Models\Crate;
use App\Models\CrateItem;
use App\Models\Ranking;

describe('syncing a record\'s crates', function () {
    it('files it in every crate ticked', function () {
        $user = proUser();
        $ranking = Ranking::factory()->for($user)->create();
        [$first, $second] = Crate::factory()->count(2)->for($user)->create();

        (new SyncEntityCrates)->handle($user, $ranking, [$first->getKey(), $second->getKey()]);

        expect(crateContents($first))->toBe([crateEntry($ranking)])
            ->and(crateContents($second))->toBe([crateEntry($ranking)]);
    });

    it('takes it out of any crate no longer ticked', function () {
        $user = proUser();
        $ranking = Ranking::factory()->for($user)->create();
        [$kept, $dropped] = Crate::factory()->count(2)->for($user)->create();
        CrateItem::factory()->for($kept)->forRanking($ranking)->create();
        CrateItem::factory()->for($dropped)->forRanking($ranking)->create();

        (new SyncEntityCrates)->handle($user, $ranking, [$kept->getKey()]);

        expect(crateContents($kept))->toBe([crateEntry($ranking)])
            ->and(crateContents($dropped))->toBe([]);
    });

    it('takes it out of everything when nothing is ticked', function () {
        $user = proUser();
        $ranking = Ranking::factory()->for($user)->create();
        CrateItem::factory()->for(Crate::factory()->for($user))->forRanking($ranking)->create();

        (new SyncEntityCrates)->handle($user, $ranking, []);

        expect($ranking->crateItems()->count())->toBe(0);
    });

    it('leaves it where it was in a crate still ticked', function () {
        $user = proUser();
        $crate = Crate::factory()->for($user)->create();
        $ranking = Ranking::factory()->for($user)->create();
        $item = CrateItem::factory()->for($crate)->forRanking($ranking)->create();
        CrateItem::factory()->for($crate)->forTierlist()->create();
        (new MoveCrateItem)->handle($crate, ['item_id' => $item->getKey(), 'position' => 1]);

        (new SyncEntityCrates)->handle($user, $ranking, [$crate->getKey()]);

        expect($item->fresh()->position)->toBe(1);
    });

    it('closes the gap it leaves in a crate', function () {
        $user = proUser();
        $crate = Crate::factory()->for($user)->create();
        $ranking = Ranking::factory()->for($user)->create();
        CrateItem::factory()->for($crate)->forRanking($ranking)->create();
        $after = CrateItem::factory()->for($crate)->forReview()->create();

        (new SyncEntityCrates)->handle($user, $ranking, []);

        expect($after->fresh()->position)->toBe(0);
    });

    it('ignores crates that belong to someone else', function () {
        $user = proUser();
        $ranking = Ranking::factory()->for($user)->create();
        $theirs = Crate::factory()->create();

        (new SyncEntityCrates)->handle($user, $ranking, [$theirs->getKey()]);

        expect($theirs->items()->count())->toBe(0);
    });
});
