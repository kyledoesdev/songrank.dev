<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\HasDateFilters;
use App\Models\Ranking;
use App\Models\Review;
use App\Models\Tierlist;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;

class CompletionTimeWidget extends ChartWidget
{
    use HasDateFilters;

    protected ?string $heading = 'Time to Complete';

    protected ?string $description = 'How long rankings, tier lists and reviews take to finish';

    protected function getData(): array
    {
        return [
            'datasets' => [
                $this->dataset('Rankings', $this->getBuckets(Ranking::class, 'completed_at'), '#c084fc', '#a855f7'),
                $this->dataset('Tier Lists', $this->getBuckets(Tierlist::class, 'completed_at'), '#86efac', '#4ade80'),
                $this->dataset('Reviews', $this->getBuckets(Review::class, 'published_at'), '#93c5fd', '#60a5fa'),
            ],
            'labels' => ['≤ 1 hour', '1–5 hours', '5–12 hours', '12–24 hours', '24+ hours'],
        ];
    }

    /**
     * @param  array<int, int>  $buckets
     * @return array<string, mixed>
     */
    private function dataset(string $label, array $buckets, string $background, string $border): array
    {
        return [
            'label' => $label,
            'data' => $buckets,
            'backgroundColor' => $background,
            'borderColor' => $border,
            'borderWidth' => 1,
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
                    'position' => 'bottom',
                ],
            ],
        ];
    }

    /**
     * @param  class-string<Model>  $model
     * @return array<int, int>
     */
    private function getBuckets(string $model, string $finishedColumn): array
    {
        $range = $this->getTrendConfig();

        $records = $model::query()
            ->whereNotNull($finishedColumn)
            ->whereBetween($finishedColumn, [$range['start'], $range['end']])
            ->toBase()
            ->get(['created_at', $finishedColumn]);

        $counts = [0, 0, 0, 0, 0];

        foreach ($records as $record) {
            $hours = Carbon::parse($record->created_at)->diffInMinutes(Carbon::parse($record->{$finishedColumn})) / 60;

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
