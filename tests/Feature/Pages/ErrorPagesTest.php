<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\withHeaders;

beforeEach(function () {
    Route::get('/testing/abort/{status}', fn (int $status) => abort($status));
});

describe('error pages', function () {
    test('renders the custom error page with the site chrome', function () {
        get('/this-page-does-not-exist')
            ->assertNotFound()
            ->assertSee('404')
            ->assertSee(route('explore'), escape: false)
            ->assertSee('<footer', escape: false);
    });

    test('drops the site chrome for livewire requests', function () {
        withHeaders(['X-Livewire' => 'true'])
            ->get('/this-page-does-not-exist')
            ->assertNotFound()
            ->assertDontSee(route('explore'), escape: false)
            ->assertDontSee('<footer', escape: false);
    });

    test('offers support options on server errors', function () {
        get('/testing/abort/500')
            ->assertStatus(500)
            ->assertSee('discord.gg', escape: false)
            ->assertDontSee('spatie-support-bubble', escape: false);
    });

    test('adds the support bubble for signed in users', function () {
        actingAs(User::factory()->create());

        get('/testing/abort/500')
            ->assertStatus(500)
            ->assertSee('spatie-support-bubble', escape: false);
    });

    test('falls back to the xx views for statuses without their own page', function () {
        get('/testing/abort/502')
            ->assertStatus(502)
            ->assertSee('502');

        get('/testing/abort/418')
            ->assertStatus(418)
            ->assertSee('418');
    });
});
