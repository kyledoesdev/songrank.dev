<?php

use App\Filament\Resources\Comments\Pages\EditComment;
use App\Models\User;
use Livewire\Livewire;

describe('editing a comment', function () {
    it('runs the edit back through the profanity filter', function () {
        $ranking = publicCompletedRanking();
        $comment = $ranking->comment('Great list!', User::factory()->createOne());

        Livewire::actingAs(kyle())
            ->test(EditComment::class, ['record' => $comment->getKey()])
            ->fillForm(['original_text' => 'a fucking great list'])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($comment->fresh())
            ->original_text->toBe('a fucking great list')
            ->text->not->toContain('fucking');
    });
});
