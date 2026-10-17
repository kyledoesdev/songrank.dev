<?php

use App\Enums\LegalDocumentType;
use App\Livewire\Legal\Privacy;
use App\Livewire\Legal\Terms;
use App\Models\LegalDocument;
use Livewire\Livewire;

use function Pest\Laravel\get;

describe('legal document pages', function () {
    test('terms renders the document currently in force', function () {
        LegalDocument::query()->forceDelete();

        $document = LegalDocument::factory()->terms()->create([
            'content' => '<h2>Acceptance of These Terms</h2><p>You agree to be bound.</p>',
        ]);

        get(route('terms'))->assertOk();

        Livewire::test(Terms::class)
            ->assertViewHas('type', LegalDocumentType::TERMS)
            ->assertViewHas('document', fn (LegalDocument $current) => $current->is($document))
            ->assertSeeHtml('<p>You agree to be bound.</p>');
    });

    test('privacy renders the document currently in force', function () {
        LegalDocument::query()->forceDelete();

        $document = LegalDocument::factory()->privacy()->create([
            'content' => '<h2>Information We Get From Spotify</h2><p>Your display name.</p>',
        ]);

        get(route('privacy'))->assertOk();

        Livewire::test(Privacy::class)
            ->assertViewHas('type', LegalDocumentType::PRIVACY)
            ->assertViewHas('document', fn (LegalDocument $current) => $current->is($document))
            ->assertSeeHtml('<p>Your display name.</p>');
    });

    test('the most recently effective revision wins', function () {
        LegalDocument::query()->forceDelete();

        LegalDocument::factory()->terms()->effectiveAt('2020-01-01 00:00:00')->create();
        $current = LegalDocument::factory()->terms()->effectiveAt('2024-01-01 00:00:00')->create();

        Livewire::test(Terms::class)
            ->assertViewHas('document', fn (LegalDocument $document) => $document->is($current));
    });

    test('a revision dated in the future is not served yet', function () {
        LegalDocument::query()->forceDelete();

        $current = LegalDocument::factory()->terms()->create();
        LegalDocument::factory()->terms()->effectiveAt(now()->addWeek()->toDateTimeString())->create();

        Livewire::test(Terms::class)
            ->assertViewHas('document', fn (LegalDocument $document) => $document->is($current));
    });

    test('each document only serves its own type', function () {
        LegalDocument::query()->forceDelete();

        $terms = LegalDocument::factory()->terms()->create();
        $privacy = LegalDocument::factory()->privacy()->create();

        Livewire::test(Terms::class)
            ->assertViewHas('document', fn (LegalDocument $document) => $document->is($terms));

        Livewire::test(Privacy::class)
            ->assertViewHas('document', fn (LegalDocument $document) => $document->is($privacy));
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

        get(route('terms'))->assertOk();
        get(route('privacy'))->assertOk();

        Livewire::test(Terms::class)->assertViewHas('document', null);
        Livewire::test(Privacy::class)->assertViewHas('document', null);
    });
});
