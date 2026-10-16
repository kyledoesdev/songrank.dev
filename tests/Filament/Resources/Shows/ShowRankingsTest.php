<?php

use App\Filament\Resources\Shows\Pages\ViewShow;
use App\Filament\Resources\Shows\RelationManagers\RankingsRelationManager;
use App\Models\Show;
use Livewire\Livewire;

describe('show rankings', function () {
    test('lists the rankings built from the show', function () {
        $show = Show::factory()->createOne();
        $ranking = publicCompletedRanking($show);
        $other = publicCompletedRanking();

        Livewire::actingAs(kyle())
            ->test(RankingsRelationManager::class, ['ownerRecord' => $show, 'pageClass' => ViewShow::class])
            ->assertOk()
            ->assertCanSeeTableRecords([$ranking])
            ->assertCanNotSeeTableRecords([$other]);
    });
});
