<?php

use App\Filament\Pages\Dashboard;
use Livewire\Livewire;

describe('admin dashboard', function () {
    test('groups the widgets into sections', function () {
        Livewire::actingAs(kyle())
            ->test(Dashboard::class)
            ->assertOk()
            ->assertSchemaComponentExists('pro', 'content')
            ->assertSchemaComponentExists('users', 'content')
            ->assertSchemaComponentExists('content', 'content');
    });
});
