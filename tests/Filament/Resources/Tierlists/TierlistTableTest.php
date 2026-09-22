<?php

use App\Enums\TierlistType;
use App\Filament\Resources\Tierlists\Pages\ListTierlists;
use App\Models\Tierlist;
use Livewire\Livewire;

describe('tierlist table', function () {
    test('renders the table', function () {
        $tierlist = Tierlist::factory()->complete()->createOne();

        Livewire::actingAs(kyle())
            ->test(ListTierlists::class)
            ->assertCanSeeTableRecords([$tierlist])
            ->assertOk();
    });

    test('hides incomplete tier lists by default', function () {
        $completed = Tierlist::factory()->complete()->createOne();
        $incomplete = Tierlist::factory()->createOne();

        Livewire::actingAs(kyle())
            ->test(ListTierlists::class)
            ->assertCanSeeTableRecords([$completed])
            ->assertCanNotSeeTableRecords([$incomplete]);
    });

    test('shows incomplete tier lists when filter is toggled off', function () {
        $completed = Tierlist::factory()->complete()->createOne();
        $incomplete = Tierlist::factory()->createOne();

        Livewire::actingAs(kyle())
            ->test(ListTierlists::class)
            ->filterTable('hide_incomplete', false)
            ->assertCanSeeTableRecords([$completed, $incomplete]);
    });

    test('filters by type', function () {
        $artist = Tierlist::factory()->complete()->createOne(['type' => TierlistType::ARTIST]);
        $album = Tierlist::factory()->complete()->createOne(['type' => TierlistType::ALBUM]);

        Livewire::actingAs(kyle())
            ->test(ListTierlists::class)
            ->filterTable('type', TierlistType::ARTIST->value)
            ->assertCanSeeTableRecords([$artist])
            ->assertCanNotSeeTableRecords([$album]);
    });
});
