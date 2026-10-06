<?php

use App\Enums\Billing\Plan;
use App\Filament\Resources\PlanFeatures\Pages\ListPlanFeatures;
use App\Models\PlanFeature;
use Livewire\Livewire;

describe('plan feature table', function () {
    test('shows one plan per tab', function () {
        $free = PlanFeature::query()->where('plan', Plan::FREE)->get();
        $pro = PlanFeature::query()->where('plan', Plan::PRO)->get();

        Livewire::actingAs(kyle())
            ->test(ListPlanFeatures::class)
            ->set('activeTab', 'free')
            ->assertCanSeeTableRecords($free)
            ->assertCanNotSeeTableRecords($pro)
            ->set('activeTab', 'pro')
            ->assertCanSeeTableRecords($pro)
            ->assertCanNotSeeTableRecords($free);
    });

    test('shows what each label becomes on the card', function () {
        Livewire::actingAs(kyle())
            ->test(ListPlanFeatures::class)
            ->set('activeTab', 'free')
            ->assertSee('{rankings} rankings')
            ->assertSee('100 rankings');
    });

    test('reordering reaches the landing page straight away', function () {
        $features = PlanFeature::cached()->get('free')->where('is_included', true)->where('is_coming_soon', false);

        $reversed = $features->reverse()->map(fn (PlanFeature $feature) => (string) $feature->getKey())->values()->all();

        Livewire::actingAs(kyle())
            ->test(ListPlanFeatures::class)
            ->set('activeTab', 'free')
            ->call('reorderTable', $reversed);

        expect(PlanFeature::cached()->get('free')->take(count($reversed))->map(fn (PlanFeature $feature) => (string) $feature->getKey())->all())
            ->toBe($reversed);
    });
});
