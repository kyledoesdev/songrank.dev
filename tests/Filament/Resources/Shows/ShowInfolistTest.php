<?php

use App\Filament\Resources\Shows\Pages\ViewShow;
use App\Models\Show;
use Livewire\Livewire;

describe('show infolist', function () {
    test('links a show back to spotify', function () {
        $show = Show::factory()->createOne();

        Livewire::actingAs(kyle())
            ->test(ViewShow::class, ['record' => $show->getKey()])
            ->assertOk()
            ->assertSee($show->spotifyUrl(), escape: false);
    });
});
