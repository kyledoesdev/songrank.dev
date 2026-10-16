<?php

use App\Filament\Widgets\LoginsWidget;
use App\Models\User;
use Kyledoesdev\Essentials\Stats\LoginStat;
use Livewire\Livewire;

describe('logins widget', function () {
    test('renders all stats', function () {
        $widget = Livewire::actingAs(kyle())
            ->test(LoginsWidget::class)
            ->assertOk();

        expect(widgetStats($widget))->toHaveCount(5);
    });

    test('counts total and deleted users', function () {
        $admin = kyle();
        User::factory()->count(2)->create();
        User::factory()->count(4)->create()->each->delete();

        [$total, $deleted] = widgetStats(Livewire::actingAs($admin)->test(LoginsWidget::class));

        expect($total)->toBe('3')
            ->and($deleted)->toBe('4');
    });

    test('reflects daily active users from login stats', function () {
        LoginStat::increase(5);

        [, , $daily] = widgetStats(Livewire::actingAs(kyle())->test(LoginsWidget::class));

        expect($daily)->toBe('5');
    });
});
