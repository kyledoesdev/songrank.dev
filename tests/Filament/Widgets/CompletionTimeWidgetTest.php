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
        Ranking::factory()->create([
            'completed_at' => now(),
            'created_at' => now()->subMinutes(30),
        ]);

        Ranking::factory()->create([
            'completed_at' => now(),
            'created_at' => now()->subHours(3),
        ]);

        Ranking::factory()->create([
            'completed_at' => now(),
            'created_at' => now()->subHours(8),
        ]);

        Ranking::factory()->create([
            'completed_at' => now(),
            'created_at' => now()->subHours(18),
        ]);

        Ranking::factory()->create([
            'completed_at' => now(),
            'created_at' => now()->subHours(48),
        ]);

        Livewire::actingAs(kyle())
            ->test(CompletionTimeWidget::class)
            ->assertOk()
            ->assertSee('Time to Complete');
    });

    test('excludes incomplete rankings', function () {
        Ranking::factory()->create([
            'completed_at' => null,
        ]);

        Livewire::actingAs(kyle())
            ->test(CompletionTimeWidget::class)
            ->assertOk();
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

        Livewire::actingAs(kyle())
            ->test(CompletionTimeWidget::class)
            ->set('filters.filter', 'month')
            ->assertOk();
    });

    test('charts rankings, tier lists and reviews side by side', function () {
        Ranking::factory()->create(['created_at' => now()->subMinutes(30), 'completed_at' => now()]);
        Tierlist::factory()->create(['created_at' => now()->subHours(3), 'is_complete' => true, 'completed_at' => now()]);
        Review::factory()->create(['created_at' => now()->subHours(30), 'is_published' => true, 'published_at' => now()]);

        $datasets = collect(completionChartData(Livewire::actingAs(kyle())
            ->test(CompletionTimeWidget::class)
            ->instance())['datasets'])
            ->mapWithKeys(fn (array $dataset) => [$dataset['label'] => $dataset['data']]);

        expect($datasets->all())->toBe([
            'Rankings' => [1, 0, 0, 0, 0],
            'Tier Lists' => [0, 1, 0, 0, 0],
            'Reviews' => [0, 0, 0, 0, 1],
        ]);
    });

    test('ignores drafts and unfinished tier lists', function () {
        Tierlist::factory()->create(['completed_at' => null]);
        Review::factory()->create(['published_at' => null]);

        $datasets = completionChartData(Livewire::actingAs(kyle())
            ->test(CompletionTimeWidget::class)
            ->instance())['datasets'];

        expect(collect($datasets)->pluck('data')->flatten()->sum())->toBe(0);
    });
});

/**
 * @return array<string, mixed>
 */
function completionChartData(CompletionTimeWidget $widget): array
{
    return (fn (): array => $this->getData())->call($widget);
}
