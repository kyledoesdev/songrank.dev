<?php

namespace App\Filament\Resources\ProLicenses\Widgets;

use App\Enums\Billing\ProLicenseSource;
use App\Enums\Billing\ProLicenseStatus;
use App\Models\ProLicense;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Laravel\Cashier\Cashier;

class ProLicenseOverview extends StatsOverviewWidget
{
    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        return [
            Stat::make('Pro Users', User::query()->where('is_pro', true)->count())
                ->description('Holding an active license')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),

            Stat::make('Net Revenue', Cashier::formatAmount($this->netRevenue(), config('billing.pro.currency')))
                ->description($this->refundSummary())
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),

            Stat::make('Paid Upgrades', $this->paidCount())
                ->description($this->compCount().' complimentary')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('info'),
        ];
    }

    /**
     * Gross takings less refunds, in minor units.
     */
    private function netRevenue(): int
    {
        $paid = ProLicense::query()->whereNotNull('purchased_at');

        return (int) $paid->clone()->sum('amount_total') - (int) $paid->clone()->sum('amount_refunded');
    }

    private function paidCount(): int
    {
        return ProLicense::query()
            ->where('source', ProLicenseSource::PURCHASE)
            ->whereNotNull('purchased_at')
            ->count();
    }

    private function compCount(): int
    {
        return ProLicense::query()
            ->where('source', ProLicenseSource::COMP)
            ->count();
    }

    private function refundSummary(): string
    {
        $refunded = ProLicense::query()
            ->where('status', ProLicenseStatus::REFUNDED)
            ->count();

        return $refunded === 0
            ? 'No refunds'
            : $refunded.' '.str('refund')->plural($refunded);
    }
}
