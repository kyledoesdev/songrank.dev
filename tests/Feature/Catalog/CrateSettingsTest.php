<?php

use App\Actions\Catalog\DestroyCrate;
use App\Actions\Catalog\UpdateCrate;
use App\Livewire\Catalog\CrateSettings;
use App\Models\Crate;
use App\Models\CrateIcon;
use App\Models\CrateItem;
use App\Models\Ranking;
use App\Models\User;
use Livewire\Livewire;

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

describe('the settings popover', function () {
    it('opens on what the crate has now', function () {
        $crate = Crate::factory()->create(['name' => 'Torches', 'color' => '#1E90FF']);

        Livewire::actingAs($crate->user)
            ->test(CrateSettings::class, ['crate' => $crate])
            ->call('openSettings')
            ->assertSet('open', true)
            ->assertSet('form.name', 'Torches')
            ->assertSet('form.color', '#1E90FF');
    });

    it('saves and tells the page', function () {
        $crate = Crate::factory()->create();
        $icon = CrateIcon::query()->where('icon', 'fa-record-vinyl')->firstOrFail();

        Livewire::actingAs($crate->user)
            ->test(CrateSettings::class, ['crate' => $crate])
            ->call('openSettings')
            ->set('form.name', 'Sacred Hearts Club')
            ->set('form.color', '#1E90FF')
            ->set('form.crate_icon_id', $icon->getKey())
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('open', false)
            ->assertDispatched('crates-updated');

        expect($crate->fresh())
            ->name->toBe('Sacred Hearts Club')
            ->color->toBe('#1E90FF')
            ->crate_icon_id->toBe($icon->getKey());
    });

    it('takes any colour the picker gives', function (string $color) {
        $crate = Crate::factory()->create();

        Livewire::actingAs($crate->user)
            ->test(CrateSettings::class, ['crate' => $crate])
            ->call('openSettings')
            ->set('form.color', $color)
            ->call('save')
            ->assertHasNoErrors();

        expect($crate->fresh()->color)->toBe($color);
    })->with([
        'lower case' => '#a855f7',
        'upper case' => '#A855F7',
    ]);

    it('takes nothing but a hex code for a colour', function (string $color) {
        $crate = Crate::factory()->create();

        Livewire::actingAs($crate->user)
            ->test(CrateSettings::class, ['crate' => $crate])
            ->call('openSettings')
            ->set('form.color', $color)
            ->call('save')
            ->assertHasErrors(['form.color' => 'regex']);
    })->with([
        'a colour name' => 'purple',
        'short hex' => '#fff',
        'anything smuggled after it' => '#ffffff; background: url(x)',
    ]);

    it('needs a name', function () {
        $crate = Crate::factory()->create();

        Livewire::actingAs($crate->user)
            ->test(CrateSettings::class, ['crate' => $crate])
            ->call('openSettings')
            ->set('form.name', '')
            ->call('save')
            ->assertHasErrors(['form.name' => 'required']);
    });

    it('locks Pro icons on a free account', function () {
        $crate = Crate::factory()->create();
        $icon = CrateIcon::factory()->pro()->create();

        $component = Livewire::actingAs($crate->user)
            ->test(CrateSettings::class, ['crate' => $crate])
            ->call('openSettings')
            ->assertDontSee("\$wire.form.crate_icon_id = {$icon->getKey()}", escape: false);

        expect($component->instance()->iconIsLocked($icon))->toBeTrue();
    });

    it('unlocks them on a Pro account', function () {
        $crate = Crate::factory()->for(proUser())->create();
        $icon = CrateIcon::factory()->pro()->create();

        $component = Livewire::actingAs($crate->user)
            ->test(CrateSettings::class, ['crate' => $crate])
            ->call('openSettings')
            ->assertSee("\$wire.form.crate_icon_id = {$icon->getKey()}", escape: false);

        expect($component->instance()->iconIsLocked($icon))->toBeFalse();
    });

    it('turns a Pro icon away from a free account', function () {
        $crate = Crate::factory()->create();
        $icon = CrateIcon::factory()->pro()->create();

        $component = Livewire::actingAs($crate->user)
            ->test(CrateSettings::class, ['crate' => $crate])
            ->call('openSettings')
            ->set('form.crate_icon_id', $icon->getKey())
            ->call('save')
            ->assertNotDispatched('crates-updated');

        expect(alertsFrom($component)->pluck('icon')->all())->toBe(['error'])
            ->and($crate->fresh()->crate_icon_id)->toBeNull();
    });

    it('belongs to the crate\'s owner alone', function () {
        Livewire::actingAs(User::factory()->createOne())
            ->test(CrateSettings::class, ['crate' => Crate::factory()->create()])
            ->assertForbidden();
    });

});

describe('deleting from the popover', function () {
    it('asks first', function () {
        $crate = Crate::factory()->create();

        $component = Livewire::actingAs($crate->user)
            ->test(CrateSettings::class, ['crate' => $crate])
            ->call('confirmDestroy');

        expect(alertsFrom($component, 'confirm')->pluck('action')->all())->toBe(['destroy']);
        assertModelExists($crate);
    });

    it('tells the page when a tile goes', function () {
        $crate = Crate::factory()->create();

        Livewire::actingAs($crate->user)
            ->test(CrateSettings::class, ['crate' => $crate])
            ->call('destroy')
            ->assertDispatched('crates-updated');

        assertModelMissing($crate);
    });

    it('leaves a deleted crate\'s page for the one above it', function () {
        $user = proUser();
        $parent = Crate::factory()->for($user)->create();
        $crate = Crate::factory()->nestedIn($parent)->create();

        Livewire::actingAs($user)
            ->test(CrateSettings::class, ['crate' => $crate, 'redirectOnDelete' => true])
            ->call('destroy')
            ->assertRedirect(route('catalog.crate', ['id' => $parent->getKey()]));
    });

    it('leaves a deleted top-level crate\'s page for the catalog', function () {
        $crate = Crate::factory()->create();

        Livewire::actingAs($crate->user)
            ->test(CrateSettings::class, ['crate' => $crate, 'redirectOnDelete' => true])
            ->call('destroy')
            ->assertRedirect(route('catalog'));
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
