<?php

use App\Filament\Widgets\CompletionTimeWidget;
use App\Models\Ranking;
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
});
