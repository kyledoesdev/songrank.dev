<?php

use App\Models\ApplicationDashboard;

use function Pest\Laravel\get;

describe('about page', function () {
    test('renders the editable welcome copy', function () {
        ApplicationDashboard::query()->first()->update([
            'about_page' => '<h2>Welcome to SongRank</h2><p>Rank the tracks you love.</p>',
        ]);

        get(route('about'))
            ->assertOk()
            ->assertSee('Welcome to SongRank')
            ->assertSee('Rank the tracks you love.');
    });
});
