<?php

use App\Filament\Resources\Playlists\Pages\ViewPlaylist;
use App\Models\Playlist;
use Livewire\Livewire;

describe('playlist infolist', function () {
    test('links a playlist back to spotify', function () {
        $playlist = Playlist::factory()->createOne();

        Livewire::actingAs(kyle())
            ->test(ViewPlaylist::class, ['record' => $playlist->getKey()])
            ->assertOk()
            ->assertSee($playlist->spotifyUrl(), escape: false);
    });
});
