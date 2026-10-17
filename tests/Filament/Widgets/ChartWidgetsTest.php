<?php

use App\Filament\Widgets\CommentsCreatedWidget;
use App\Filament\Widgets\NewUsersWidget;
use App\Filament\Widgets\RankingsCreatedWidget;
use App\Filament\Widgets\ReviewsCreatedWidget;
use App\Filament\Widgets\TierlistsCreatedWidget;
use App\Models\User;
use Livewire\Livewire;

describe('trend chart widgets', function () {
    test('renders without error', function (string $widget) {
        Livewire::actingAs(kyle())
            ->test($widget)
            ->assertOk();
    })->with([
        NewUsersWidget::class,
        RankingsCreatedWidget::class,
        CommentsCreatedWidget::class,
        TierlistsCreatedWidget::class,
        ReviewsCreatedWidget::class,
    ]);

    test('renders with each quick filter applied', function (string $filter) {
        Livewire::actingAs(kyle())
            ->test(NewUsersWidget::class)
            ->set('filters.filter', $filter)
            ->assertOk();
    })->with(['all', 'day', 'week', 'month', 'year']);

    test('builds a dataset for new and deleted users', function () {
        $admin = kyle();
        User::factory()->count(2)->create();
        User::factory()->count(1)->create()->each->delete();

        [$new, $deleted] = widgetChartData(Livewire::actingAs($admin)->test(NewUsersWidget::class));

        expect(array_sum($new))->toBe(3)
            ->and(array_sum($deleted))->toBe(1);
    });
});
