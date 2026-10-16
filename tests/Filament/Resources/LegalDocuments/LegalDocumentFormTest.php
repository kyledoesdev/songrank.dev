<?php

use App\Filament\Resources\LegalDocuments\Pages\EditLegalDocument;
use App\Models\LegalDocument;
use Livewire\Livewire;

describe('editing a legal document', function () {
    it('saves a new effective date', function () {
        $document = LegalDocument::factory()->terms()->create();

        Livewire::actingAs(kyle())
            ->test(EditLegalDocument::class, ['record' => $document->getKey()])
            ->fillForm(['effective_at' => '2026-11-01 00:00:00'])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($document->fresh()->getRawOriginal('effective_at'))->toBe('2026-11-01 00:00:00');
    });
});
