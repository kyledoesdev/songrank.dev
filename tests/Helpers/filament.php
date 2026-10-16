<?php

use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Features\SupportTesting\Testable;

/*
| A dashboard widget's numbers, read from the widget rather than from its
| rendered text: a stats widget's values in the order it shows them, and a
| chart widget's datasets in the order it draws them. Destructure them into
| names, `[$paid, $allPro] = widgetStats($widget);`.
*/

/**
 * @return list<mixed>
 */
function widgetStats(Testable $widget): array
{
    $stats = (fn (): array => $this->getStats())->call($widget->instance());

    return array_map(fn (Stat $stat) => $stat->getValue(), $stats);
}

/**
 * @return list<list<int|float>>
 */
function widgetChartData(Testable $widget): array
{
    $data = (fn (): array => $this->getData())->call($widget->instance());

    return array_map(fn (array $dataset) => collect($dataset['data'])->values()->all(), $data['datasets']);
}
