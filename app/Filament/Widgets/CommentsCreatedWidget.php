<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\HasDateFilters;
use App\Models\Comment;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class CommentsCreatedWidget extends ChartWidget
{
    use HasDateFilters;

    protected ?string $heading = 'Comment Stats';

    protected function getData(): array
    {
        $trendConfig = $this->getTrendConfig();

        $created = Trend::model(Comment::class)
            ->between(start: $trendConfig['start'], end: $trendConfig['end'])
            ->{$trendConfig['period']}()
            ->count();

        return [
            'datasets' => [
                [
                    'label' => 'Comments Created',
                    'data' => $created->map(fn (TrendValue $value) => $value->aggregate),
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
