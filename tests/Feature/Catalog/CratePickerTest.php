<?php

use App\Actions\Catalog\MoveCrateItem;
use App\Actions\Catalog\SyncEntityCrates;
use App\Livewire\Catalog\CratePicker;
use App\Livewire\Reviews\Setup\AlbumSetup as ReviewAlbumSetup;
use App\Livewire\SongRank\Setup\ArtistSetup as RankingArtistSetup;
use App\Livewire\Tierlist\Setup\ArtistSetup as TierlistArtistSetup;
use App\Models\Crate;
use App\Models\CrateItem;
use App\Models\Ranking;
use App\Models\Review;
use App\Models\Tierlist;
use App\Models\User;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

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

describe('the picker on a finished record', function () {
    it('starts on the crates the record is already in', function () {
        $user = proUser();
        $ranking = Ranking::factory()->for($user)->create();
        [$filed] = Crate::factory()->count(2)->for($user)->create();
        CrateItem::factory()->for($filed)->forRanking($ranking)->create();

        Livewire::actingAs($user)
            ->test(CratePicker::class, ['entity' => $ranking])
            ->assertSet('crateIds', [$filed->getKey()]);
    });

    it('files the record as each box is ticked', function () {
        $user = proUser();
        $ranking = Ranking::factory()->for($user)->create();
        [$first, $second] = Crate::factory()->count(2)->for($user)->create();

        Livewire::actingAs($user)
            ->test(CratePicker::class, ['entity' => $ranking])
            ->set('crateIds', [(string) $first->getKey(), (string) $second->getKey()]);

        expect(crateContents($first))->toBe([crateEntry($ranking)])
            ->and(crateContents($second))->toBe([crateEntry($ranking)]);
    });

    it('takes the record out as a box is unticked', function () {
        $user = proUser();
        $ranking = Ranking::factory()->for($user)->create();
        $crate = Crate::factory()->for($user)->create();
        CrateItem::factory()->for($crate)->forRanking($ranking)->create();

        Livewire::actingAs($user)
            ->test(CratePicker::class, ['entity' => $ranking])
            ->set('crateIds', []);

        expect(crateContents($crate))->toBe([]);
    });

    it('lays crates out under the crate they sit in', function () {
        $user = proUser();
        $parent = Crate::factory()->for($user)->create();
        $nested = Crate::factory()->nestedIn($parent)->create();
        Crate::factory()->create();

        $picker = Livewire::actingAs($user)
            ->test(CratePicker::class, ['entity' => Ranking::factory()->for($user)->create()])
            ->instance();

        expect($picker->childrenOf()->modelKeys())->toBe([$parent->getKey()])
            ->and($picker->childrenOf($parent)->modelKeys())->toBe([$nested->getKey()]);
    });

    it('makes a crate, ticks it and files the record in it', function () {
        $user = User::factory()->createOne();
        $ranking = Ranking::factory()->for($user)->create();

        $component = Livewire::actingAs($user)
            ->test(CratePicker::class, ['entity' => $ranking])
            ->set('newCrateName', 'Foster the People')
            ->call('createCrate')
            ->assertSet('newCrateName', '');

        $crate = $user->crates()->sole();

        expect($component->get('crateIds'))->toBe([$crate->getKey()])
            ->and(crateContents($crate))->toBe([crateEntry($ranking)]);
    });

    it('turns a new crate away at the limit', function () {
        $user = userWithCrates(5);

        $component = Livewire::actingAs($user)
            ->test(CratePicker::class, ['entity' => Ranking::factory()->for($user)->create()])
            ->assertDontSee('wire:submit="createCrate"', escape: false)
            ->set('newCrateName', 'One Too Many')
            ->call('createCrate');

        expect(alertsFrom($component)->pluck('icon')->all())->toBe(['error'])
            ->and($user->crates()->count())->toBe(5);
    });

    it('belongs to the record\'s owner alone', function () {
        Livewire::actingAs(User::factory()->createOne())
            ->test(CratePicker::class, ['entity' => Ranking::factory()->create()])
            ->assertForbidden();
    });
});

