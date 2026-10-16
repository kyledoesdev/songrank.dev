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

        [$paid, $allPro] = widgetStats(Livewire::actingAs(kyle())->test(PaidUsersWidget::class));

        expect($paid)->toBe('2')
            ->and($allPro)->toBe('4');
    });

    test('renders with no licences at all', function () {
        $widget = Livewire::actingAs(kyle())
            ->test(PaidUsersWidget::class)
            ->assertOk();

        [$paid, $allPro] = widgetStats($widget);

        expect($paid)->toBe('0')
            ->and($allPro)->toBe('0');
    });
});
