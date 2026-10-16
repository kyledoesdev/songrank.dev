<?php

use App\Filament\Resources\Rankings\Pages\ViewRanking;
use App\Filament\Resources\Rankings\RelationManagers\SongsRelationManager;
use Livewire\Livewire;

describe('ranking songs', function () {
    test('lists the songs of that ranking', function () {
        $ranking = publicCompletedRanking();
        $other = publicCompletedRanking();

        Livewire::actingAs(kyle())
            ->test(SongsRelationManager::class, ['ownerRecord' => $ranking, 'pageClass' => ViewRanking::class])
            ->assertOk()
            ->assertCanSeeTableRecords($ranking->songs()->orderBy('rank')->limit(5)->get())
            ->assertCanNotSeeTableRecords($other->songs()->limit(5)->get());
    });
});
