<?php

use App\Filament\Resources\Rankings\Pages\ViewRanking;
use App\Models\Ranking;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

describe('source sections', function () {
    test('only shows artist details for an artist ranking', function () {
        $ranking = Ranking::factory()->createOne();

        viewRanking($ranking)
            ->assertSchemaComponentVisible('artist')
            ->assertSchemaComponentHidden('playlist')
            ->assertSchemaComponentHidden('show');
    });

    test('only shows playlist details for a playlist ranking', function () {
        $ranking = Ranking::factory()->playlist()->createOne();

        viewRanking($ranking)
            ->assertSchemaComponentVisible('playlist')
            ->assertSchemaComponentHidden('artist')
            ->assertSchemaComponentHidden('show');
    });

    test('only shows show details for a show ranking', function () {
        $ranking = Ranking::factory()->show()->createOne();

        viewRanking($ranking)
            ->assertSchemaComponentVisible('show')
            ->assertSchemaComponentHidden('artist')
            ->assertSchemaComponentHidden('playlist');
    });
});

function viewRanking(Ranking $ranking): Testable
{
    return Livewire::actingAs(kyle())
        ->test(ViewRanking::class, ['record' => $ranking->getKey()])
        ->assertOk();
}
