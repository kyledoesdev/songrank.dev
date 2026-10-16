<?php

use App\Filament\Widgets\CompletionTimeWidget;
use App\Models\Ranking;
use App\Models\Review;
use App\Models\Tierlist;
use Livewire\Livewire;

describe('completion time widget', function () {
    test('renders without error', function () {
        Livewire::actingAs(kyle())
            ->test(CompletionTimeWidget::class)
            ->assertOk();
    });

    test('buckets completed rankings by time to finish', function () {
        foreach ([30, 3 * 60, 8 * 60, 18 * 60, 48 * 60] as $minutes) {
            Ranking::factory()->create([
                'completed_at' => now(),
                'created_at' => now()->subMinutes($minutes),
            ]);
        }

        [$rankings] = widgetChartData(Livewire::actingAs(kyle())->test(CompletionTimeWidget::class));

        expect($rankings)->toBe([1, 1, 1, 1, 1]);
    });

    test('excludes incomplete rankings', function () {
        Ranking::factory()->create([
            'completed_at' => null,
        ]);

        [$rankings] = widgetChartData(Livewire::actingAs(kyle())->test(CompletionTimeWidget::class));

        expect($rankings)->toBe([0, 0, 0, 0, 0]);
    });

    test('filters by date range', function () {
        Ranking::factory()->create([
            'completed_at' => now(),
            'created_at' => now()->subMinutes(30),
        ]);

        Ranking::factory()->create([
            'completed_at' => now()->subYear(),
            'created_at' => now()->subYear()->subHours(3),
        ]);

        $widget = Livewire::actingAs(kyle())
            ->test(CompletionTimeWidget::class)
            ->set('filters.filter', 'month');

        [$rankings] = widgetChartData($widget);

        expect($rankings)->toBe([1, 0, 0, 0, 0]);
    });

    test('charts rankings, tier lists and reviews side by side', function () {
        Ranking::factory()->create(['created_at' => now()->subMinutes(30), 'completed_at' => now()]);
        Tierlist::factory()->create(['created_at' => now()->subHours(3), 'is_complete' => true, 'completed_at' => now()]);
        Review::factory()->create(['created_at' => now()->subHours(30), 'is_published' => true, 'published_at' => now()]);

        [$rankings, $tierlists, $reviews] = widgetChartData(Livewire::actingAs(kyle())->test(CompletionTimeWidget::class));

        expect($rankings)->toBe([1, 0, 0, 0, 0])
            ->and($tierlists)->toBe([0, 1, 0, 0, 0])
            ->and($reviews)->toBe([0, 0, 0, 0, 1]);
    });

    test('ignores drafts and unfinished tier lists', function () {
        Tierlist::factory()->create(['completed_at' => null]);
        Review::factory()->create(['published_at' => null]);

        $datasets = widgetChartData(Livewire::actingAs(kyle())->test(CompletionTimeWidget::class));

        expect(collect($datasets)->flatten()->sum())->toBe(0);
    });
});
