<?php

use App\Filament\Resources\CrateIcons\Pages\ListCrateIcons;
use App\Models\Crate;
use App\Models\CrateIcon;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

describe('access', function () {
    it('is closed to non-developers', function () {
        actingAs(User::factory()->createOne())
            ->get('/admin/crate-icons')
            ->assertForbidden();
    });

    it('is closed to guests', function () {
        get('/admin/crate-icons')->assertRedirect();
    });
});

describe('the icon table', function () {
    beforeEach(function () {
        CrateIcon::query()->delete();

        actingAs(kyle());
    });

    it('lists the icons in picker order', function () {
        CrateIcon::factory()->count(3)->create();

        Livewire::test(ListCrateIcons::class)
            ->assertCanSeeTableRecords(CrateIcon::query()->ordered()->get(), inOrder: true);
    });

    it('draws each icon', function () {
        $icon = CrateIcon::factory()->create();

        Livewire::test(ListCrateIcons::class)
            ->assertSeeHtml('<i class="fa-solid '.$icon->icon.' fa-lg"></i>');
    });

    it('finds an icon by its class', function () {
        $match = CrateIcon::factory()->create(['icon' => 'fa-record-vinyl']);
        $other = CrateIcon::factory()->create(['icon' => 'fa-music']);

        Livewire::test(ListCrateIcons::class)
            ->searchTable('fa-record-vinyl')
            ->assertCanSeeTableRecords([$match])
            ->assertCanNotSeeTableRecords([$other]);
    });

    it('counts the crates using each icon', function () {
        $icon = CrateIcon::factory()->create();
        Crate::factory()->count(2)->create(['crate_icon_id' => $icon->getKey()]);

        Livewire::test(ListCrateIcons::class)
            ->assertTableColumnStateSet('crates_count', 2, record: $icon);
    });

    it('reorders the picker by dragging', function () {
        CrateIcon::factory()->count(3)->create();

        $reversed = CrateIcon::query()->ordered()->pluck('id')->reverse()->map(fn (int $id) => (string) $id)->values()->all();

        Livewire::test(ListCrateIcons::class)
            ->call('reorderTable', $reversed);

        expect(CrateIcon::query()->ordered()->pluck('id')->map(fn (int $id) => (string) $id)->all())->toBe($reversed);
    });
});
