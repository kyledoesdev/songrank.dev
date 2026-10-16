<?php

use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\User;
use Livewire\Livewire;

describe('editing a user', function () {
    it('saves the account details', function () {
        $user = User::factory()->createOne();

        Livewire::actingAs(kyle())
            ->test(EditUser::class, ['record' => $user->getKey()])
            ->fillForm(['name' => 'Kevin Parker', 'timezone' => 'Australia/Perth'])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($user->fresh())
            ->name->toBe('Kevin Parker')
            ->timezone->toBe('Australia/Perth');
    });
});
