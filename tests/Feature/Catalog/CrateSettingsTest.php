<?php

use App\Actions\Catalog\DestroyCrate;
use App\Actions\Catalog\UpdateCrate;
use App\Models\Crate;
use App\Models\CrateIcon;
use App\Models\CrateItem;
use App\Models\Ranking;
use App\Models\User;

use function Pest\Laravel\assertModelExists;
use function Pest\Laravel\assertModelMissing;

describe('updating a crate', function () {
    it('renames, recolours and re-icons it', function () {
        $crate = Crate::factory()->create();
        $icon = CrateIcon::query()->where('icon', 'fa-record-vinyl')->firstOrFail();

        $updated = (new UpdateCrate)->handle($crate, crateSettings(['crate_icon_id' => $icon->getKey()]));

        expect($updated)->toBeTrue()
            ->and($crate->fresh())
            ->name->toBe('Sacred Hearts Club')
            ->color->toBe('#1E90FF')
            ->and($crate->fresh()->iconClass())->toBe('fa-record-vinyl');
    });

    it('falls back to a folder once the icon is cleared', function () {
        $crate = Crate::factory()->for(CrateIcon::factory(), 'icon')->create();

        (new UpdateCrate)->handle($crate, crateSettings(['crate_icon_id' => null]));

        expect($crate->fresh()->iconClass())->toBe(Crate::DEFAULT_ICON);
    });
});

describe('choosing an icon', function () {
    it('refuses a Pro icon on a free account', function () {
        $crate = Crate::factory()->create();
        $icon = CrateIcon::factory()->pro()->create();

        expect((new UpdateCrate)->handle($crate, crateSettings(['crate_icon_id' => $icon->getKey()])))->toBeFalse()
            ->and($crate->fresh()->crate_icon_id)->toBeNull()
            ->and($crate->fresh()->name)->not->toBe('Sacred Hearts Club');
    });

    it('allows a Pro icon on a Pro account', function () {
        $crate = Crate::factory()->for(proUser())->create();
        $icon = CrateIcon::factory()->pro()->create();

        expect((new UpdateCrate)->handle($crate, crateSettings(['crate_icon_id' => $icon->getKey()])))->toBeTrue()
            ->and($crate->fresh()->crate_icon_id)->toBe($icon->getKey());
    });

    it('refuses an icon that has been switched off', function () {
        $crate = Crate::factory()->create();
        $icon = CrateIcon::factory()->inactive()->create();

        expect((new UpdateCrate)->handle($crate, crateSettings(['crate_icon_id' => $icon->getKey()])))->toBeFalse();
    });

    it('lets a crate keep its Pro icon after Pro is revoked', function () {
        $user = proUser();
        $icon = CrateIcon::factory()->pro()->create();
        $crate = Crate::factory()->for($user)->create(['crate_icon_id' => $icon->getKey()]);

        revokePro($user);

        expect((new UpdateCrate)->handle($crate->fresh(), crateSettings(['crate_icon_id' => $icon->getKey()])))->toBeTrue()
            ->and($crate->fresh())
            ->crate_icon_id->toBe($icon->getKey())
            ->name->toBe('Sacred Hearts Club');
    });

    it('hides Pro and switched off icons from a free account', function () {
        $pro = CrateIcon::factory()->pro()->create();
        $inactive = CrateIcon::factory()->inactive()->create();

        $available = CrateIcon::query()->active()->availableTo(User::factory()->createOne())->pluck('id');

        expect($available)->not->toContain($pro->getKey(), $inactive->getKey())
            ->and(CrateIcon::query()->active()->availableTo(proUser())->pluck('id'))->toContain($pro->getKey());
    });
});

describe('deleting a crate', function () {
    it('takes its nested crates and items with it', function () {
        $user = proUser();
        $crate = Crate::factory()->for($user)->create();
        $nested = Crate::factory()->nestedIn($crate)->create();
        $item = CrateItem::factory()->for($crate)->create();
        $nestedItem = CrateItem::factory()->for($nested)->forTierlist()->create();

        (new DestroyCrate)->handle($crate);

        assertModelMissing($crate);
        assertModelMissing($nested);
        assertModelMissing($item);
        assertModelMissing($nestedItem);
    });

    it('leaves the rankings, tier lists and reviews inside alone', function () {
        $crate = Crate::factory()->create();
        $ranking = CrateItem::factory()->for($crate)->forRanking()->create()->entity;
        $tierlist = CrateItem::factory()->for($crate)->forTierlist()->create()->entity;
        $review = CrateItem::factory()->for($crate)->forReview()->create()->entity;

        (new DestroyCrate)->handle($crate);

        assertModelExists($ranking);
        assertModelExists($tierlist);
        assertModelExists($review);
        expect(Ranking::query()->find($ranking->getKey())->trashed())->toBeFalse();
    });

    it('closes the gap it leaves in its list', function () {
        $user = User::factory()->createOne();
        [$first, $second, $third] = Crate::factory()->count(3)->for($user)->create();

        (new DestroyCrate)->handle($second);

        expect($first->fresh()->position)->toBe(0)
            ->and($third->fresh()->position)->toBe(1);
    });
});

/**
 * @return array{name: string, color: string, crate_icon_id: ?int}
 */
function crateSettings(array $overrides = []): array
{
    return array_merge([
        'name' => 'Sacred Hearts Club',
        'color' => '#1E90FF',
        'crate_icon_id' => null,
    ], $overrides);
}
