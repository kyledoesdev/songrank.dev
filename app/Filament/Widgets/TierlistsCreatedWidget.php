<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\HasDateFilters;
use App\Models\Tierlist;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class TierlistsCreatedWidget extends ChartWidget
{
    use HasDateFilters;

    protected ?string $heading = 'Tier List Stats';

    protected static ?int $sort = 4;

    protected function getData(): array
    {
        $trendConfig = $this->getTrendConfig();

        $created = Trend::model(Tierlist::class)
            ->between(start: $trendConfig['start'], end: $trendConfig['end'])
            ->{$trendConfig['period']}()
            ->count();

        $deleted = Trend::query(Tierlist::onlyTrashed())
            ->dateColumn('deleted_at')
            ->between(start: $trendConfig['start'], end: $trendConfig['end'])
            ->{$trendConfig['period']}()
            ->count();

        $completed = Trend::query(Tierlist::query()->whereNotNull('completed_at'))
            ->dateColumn('completed_at')
            ->between(start: $trendConfig['start'], end: $trendConfig['end'])
            ->{$trendConfig['period']}()
            ->count();

        return [
            'datasets' => [
                [
                    'label' => 'Tier Lists Created',
                    'data' => $created->map(fn (TrendValue $value) => $value->aggregate),
                    'borderColor' => '#86efac',
                ],
                [
                    'label' => 'Tier Lists Deleted',
                    'data' => $deleted->map(fn (TrendValue $value) => $value->aggregate),
                    'borderColor' => '#f87171',
                ],
                [
                    'label' => 'Tier Lists Completed',
                    'data' => $completed->map(fn (TrendValue $value) => $value->aggregate),
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
