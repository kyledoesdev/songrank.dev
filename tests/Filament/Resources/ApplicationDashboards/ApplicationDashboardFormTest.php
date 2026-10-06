<?php

use App\Filament\Resources\ApplicationDashboards\Pages\ManageApplicationDashboards;
use App\Models\ApplicationDashboard;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

describe('application settings form', function () {
    test('saving new seo terms replaces the cached ones', function () {
        $dashboard = ApplicationDashboard::query()->first();
        $dashboard->update(['seo_terms' => 'old terms']);

        expect(ApplicationDashboard::seoTerms())->toBe('old terms');

        Livewire::actingAs(kyle())
            ->test(ManageApplicationDashboards::class)
            ->callAction(TestAction::make('edit')->table($dashboard), [
                'seo_terms' => 'music, rankings, tier lists',
            ])
            ->assertHasNoFormErrors();

        expect(ApplicationDashboard::seoTerms())->toBe('music, rankings, tier lists');
    });

    test('deleting the settings clears the cached terms', function () {
        $dashboard = ApplicationDashboard::query()->first();
        $dashboard->update(['seo_terms' => 'cached terms']);

        expect(ApplicationDashboard::seoTerms())->toBe('cached terms');

        $dashboard->delete();

        expect(cache()->has(ApplicationDashboard::SEO_TERMS_CACHE_KEY))->toBeFalse();
    });
});
