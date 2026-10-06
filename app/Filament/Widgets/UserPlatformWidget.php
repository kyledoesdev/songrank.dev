<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class UserPlatformWidget extends ChartWidget
{
    protected ?string $heading = 'User Platforms';

    protected ?string $maxHeight = '300px';

    protected ?string $pollingInterval = null;

    /** iOS reports no platform client hint, so those users are read off the user agent instead. */
    protected function getData(): array
    {
        $iphones = User::query()
            ->where('user_agent', 'like', '%iPhone%')
            ->count();

        $platforms = User::query()
            ->whereNotNull('user_platform')
            ->where('user_platform', '!=', '')
            ->where(fn (Builder $query) => $query
                ->whereNull('user_agent')
                ->orWhere('user_agent', 'not like', '%iPhone%'))
            ->pluck('user_platform')
            ->map(fn (string $platform) => Str::of($platform)->trim('"')->lower()->toString())
            ->filter()
            ->countBy()
            ->when($iphones > 0, fn (Collection $platforms) => $platforms->put('iphone', $iphones))
            ->sortDesc();

        $colors = $platforms->keys()->map(fn (string $platform) => $this->getPlatformColor($platform))->values();

        return [
            'datasets' => [
                [
                    'label' => 'Users',
                    'data' => $platforms->values()->toArray(),
                    'backgroundColor' => $colors->toArray(),
                ],
            ],
            'labels' => $platforms->keys()->map(fn (string $platform) => $this->formatLabel($platform))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getPlatformColor(string $platform): string
    {
        return match ($platform) {
            'iphone' => '#f4f4f5',
            'macos' => '#7dd3fc',
            'android' => '#a3e635',
            'windows' => '#2dd4bf',
            'linux' => '#facc15',
            'chrome os', 'chromeos' => '#fb923c',
            default => '#a1a1aa',
        };
    }

    protected function formatLabel(string $platform): string
    {
        return match ($platform) {
            'iphone' => 'iPhone',
            'macos' => 'macOS',
            'android' => 'Android',
            'windows' => 'Windows',
            'linux' => 'Linux',
            'chrome os', 'chromeos' => 'Chrome OS',
            default => ucfirst($platform),
        };
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}
