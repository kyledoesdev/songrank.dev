<?php

use App\Models\LegalDocument;

use function Pest\Laravel\get;

describe('legal document pages', function () {
    test('terms renders the document currently in force', function () {
        LegalDocument::query()->forceDelete();

        LegalDocument::factory()->terms()->create([
            'content' => '<h2>Acceptance of These Terms</h2><p>You agree to be bound.</p>',
        ]);

        get(route('terms'))
            ->assertOk()
            ->assertSee('Terms of Service')
            ->assertSee('Acceptance of These Terms')
            ->assertSee('You agree to be bound.');
    });

    test('privacy renders the document currently in force', function () {
        LegalDocument::query()->forceDelete();

        LegalDocument::factory()->privacy()->create([
            'content' => '<h2>Information We Get From Spotify</h2><p>Your display name.</p>',
        ]);

        get(route('privacy'))
            ->assertOk()
            ->assertSee('Privacy Policy')
            ->assertSee('Information We Get From Spotify')
            ->assertSee('Your display name.');
    });

    test('the most recently effective revision wins', function () {
        LegalDocument::query()->forceDelete();

        LegalDocument::factory()->terms()->effectiveAt('2020-01-01 00:00:00')->create([
            'content' => '<h2>Old Section</h2>',
        ]);

        LegalDocument::factory()->terms()->effectiveAt('2024-01-01 00:00:00')->create([
            'content' => '<h2>Current Section</h2>',
        ]);

        get(route('terms'))
            ->assertOk()
            ->assertSee('Current Section')
            ->assertDontSee('Old Section');
    });

    test('a revision dated in the future is not served yet', function () {
        LegalDocument::query()->forceDelete();

        LegalDocument::factory()->terms()->create([
            'content' => '<h2>Published Section</h2>',
        ]);

        LegalDocument::factory()->terms()->effectiveAt(now()->addWeek()->toDateTimeString())->create([
            'content' => '<h2>Unpublished Section</h2>',
        ]);

        get(route('terms'))
            ->assertOk()
            ->assertSee('Published Section')
            ->assertDontSee('Unpublished Section');
    });

    test('each document only serves its own type', function () {
        LegalDocument::query()->forceDelete();

        LegalDocument::factory()->terms()->create(['content' => '<h2>Terms Only Section</h2>']);
        LegalDocument::factory()->privacy()->create(['content' => '<h2>Privacy Only Section</h2>']);

        get(route('terms'))->assertOk()->assertDontSee('Privacy Only Section');
        get(route('privacy'))->assertOk()->assertDontSee('Terms Only Section');
    });

    test('headings get anchors and a table of contents', function () {
        LegalDocument::query()->forceDelete();

        LegalDocument::factory()->terms()->create([
            'content' => '<h2>Acceptable Use</h2><p>Behave.</p><h2>Governing Law</h2><p>Somewhere.</p>',
        ]);

        get(route('terms'))
            ->assertOk()
            ->assertSee('<h2 id="acceptable-use">', false)
            ->assertSee('<h2 id="governing-law">', false)
            ->assertSee('href="#acceptable-use"', false)
            ->assertSee('href="#governing-law"', false);
    });

    test('an unpublished document shows an empty state instead of erroring', function () {
        LegalDocument::query()->forceDelete();

        get(route('terms'))
            ->assertOk()
            ->assertDontSee('<h2', escape: false);

        get(route('privacy'))
            ->assertOk()
            ->assertDontSee('<h2', escape: false);
    });
});
