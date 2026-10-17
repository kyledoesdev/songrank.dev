<?php

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(fn () => actingAs(kyle()));

describe('who has Pro', function () {
    it('shows it against each user', function () {
        $pro = proUser();
        $free = User::factory()->createOne();

        Livewire::test(ListUsers::class)
            ->assertTableColumnStateSet('is_pro', true, $pro)
            ->assertTableColumnStateSet('is_pro', false, $free);
    });

    it('filters down to Pro users', function () {
        $pro = proUser();
        $free = User::factory()->createOne();

        Livewire::test(ListUsers::class)
            ->filterTable('is_pro', true)
            ->assertCanSeeTableRecords([$pro])
            ->assertCanNotSeeTableRecords([$free]);
    });
});
