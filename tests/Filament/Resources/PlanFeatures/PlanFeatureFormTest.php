<?php

use App\Enums\Billing\Plan;
use App\Filament\Resources\PlanFeatures\Pages\CreatePlanFeature;
use App\Filament\Resources\PlanFeatures\Pages\EditPlanFeature;
use App\Models\PlanFeature;
use Livewire\Livewire;

use function Pest\Laravel\get;

describe('plan feature form', function () {
    test('adds a feature to the end of a card', function () {
        Livewire::actingAs(kyle())
            ->test(CreatePlanFeature::class)
            ->fillForm([
                'plan' => Plan::PRO->value,
                'label' => 'Custom tier colours',
                'is_included' => true,
                'is_coming_soon' => false,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $feature = PlanFeature::query()->where('label', 'Custom tier colours')->first();

        expect($feature->plan)->toBe(Plan::PRO);
        expect($feature->order)->toBe(PlanFeature::query()->where('plan', Plan::PRO)->max('order'));

        get(route('welcome'))->assertSee('Custom tier colours');
    });

    test('rewrites a feature on the landing page straight away', function () {
        get(route('welcome'))->assertSee('5 reviews');

        $feature = PlanFeature::query()->where('plan', Plan::FREE)->where('label', '{reviews} reviews')->first();

        Livewire::actingAs(kyle())
            ->test(EditPlanFeature::class, ['record' => $feature->getKey()])
            ->fillForm(['label' => '{reviews} written reviews'])
            ->call('save')
            ->assertHasNoFormErrors();

        get(route('welcome'))
            ->assertSee('5 written reviews')
            ->assertDontSee('5 reviews');
    });

    test('requires a plan and a label', function () {
        Livewire::actingAs(kyle())
            ->test(CreatePlanFeature::class)
            ->fillForm(['plan' => null, 'label' => ''])
            ->call('create')
            ->assertHasFormErrors(['plan' => 'required', 'label' => 'required']);
    });
});