describe('the picker in a setup', function () {
    it('leaves the filing to the setup', function () {
        $user = User::factory()->createOne();
        $crate = Crate::factory()->for($user)->create();

        Livewire::actingAs($user)
            ->test(CratePicker::class)
            ->set('crateIds', [(string) $crate->getKey()]);

        expect(crateContents($crate))->toBe([]);
    });

    it('files a new ranking in the crates picked', function () {
        $user = User::factory()->createOne();
        $crate = Crate::factory()->for($user)->create();

        rankingArtistSetup($user)
            ->set('crateIds', [(string) $crate->getKey()])
            ->call('beginRanking');

        expect(crateContents($crate))->toBe([crateEntry($user->rankings()->sole())]);
    });

    it('files a new tier list in the crates picked', function () {
        $user = User::factory()->createOne();
        $crate = Crate::factory()->for($user)->create();

        Livewire::actingAs($user)
            ->test(TierlistArtistSetup::class)
            ->set('bank', collect([artistEntry(), artistEntry(['id' => 'foster-the-people-id', 'name' => 'Foster the People'])]))
            ->set('crateIds', [(string) $crate->getKey()])
            ->call('startTierlist');

        expect(crateContents($crate))->toBe([crateEntry($user->tierlists()->sole())]);
    });

    it('files a new review in the crates picked', function () {
        $user = User::factory()->createOne();
        $crate = Crate::factory()->for($user)->create();

        Livewire::actingAs($user)
            ->test(ReviewAlbumSetup::class)
            ->set('subject', albumEntry())
            ->set('crateIds', [(string) $crate->getKey()])
            ->call('startReview');

        expect(crateContents($crate))->toBe([crateEntry($user->reviews()->sole())]);
    });

    it('files nothing when no crate was picked', function () {
        $user = User::factory()->createOne();
        Crate::factory()->for($user)->create();

        rankingArtistSetup($user)->call('beginRanking');

        expect(CrateItem::query()->count())->toBe(0);
    });

    it('sits in every setup form', function (Testable $setup) {
        $setup->assertSeeLivewire(CratePicker::class);
    })->with([
        'a ranking' => fn () => rankingArtistSetup(User::factory()->createOne()),
        'a tier list' => fn () => Livewire::actingAs(User::factory()->createOne())->test(TierlistArtistSetup::class),
        'a review' => fn () => Livewire::actingAs(User::factory()->createOne())->test(ReviewAlbumSetup::class),
    ]);
});

describe('where the picker shows up', function () {
    it('is on a finished record for its owner', function (Closure $record, string $route) {
        $entity = $record();

        actingAs($entity->user);

        get(route($route, ['id' => $entity->getKey()]))->assertSeeLivewire(CratePicker::class);
    })->with([
        'a ranking' => [fn () => Ranking::factory()->create(['is_ranked' => true, 'completed_at' => now()]), 'ranking'],
        'a tier list' => [fn () => Tierlist::factory()->complete()->create(), 'tierlist'],
        'a review' => [fn () => Review::factory()->published()->create(), 'review'],
    ]);

    it('waits until a ranking is finished', function () {
        $ranking = Ranking::factory()->create();

        actingAs($ranking->user);

        get(route('ranking', ['id' => $ranking->getKey()]))->assertDontSeeLivewire(CratePicker::class);
    });

    it('waits until a tier list is finished', function () {
        $tierlist = Tierlist::factory()->create();

        actingAs($tierlist->user);

        get(route('tierlist', ['id' => $tierlist->getKey()]))->assertDontSeeLivewire(CratePicker::class);
    });

    it('is not offered to anyone else viewing a record', function () {
        $ranking = publicCompletedRanking();

        actingAs(User::factory()->createOne());

        get(route('ranking', ['id' => $ranking->getKey()]))->assertDontSeeLivewire(CratePicker::class);
    });
});

/**
 * An artist ranking setup that opens with an artist picked and two tracks to
 * rank, so the form is there on the first render.
 */
function rankingArtistSetup(User $user): Testable
{
    return Livewire::actingAs($user)->test(RankingArtistSetup::class, [
        'selectedArtist' => [
            'id' => 'tame-impala-id',
            'name' => 'Tame Impala',
            'cover' => 'https://example.test/tame-impala.png',
        ],
        'selectedTracks' => collect([
            ['id' => 'let-it-happen-id', 'name' => 'Let It Happen', 'cover' => 'https://example.test/currents.png', 'uuid' => str()->uuid()->toString(), 'album_name' => 'Currents', 'album_id' => 'currents-id', 'album_type' => 'album'],
            ['id' => 'eventually-id', 'name' => 'Eventually', 'cover' => 'https://example.test/currents.png', 'uuid' => str()->uuid()->toString(), 'album_name' => 'Currents', 'album_id' => 'currents-id', 'album_type' => 'album'],
        ]),
    ]);
}
