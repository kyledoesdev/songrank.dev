<?php

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

describe('the logo helper', function () {
    it('gives guests the default logo', function () {
        expect(logo())->toBe(asset('images/logo.png'));
    });

    it('gives a free account the default logo', function () {
        actingAs(User::factory()->createOne());

        expect(logo())->toBe(asset('images/logo.png'));
    });

    it('gives a Pro account the Pro logo', function () {
        actingAs(proUser());

        expect(logo())->toBe(asset('images/pro-logo.png'));
    });
});

describe('where the logo is drawn', function () {
    it('swaps in the Pro logo in the navigation for a Pro account', function () {
        actingAs(proUser())
            ->get(route('explore'))
            ->assertOk()
            ->assertSee(asset('images/pro-logo.png'))
            ->assertDontSee(asset('images/logo.png'));
    });

    it('keeps the default logo in the navigation for everyone else', function () {
        get(route('explore'))
            ->assertOk()
            ->assertSee(asset('images/logo.png'))
            ->assertDontSee(asset('images/pro-logo.png'));
    });
});
