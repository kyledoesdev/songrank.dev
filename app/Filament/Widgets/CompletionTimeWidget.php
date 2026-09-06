<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\HasDateFilters;
use App\Models\Ranking;
use Filament\Widgets\ChartWidget;

class CompletionTimeWidget extends ChartWidget
{
    use HasDateFilters;

    protected ?string $heading = 'Time to Complete';

    protected ?string $description = 'Distribution of how long rankings take to finish';

    protected static ?int $sort = 7;

    protected function getData(): array
    {
        $buckets = $this->getBuckets();

        return [
            'datasets' => [
                [
                    'label' => 'Rankings',
                    'data' => $buckets,
                    'backgroundColor' => [
                        '#86efac',
                        '#7dd3fc',
                        '#c084fc',
                        '#fbbf24',
                        '#f87171',
                    ],
                    'borderColor' => [
                        '#4ade80',
                        '#38bdf8',
                        '#a855f7',
                        '#f59e0b',
                        '#ef4444',
                    ],
                    'borderWidth' => 1,
                ],
            ],
            'labels' => ['≤ 1 hour', '1–5 hours', '5–12 hours', '12–24 hours', '24+ hours'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
        ];
    }

    /**
     * @return array<int, int>
     */
    private function getBuckets(): array
    {
        $range = $this->getTrendConfig();

        $rankings = Ranking::query()
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$range['start'], $range['end']])
            ->select(['created_at', 'completed_at'])
            ->get();

        $counts = [0, 0, 0, 0, 0];

        foreach ($rankings as $ranking) {
            $hours = $ranking->created_at->diffInMinutes($ranking->completed_at) / 60;

            match (true) {
                $hours <= 1 => $counts[0]++,
                $hours <= 5 => $counts[1]++,
                $hours <= 12 => $counts[2]++,
                $hours <= 24 => $counts[3]++,
                default => $counts[4]++,
            };
        }

        return $counts;
    }
}
