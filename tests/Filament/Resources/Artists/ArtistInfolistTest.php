<?php

use App\Filament\Resources\Artists\Pages\ViewArtist;
use App\Models\Artist;
use Livewire\Livewire;

describe('artist infolist', function () {
    test('links an artist back to spotify', function () {
        $artist = Artist::factory()->createOne();

        Livewire::actingAs(kyle())
            ->test(ViewArtist::class, ['record' => $artist->getKey()])
            ->assertOk()
            ->assertSee($artist->spotifyUrl(), escape: false);
    });
});
