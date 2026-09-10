<?php

use App\Enums\Billing\ProLicenseStatus;
use App\Filament\Resources\ProLicenses\Pages\ListProLicenses;
use App\Filament\Resources\Users\UserResource;
use App\Models\ProLicense;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

describe('access', function () {
    it('is closed to non-developers', function () {
        actingAs(User::factory()->createOne())
            ->get('/admin/pro-licenses')
            ->assertForbidden();
    });

    it('is closed to guests', function () {
        get('/admin/pro-licenses')->assertRedirect();
    });
});

describe('the licence table', function () {
    beforeEach(fn () => actingAs(kyle()));

    it('lists every licence', function () {
        $licenses = ProLicense::factory()->count(3)->active()->create();

        Livewire::test(ListProLicenses::class)
            ->assertCanSeeTableRecords($licenses);
    });

    it('finds a licence by its uuid', function () {
        $licenses = ProLicense::factory()->count(3)->active()->create();

        Livewire::test(ListProLicenses::class)
            ->searchTable($licenses->first()->uuid)
            ->assertCanSeeTableRecords($licenses->take(1))
            ->assertCanNotSeeTableRecords($licenses->skip(1));
    });

    it('finds a licence by the buyer name', function () {
        $buyer = User::factory()->createOne(['name' => 'Findable Person']);
        $theirs = ProLicense::factory()->active()->for($buyer)->create();
        $others = ProLicense::factory()->count(2)->active()->create();

        Livewire::test(ListProLicenses::class)
            ->searchTable('Findable Person')
            ->assertCanSeeTableRecords([$theirs])
            ->assertCanNotSeeTableRecords($others);
    });

    it('filters by status', function () {
        $active = ProLicense::factory()->active()->create();
        $refunded = ProLicense::factory()->refunded()->create();

        Livewire::test(ListProLicenses::class)
            ->filterTable('status', ProLicenseStatus::REFUNDED->value)
            ->assertCanSeeTableRecords([$refunded])
            ->assertCanNotSeeTableRecords([$active]);
    });

    it('links the buyer name to their user record', function () {
        $buyer = User::factory()->createOne();
        ProLicense::factory()->active()->for($buyer)->create();

        Livewire::test(ListProLicenses::class)
            ->assertSee(UserResource::getUrl('view', ['record' => $buyer]));
    });

    it('shows a placeholder where the account was deleted', function () {
        $license = ProLicense::factory()->active()->create(['user_id' => null]);

        Livewire::test(ListProLicenses::class)
            ->assertCanSeeTableRecords([$license])
            ->assertSee('Account deleted');
    });

    it('formats money rather than showing raw cents', function () {
        ProLicense::factory()->active()->create();

        Livewire::test(ListProLicenses::class)
            ->assertSee('$10.00');
    });
});
