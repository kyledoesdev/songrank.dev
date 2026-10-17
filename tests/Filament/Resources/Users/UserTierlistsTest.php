<?php

use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Resources\Users\RelationManagers\TierlistsRelationManager;
use App\Models\User;
use Livewire\Livewire;

describe('user tier lists', function () {
    test('lists only that user\'s tier lists', function () {
        $user = User::factory()->createOne();
        $own = publicCompletedTierlist(['user_id' => $user->getKey()]);
        $other = publicCompletedTierlist();

        Livewire::actingAs(kyle())
            ->test(TierlistsRelationManager::class, ['ownerRecord' => $user, 'pageClass' => ViewUser::class])
            ->assertOk()
            ->assertCanSeeTableRecords([$own])
            ->assertCanNotSeeTableRecords([$other]);
    });
});
