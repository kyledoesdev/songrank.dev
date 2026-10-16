<?php

use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Resources\Users\RelationManagers\ProLicensesRelationManager;
use App\Models\ProLicense;
use App\Models\User;
use Livewire\Livewire;

describe('user Pro licences', function () {
    test('lists only that user\'s Pro licences', function () {
        $user = User::factory()->createOne();
        $own = ProLicense::factory()->active()->for($user)->create();
        $other = ProLicense::factory()->active()->create();

        Livewire::actingAs(kyle())
            ->test(ProLicensesRelationManager::class, ['ownerRecord' => $user, 'pageClass' => ViewUser::class])
            ->assertOk()
            ->assertCanSeeTableRecords([$own])
            ->assertCanNotSeeTableRecords([$other]);
    });
});
