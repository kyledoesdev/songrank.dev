<?php

use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Resources\Users\RelationManagers\PreferencesRelationManager;
use App\Models\User;
use Livewire\Livewire;

describe('user preferences', function () {
    test('lists only that user\'s preferences', function () {
        $user = User::factory()->createOne();
        $own = $user->preferences;
        $other = User::factory()->createOne()->preferences;

        Livewire::actingAs(kyle())
            ->test(PreferencesRelationManager::class, ['ownerRecord' => $user, 'pageClass' => ViewUser::class])
            ->assertOk()
            ->assertCanSeeTableRecords([$own])
            ->assertCanNotSeeTableRecords([$other]);
    });
});
