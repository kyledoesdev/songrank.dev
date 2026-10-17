<?php

use App\Enums\Billing\Plan;
use App\Filament\Resources\PlanFeatures\Pages\ListPlanFeatures;
use App\Models\PlanFeature;
use Livewire\Livewire;

describe('plan feature table', function () {
    test('shows one plan per tab', function () {
        PlanFeature::query()->forceDelete();

        $free = PlanFeature::factory()->count(2)->create();
        $pro = PlanFeature::factory()->count(2)->pro()->create();

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
        $feature = PlanFeature::factory()->create(['plan' => Plan::FREE, 'label' => '{rankings} rankings to begin with']);

        Livewire::actingAs(kyle())
            ->test(ListPlanFeatures::class)
            ->set('activeTab', 'free')
            ->assertTableColumnStateSet('label', '{rankings} rankings to begin with', $feature)
            ->assertTableColumnStateSet('rendered_label', $feature->renderedLabel(), $feature);
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
