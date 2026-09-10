<?php

use App\Http\Middleware\EnsureUserIsPro;
use App\Models\ProLicense;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Laravel\Pennant\Feature;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

describe('the songrank-pro feature flag', function () {
    it('is active for developers', function () {
        expect(Feature::for(kyle())->active('songrank-pro'))->toBeTrue();
    });

    it('is inactive for everyone else', function () {
        expect(Feature::for(User::factory()->createOne())->active('songrank-pro'))->toBeFalse();
    });

    it('is inactive for guests without throwing', function () {
        expect(Feature::for(null)->active('songrank-pro'))->toBeFalse();
    });
});

describe('billing routes', function () {
    it('are hidden while the feature is inactive', function () {
        actingAs(User::factory()->createOne());

        get(route('billing'))->assertNotFound();
        post(route('billing.checkout'))->assertNotFound();
        get(route('billing.success'))->assertNotFound();
        get(route('billing.cancel'))->assertNotFound();
    });

    it('are reachable once the feature is active', function () {
        Feature::define('songrank-pro', true);

        actingAs(User::factory()->createOne());

        get(route('billing'))->assertOk();
    });

    it('require authentication', function () {
        Feature::define('songrank-pro', true);

        get(route('billing'))->assertRedirect(route('welcome'));
    });
});

describe('EnsureUserIsPro', function () {
    beforeEach(function () {
        Feature::define('songrank-pro', true);

        Route::middleware(['web', 'auth', EnsureUserIsPro::class])
            ->get('/__pro-only', fn () => 'pro content')
            ->name('pro.only');
    });

    it('admits a user holding an active license', function () {
        actingAs(proUser())
            ->get('/__pro-only')
            ->assertOk()
            ->assertSee('pro content');
    });

    it('sends a free user to billing rather than a 403', function () {
        actingAs(User::factory()->createOne())
            ->get('/__pro-only')
            ->assertRedirect(route('billing'));
    });

    it('does not admit a user whose license was refunded', function () {
        $user = User::factory()->createOne();
        ProLicense::factory()->refunded()->for($user)->create();

        actingAs($user)
            ->get('/__pro-only')
            ->assertRedirect(route('billing'));
    });
});
