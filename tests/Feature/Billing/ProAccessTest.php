<?php

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

describe('billing routes', function () {
    it('are reachable for any signed-in user', function () {
        actingAs(User::factory()->createOne());

        get(route('billing'))->assertOk();
    });

    it('require authentication', function () {
        get(route('billing'))->assertRedirect(route('welcome'));
    });
});
