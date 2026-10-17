<?php

use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Resources\Users\RelationManagers\RankingsRelationManager;
use App\Models\User;
use Livewire\Livewire;

describe('user rankings', function () {
    test('lists only that user\'s rankings', function () {
        $user = User::factory()->createOne();
        $own = publicCompletedRanking(attributes: ['user_id' => $user->getKey()]);
        $other = publicCompletedRanking();

        Livewire::actingAs(kyle())
            ->test(RankingsRelationManager::class, ['ownerRecord' => $user, 'pageClass' => ViewUser::class])
            ->assertOk()
            ->assertCanSeeTableRecords([$own])
            ->assertCanNotSeeTableRecords([$other]);
    });
});
