<?php

use App\Filament\Resources\LandingPageContents\Pages\EditLandingPageContent;
use App\Models\LandingPageContent;
use Livewire\Livewire;

use function Pest\Laravel\get;

describe('landing page content form', function () {
    test('publishes an edit to the landing page without waiting on the cache', function () {
        get(route('welcome'))->assertSee('Pricing');

        $content = LandingPageContent::query()->where('slug', 'pricing-title')->first();

        Livewire::actingAs(kyle())
            ->test(EditLandingPageContent::class, ['record' => $content->getKey()])
            ->fillForm(['text' => 'Plans & Pricing'])
            ->call('save')
            ->assertHasNoFormErrors();

        get(route('welcome'))->assertSee('Plans &amp; Pricing', false);
    });
});
