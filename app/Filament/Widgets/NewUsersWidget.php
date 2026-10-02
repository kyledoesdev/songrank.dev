<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\HasDateFilters;
use App\Models\User;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class NewUsersWidget extends ChartWidget
{
    use HasDateFilters;

    protected ?string $heading = 'New Users';

    protected int|string|array $columnSpan = 2;

    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $trendConfig = $this->getTrendConfig();

        $newUsers = Trend::model(User::class)
            ->between(start: $trendConfig['start'], end: $trendConfig['end'])
            ->{$trendConfig['period']}()
            ->count();

        $deletedUsers = Trend::query(User::onlyTrashed())
            ->dateColumn('deleted_at')
            ->between(start: $trendConfig['start'], end: $trendConfig['end'])
            ->{$trendConfig['period']}()
            ->count();

        return [
            'datasets' => [
                [
                    'label' => 'New Users',
                    'data' => $newUsers->map(fn (TrendValue $value) => $value->aggregate),
                    'borderColor' => '#93c5fd',
                ],
                [
                    'label' => 'Deleted Users',
                    'data' => $deletedUsers->map(fn (TrendValue $value) => $value->aggregate),
                    'borderColor' => '#f87171',
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
