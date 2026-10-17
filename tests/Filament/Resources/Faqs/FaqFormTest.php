<?php

use App\Filament\Resources\Faqs\Pages\EditFaq;
use App\Models\Faq;
use Livewire\Livewire;

describe('editing a faq', function () {
    it('saves the question and whether it shows', function () {
        $faq = Faq::factory()->create(['is_active' => true]);

        Livewire::actingAs(kyle())
            ->test(EditFaq::class, ['record' => $faq->getKey()])
            ->fillForm(['question' => 'How do I rank an album?', 'is_active' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($faq->fresh())
            ->question->toBe('How do I rank an album?')
            ->is_active->toBeFalse();
    });
});
