<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Collection;

class UserGeographyWidget extends ChartWidget
{
    protected ?string $heading = 'User Geography';

    protected ?string $maxHeight = '300px';

    public ?string $filter = 'continent';

    protected ?string $pollingInterval = null;

    protected function getFilters(): ?array
    {
        return [
            'continent' => 'By Continent',
            'country' => 'By Country',
            'region' => 'By Region',
            'city' => 'By City',
        ];
    }

    protected function getData(): array
    {
        $data = $this->getGroupedData();

        return [
            'datasets' => [
                [
                    'label' => 'Users',
                    'data' => $data->values()->toArray(),
                    'backgroundColor' => $this->getColors($data->count()),
                ],
            ],
            'labels' => $data->keys()->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getGroupedData(): Collection
    {
        $packets = User::query()
            ->whereNotNull('user_packet')
            ->pluck('user_packet')
            ->map(fn ($packet) => (array) (is_string($packet) ? json_decode($packet) : $packet))
            ->filter();

        $data = match ($this->filter) {
            'country' => $packets->pluck('country'),
            'region' => $packets->map(fn ($p) => ($p['regionName'] ?? $p['region'] ?? null)),
            'city' => $packets->pluck('city'),
            default => $packets->map(fn ($p) => $this->countryToContinent($p['countryCode'] ?? null)),
        };

        return $data
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take(20);
    }

    protected function countryToContinent(?string $code): ?string
    {
        if (! $code) {
            return null;
        }

        $continents = [
            'AF' => 'Africa',
            'AN' => 'Antarctica',
            'AS' => 'Asia',
            'EU' => 'Europe',
            'NA' => 'North America',
            'OC' => 'Oceania',
            'SA' => 'South America',
        ];

        $countryToContinentCode = [
            'US' => 'NA', 'CA' => 'NA', 'MX' => 'NA', 'GT' => 'NA', 'BZ' => 'NA', 'HN' => 'NA', 'SV' => 'NA', 'NI' => 'NA', 'CR' => 'NA', 'PA' => 'NA',
            'CU' => 'NA', 'JM' => 'NA', 'HT' => 'NA', 'DO' => 'NA', 'PR' => 'NA', 'BS' => 'NA', 'BB' => 'NA', 'TT' => 'NA',
            'BR' => 'SA', 'AR' => 'SA', 'CO' => 'SA', 'PE' => 'SA', 'VE' => 'SA', 'CL' => 'SA', 'EC' => 'SA', 'BO' => 'SA', 'PY' => 'SA', 'UY' => 'SA', 'GY' => 'SA', 'SR' => 'SA',
            'GB' => 'EU', 'DE' => 'EU', 'FR' => 'EU', 'IT' => 'EU', 'ES' => 'EU', 'PT' => 'EU', 'NL' => 'EU', 'BE' => 'EU', 'AT' => 'EU', 'CH' => 'EU',
            'PL' => 'EU', 'CZ' => 'EU', 'SK' => 'EU', 'HU' => 'EU', 'RO' => 'EU', 'BG' => 'EU', 'GR' => 'EU', 'SE' => 'EU', 'NO' => 'EU', 'DK' => 'EU',
            'FI' => 'EU', 'IE' => 'EU', 'UA' => 'EU', 'BY' => 'EU', 'LT' => 'EU', 'LV' => 'EU', 'EE' => 'EU', 'HR' => 'EU', 'SI' => 'EU', 'RS' => 'EU',
            'BA' => 'EU', 'ME' => 'EU', 'MK' => 'EU', 'AL' => 'EU', 'LU' => 'EU', 'MT' => 'EU', 'CY' => 'EU', 'IS' => 'EU', 'MD' => 'EU',
            'RU' => 'EU', 'TR' => 'AS',
            'CN' => 'AS', 'JP' => 'AS', 'KR' => 'AS', 'IN' => 'AS', 'ID' => 'AS', 'TH' => 'AS', 'VN' => 'AS', 'PH' => 'AS', 'MY' => 'AS', 'SG' => 'AS',
            'PK' => 'AS', 'BD' => 'AS', 'IR' => 'AS', 'IQ' => 'AS', 'SA' => 'AS', 'AE' => 'AS', 'IL' => 'AS', 'JO' => 'AS', 'LB' => 'AS', 'SY' => 'AS',
            'KW' => 'AS', 'QA' => 'AS', 'BH' => 'AS', 'OM' => 'AS', 'YE' => 'AS', 'AF' => 'AS', 'KZ' => 'AS', 'UZ' => 'AS', 'TM' => 'AS', 'KG' => 'AS',
            'TJ' => 'AS', 'NP' => 'AS', 'LK' => 'AS', 'MM' => 'AS', 'KH' => 'AS', 'LA' => 'AS', 'MN' => 'AS', 'TW' => 'AS', 'HK' => 'AS', 'MO' => 'AS',
            'AU' => 'OC', 'NZ' => 'OC', 'PG' => 'OC', 'FJ' => 'OC', 'SB' => 'OC', 'VU' => 'OC', 'NC' => 'OC', 'PF' => 'OC', 'WS' => 'OC', 'GU' => 'OC',
            'EG' => 'AF', 'ZA' => 'AF', 'NG' => 'AF', 'KE' => 'AF', 'MA' => 'AF', 'DZ' => 'AF', 'TN' => 'AF', 'GH' => 'AF', 'ET' => 'AF', 'TZ' => 'AF',
            'UG' => 'AF', 'SD' => 'AF', 'LY' => 'AF', 'CI' => 'AF', 'CM' => 'AF', 'SN' => 'AF', 'ZW' => 'AF', 'AO' => 'AF', 'MZ' => 'AF', 'MG' => 'AF',
        ];

        $continentCode = $countryToContinentCode[strtoupper($code)] ?? null;

        return $continentCode ? $continents[$continentCode] : null;
    }

    protected function getColors(int $count): array
    {
        $palette = [
            '#c084fc', '#a855f7', '#9333ea', '#7c3aed',
            '#86efac', '#4ade80', '#22c55e', '#16a34a',
            '#93c5fd', '#60a5fa', '#3b82f6', '#2563eb',
            '#fca5a5', '#f87171', '#ef4444', '#dc2626',
            '#d8b4fe', '#a5f3fc', '#fde047', '#fdba74',
        ];

        return array_slice($palette, 0, $count);
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
