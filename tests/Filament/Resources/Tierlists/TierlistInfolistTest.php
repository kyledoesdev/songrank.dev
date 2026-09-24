<?php

use App\Filament\Resources\Tierlists\Pages\ViewTierlist;
use App\Models\Tierlist;
use Livewire\Livewire;

describe('tierlist infolist', function () {
    test('renders the view page for a completed tier list', function () {
        $tierlist = Tierlist::factory()->complete()->createOne();

        Livewire::actingAs(kyle())
            ->test(ViewTierlist::class, ['record' => $tierlist->getKey()])
            ->assertOk()
            ->assertSee('Tier List Details');
    });

    test('shows source section when a source exists', function () {
        $tierlist = Tierlist::factory()->complete()->fromArtist()->createOne();

        Livewire::actingAs(kyle())
            ->test(ViewTierlist::class, ['record' => $tierlist->getKey()])
            ->assertOk()
            ->assertSee('Source');
    });

    test('hides source section when no source exists', function () {
        $tierlist = Tierlist::factory()->complete()->createOne([
            'source_type' => null,
            'source_id' => null,
        ]);

        Livewire::actingAs(kyle())
            ->test(ViewTierlist::class, ['record' => $tierlist->getKey()])
            ->assertOk()
            ->assertDontSee('Source');
    });
});
