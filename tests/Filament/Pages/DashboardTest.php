<?php

use App\Filament\Pages\Dashboard;
use Livewire\Livewire;

describe('admin dashboard', function () {
    test('groups the widgets into sections', function () {
        Livewire::actingAs(kyle())
            ->test(Dashboard::class)
            ->assertOk()
            ->assertSeeInOrder(['Pro', 'Users', 'Content']);
    });
});
