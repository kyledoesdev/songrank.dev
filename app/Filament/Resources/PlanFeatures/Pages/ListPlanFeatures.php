<?php

namespace App\Filament\Resources\PlanFeatures\Pages;

use App\Enums\Billing\Plan;
use App\Filament\Resources\PlanFeatures\PlanFeatureResource;
use App\Models\PlanFeature;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListPlanFeatures extends ListRecords
{
    protected static string $resource = PlanFeatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * One tab per plan, so a drag reorders one card's list at a time.
     *
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return collect(Plan::cases())
            ->mapWithKeys(fn (Plan $plan) => [
                $plan->value => Tab::make($plan->label())
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('plan', $plan->value)),
            ])
            ->all();
    }

    /**
     * Filament reorders with a mass update, which fires no model events.
     */
    public function reorderTable(array $order, int|string|null $draggedRecordKey = null): void
    {
        parent::reorderTable($order, $draggedRecordKey);

        cache()->forget(PlanFeature::CACHE_KEY);
    }
}
