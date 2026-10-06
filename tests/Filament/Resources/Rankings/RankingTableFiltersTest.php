<?php

use App\Filament\Resources\Rankings\Pages\ListRankings;
use App\Models\Ranking;
use Livewire\Livewire;

describe('in process filter', function () {
    test('hides in process rankings by default', function () {
        $completed = publicCompletedRanking();
        $inProcess = Ranking::factory()->createOne();

        Livewire::actingAs(kyle())
            ->test(ListRankings::class)
            ->assertCanSeeTableRecords([$completed])
            ->assertCanNotSeeTableRecords([$inProcess]);
    });

    test('shows in process rankings once toggled off', function () {
        $completed = publicCompletedRanking();
        $inProcess = Ranking::factory()->createOne();

        Livewire::actingAs(kyle())
            ->test(ListRankings::class)
            ->filterTable('hide_incomplete', false)
            ->assertCanSeeTableRecords([$completed, $inProcess]);
    });
});
