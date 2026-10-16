<?php

use App\Filament\Resources\Reviews\Pages\EditReview;
use App\Filament\Resources\Reviews\Pages\ViewReview;
use App\Filament\Resources\Reviews\RelationManagers\CommentsRelationManager;
use App\Models\Comment;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
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

    test('lets the admin delete one', function () {
        $review = publicPublishedReview();
        $comment = $review->comment('Great!', User::factory()->createOne());

        Livewire::actingAs(kyle())
            ->test(CommentsRelationManager::class, ['ownerRecord' => $review, 'pageClass' => EditReview::class])
            ->callAction(TestAction::make(DeleteAction::class)->table($comment));

        expect(Comment::query()->find($comment->getKey()))->toBeNull();
    });
});
