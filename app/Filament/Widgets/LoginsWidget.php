<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Kyledoesdev\Essentials\Stats\LoginStat;
use Spatie\Stats\DataPoint;
use Spatie\Stats\Models\StatsEvent;

class LoginsWidget extends StatsOverviewWidget
{
    protected ?string $heading = 'User Stats';

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $timezone = Auth::user()->timezone;
        $now = now()->tz($timezone);

        $totalUsers = User::query()->count();
        $newUsersThisMonth = User::query()
            ->where('created_at', '>=', $now->copy()->startOfMonth())
            ->count();
        $deletedUsers = User::onlyTrashed()->count();

        $dau = $this->loginsBetween($now->copy()->startOfDay(), $now->copy()->endOfDay());
        $dauYesterday = $this->loginsBetween($now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay());

        $wau = $this->loginsBetween($now->copy()->startOfWeek(), $now->copy()->endOfWeek());
        $wauLastWeek = $this->loginsBetween($now->copy()->subWeek()->startOfWeek(), $now->copy()->subWeek()->endOfWeek());

        $mau = $this->loginsBetween($now->copy()->startOfMonth(), $now->copy()->endOfMonth());
        $mauLastMonth = $this->loginsBetween($now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth());

        return [
            Stat::make('Total Users', short_number($totalUsers))
                ->description("{$newUsersThisMonth} new this month")
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('primary'),
            Stat::make('Deleted Users', short_number($deletedUsers))
                ->description('All-time account deletions')
                ->descriptionIcon('heroicon-m-user-minus')
                ->color('gray'),
            $this->trendStat('Daily Active Users', $dau, $dauYesterday, 'vs yesterday'),
            $this->trendStat('Weekly Active Users', $wau, $wauLastWeek, 'vs last week'),
            $this->trendStat('Monthly Active Users', $mau, $mauLastMonth, 'vs last month'),
        ];
    }

    /** Sums increments directly; StatsQuery buckets by week and costs several queries. */
    private function loginsBetween(Carbon $start, Carbon $end): int
    {
        return (int) StatsEvent::query()
            ->where('name', (new LoginStat)->getName())
            ->where('type', DataPoint::TYPE_CHANGE)
            ->where('value', '>', 0)
            ->whereBetween('created_at', [$start, $end])
            ->sum('value');
    }

    private function trendStat(string $label, int $current, int $previous, string $comparedTo): Stat
    {
        $delta = $current - $previous;

        [$icon, $color] = match (true) {
            $delta > 0 => ['heroicon-m-arrow-trending-up', 'success'],
            $delta < 0 => ['heroicon-m-arrow-trending-down', 'danger'],
            default => ['heroicon-m-minus', 'gray'],
        };

        $sign = $delta > 0 ? '+' : '';

        return Stat::make($label, short_number($current))
            ->description("{$sign}{$delta} {$comparedTo}")
            ->descriptionIcon($icon)
            ->color($color);
    }
}
