<?php

use App\Filament\Resources\CrateIcons\Pages\CreateCrateIcon;
use App\Filament\Resources\CrateIcons\Pages\EditCrateIcon;
use App\Models\Crate;
use App\Models\CrateIcon;
use Filament\Actions\DeleteAction;
use Livewire\Livewire;

describe('creating an icon', function () {
    it('adds it to the end of the picker', function () {
        Livewire::actingAs(kyle())
            ->test(CreateCrateIcon::class)
            ->fillForm(['name' => 'Drum', 'icon' => 'fa-drum'])
            ->call('create')
            ->assertHasNoFormErrors();

        $icon = CrateIcon::query()->where('icon', 'fa-drum')->firstOrFail();

        expect($icon)
            ->is_active->toBeTrue()
            ->is_pro->toBeFalse()
            ->and(CrateIcon::query()->ordered()->get()->last()->is($icon))->toBeTrue();
    });

    it('requires a name and an icon', function () {
        Livewire::actingAs(kyle())
            ->test(CreateCrateIcon::class)
            ->fillForm(['name' => '', 'icon' => ''])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required', 'icon' => 'required']);
    });

    it('refuses an icon already in the picker', function () {
        $existing = CrateIcon::factory()->create();

        Livewire::actingAs(kyle())
            ->test(CreateCrateIcon::class)
            ->fillForm(['name' => 'Another One', 'icon' => $existing->icon])
            ->call('create')
            ->assertHasFormErrors(['icon' => 'unique']);
    });

    it('takes a single icon class and nothing else', function (string $icon) {
        Livewire::actingAs(kyle())
            ->test(CreateCrateIcon::class)
            ->fillForm(['name' => 'Drum', 'icon' => $icon])
            ->call('create')
            ->assertHasFormErrors(['icon' => 'regex']);
    })->with([
        'with the style class' => 'fa-solid fa-drum',
        'without the prefix' => 'drum',
        'with markup' => 'fa-drum"><script>',
    ]);

    it('previews the icon as it is typed', function () {
        Livewire::actingAs(kyle())
            ->test(CreateCrateIcon::class)
            ->fillForm(['icon' => 'fa-drum'])
            ->assertSeeHtml('<i class="fa-solid fa-drum"></i>');
    });
});

describe('editing an icon', function () {
    it('keeps its own class without tripping the unique rule', function () {
        $icon = CrateIcon::factory()->create();

        Livewire::actingAs(kyle())
            ->test(EditCrateIcon::class, ['record' => $icon->getKey()])
            ->fillForm(['name' => 'Royalty', 'is_pro' => true])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($icon->fresh())
            ->name->toBe('Royalty')
            ->is_pro->toBeTrue();
    });

    it('puts crates back on the default folder once their icon is deleted', function () {
        $icon = CrateIcon::factory()->create();
        $crate = Crate::factory()->create(['crate_icon_id' => $icon->getKey()]);

        Livewire::actingAs(kyle())
            ->test(EditCrateIcon::class, ['record' => $icon->getKey()])
            ->callAction(DeleteAction::class);

        expect($crate->fresh()->iconClass())->toBe(Crate::DEFAULT_ICON);
    });
});
