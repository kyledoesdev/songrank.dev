<?php

use App\Filament\Widgets\CommentsCreatedWidget;
use App\Filament\Widgets\NewUsersWidget;
use App\Filament\Widgets\RankingsCreatedWidget;
use App\Filament\Widgets\TierlistsCreatedWidget;
use App\Models\User;
use Livewire\Livewire;

describe('trend chart widgets', function () {
    test('renders without error', function (string $widget) {
        $user = User::factory()->createOne();

        Livewire::actingAs($user)
            ->test($widget)
            ->assertOk();
    })->with([
        NewUsersWidget::class,
        RankingsCreatedWidget::class,
        CommentsCreatedWidget::class,
        TierlistsCreatedWidget::class,
    ]);

    test('renders with each quick filter applied', function (string $filter) {
        $user = User::factory()->createOne();

        Livewire::actingAs($user)
            ->test(NewUsersWidget::class)
            ->set('filters.filter', $filter)
            ->assertOk();
    })->with(['all', 'day', 'week', 'month', 'year']);

    test('builds a dataset for new and deleted users', function () {
        $actor = User::factory()->createOne();
        User::factory()->count(2)->create();
        User::factory()->count(1)->create()->each->delete();

        Livewire::actingAs($actor)
            ->test(NewUsersWidget::class)
            ->assertOk()
            ->assertSee('New Users');
    });
});
