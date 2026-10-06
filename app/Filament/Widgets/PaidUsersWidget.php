<?php

namespace App\Filament\Widgets;

use App\Enums\Billing\ProLicenseSource;
use App\Models\ProLicense;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PaidUsersWidget extends StatsOverviewWidget
{
    protected ?string $heading = 'Pro';

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $proUsers = User::query()->where('is_pro', true)->count();
        $paidUsers = $this->paidUserCount();
        $unpaid = $proUsers - $paidUsers;

        return [
            Stat::make('Paid Users', short_number($paidUsers))
                ->description("{$unpaid} complimentary or gifted")
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('success'),
            Stat::make('All Pro Users', short_number($proUsers))
                ->description('Paid, complimentary and gifted')
                ->descriptionIcon('heroicon-m-star')
                ->color('primary'),
        ];
    }

    /**
     * Users holding an active licence they bought, leaving out comps and gifts.
     */
    private function paidUserCount(): int
    {
        return ProLicense::query()
            ->active()
            ->where('source', ProLicenseSource::PURCHASE)
            ->distinct()
            ->count('user_id');
    }
}
