<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\HasDateFilters;
use App\Models\Review;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class ReviewsCreatedWidget extends ChartWidget
{
    use HasDateFilters;

    protected ?string $heading = 'Review Stats';

    protected function getData(): array
    {
        $trendConfig = $this->getTrendConfig();

        $created = Trend::model(Review::class)
            ->between(start: $trendConfig['start'], end: $trendConfig['end'])
            ->{$trendConfig['period']}()
            ->count();

        $deleted = Trend::query(Review::onlyTrashed())
            ->dateColumn('deleted_at')
            ->between(start: $trendConfig['start'], end: $trendConfig['end'])
            ->{$trendConfig['period']}()
            ->count();

        $published = Trend::query(Review::query()->whereNotNull('published_at'))
            ->dateColumn('published_at')
            ->between(start: $trendConfig['start'], end: $trendConfig['end'])
            ->{$trendConfig['period']}()
            ->count();

        return [
            'datasets' => [
                [
                    'label' => 'Reviews Created',
                    'data' => $created->map(fn (TrendValue $value) => $value->aggregate),
                    'borderColor' => '#86efac',
                ],
                [
                    'label' => 'Reviews Deleted',
                    'data' => $deleted->map(fn (TrendValue $value) => $value->aggregate),
                    'borderColor' => '#f87171',
                ],
                [
                    'label' => 'Reviews Published',
                    'data' => $published->map(fn (TrendValue $value) => $value->aggregate),
                    'borderColor' => '#c084fc',
                ],
            ],
            'labels' => $trendConfig['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
