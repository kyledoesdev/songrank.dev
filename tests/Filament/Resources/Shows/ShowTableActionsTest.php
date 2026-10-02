<?php

use App\Filament\Resources\Shows\Pages\ManageShows;
use App\Models\Show;
use Livewire\Livewire;

describe('show table actions', function () {
    test('a show can be viewed but never deleted, since rankings are built from it', function () {
        $show = Show::factory()->createOne();

        Livewire::actingAs(kyle())
            ->test(ManageShows::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$show])
            ->assertTableActionExists('view')
            ->assertTableActionDoesNotExist('delete');
    });
});
