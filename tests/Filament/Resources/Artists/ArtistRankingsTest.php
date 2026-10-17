<?php

use App\Filament\Resources\Artists\Pages\ViewArtist;
use App\Filament\Resources\Artists\RelationManagers\RankingsRelationManager;
use App\Models\Artist;
use Livewire\Livewire;

describe('artist rankings', function () {
    test('lists the rankings of that artist', function () {
        $artist = Artist::factory()->create();
        $ranking = publicCompletedRanking($artist);
        $other = publicCompletedRanking();

        Livewire::actingAs(kyle())
            ->test(RankingsRelationManager::class, ['ownerRecord' => $artist, 'pageClass' => ViewArtist::class])
            ->assertOk()
            ->assertCanSeeTableRecords([$ranking])
            ->assertCanNotSeeTableRecords([$other]);
    });
});
