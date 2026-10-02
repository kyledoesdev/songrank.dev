<?php

use App\Filament\Resources\Reviews\Pages\ViewReview;
use App\Filament\Resources\Reviews\RelationManagers\CommentsRelationManager;
use Livewire\Livewire;

describe('review comments relation manager', function () {
    test('lists the comments left on the review', function () {
        $review = publicPublishedReview(['comments_enabled' => true]);
        $comment = $review->comment('spot on', $review->user);

        Livewire::actingAs(kyle())
            ->test(CommentsRelationManager::class, [
                'ownerRecord' => $review,
                'pageClass' => ViewReview::class,
            ])
            ->assertOk()
            ->assertCanSeeTableRecords([$comment]);
    });
});
