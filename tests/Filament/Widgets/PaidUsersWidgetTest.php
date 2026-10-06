<?php

use App\Enums\Billing\ProLicenseSource;
use App\Filament\Widgets\PaidUsersWidget;
use App\Models\ProLicense;
use Livewire\Livewire;

describe('paid users widget', function () {
    test('counts only users who bought their licence', function () {
        ProLicense::factory()->count(2)->active()->create();
        ProLicense::factory()->comp()->create();
        ProLicense::factory()->comp()->create(['source' => ProLicenseSource::GIFT]);
        ProLicense::factory()->refunded()->create();
        ProLicense::factory()->revoked()->create();

        Livewire::actingAs(kyle())
            ->test(PaidUsersWidget::class)
            ->assertSeeInOrder(['Paid Users', '2', '2 complimentary or gifted'])
            ->assertSeeInOrder(['All Pro Users', '4']);
    });

    test('renders with no licences at all', function () {
        Livewire::actingAs(kyle())
            ->test(PaidUsersWidget::class)
            ->assertOk()
            ->assertSee('0 complimentary or gifted');
    });
});
