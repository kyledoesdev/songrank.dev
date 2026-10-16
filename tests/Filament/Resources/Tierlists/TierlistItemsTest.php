<?php

use App\Filament\Resources\Tierlists\Pages\ViewTierlist;
use App\Filament\Resources\Tierlists\RelationManagers\ItemsRelationManager;
use Livewire\Livewire;

describe('tier list items', function () {
    test('lists the entries on that board', function () {
        $tierlist = publicCompletedTierlist();
        $other = publicCompletedTierlist();

        Livewire::actingAs(kyle())
            ->test(ItemsRelationManager::class, ['ownerRecord' => $tierlist, 'pageClass' => ViewTierlist::class])
            ->assertOk()
            ->assertCanSeeTableRecords($tierlist->items)
            ->assertCanNotSeeTableRecords($other->items);
    });
});
