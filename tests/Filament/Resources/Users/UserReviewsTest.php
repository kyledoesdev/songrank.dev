<?php

use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Resources\Users\RelationManagers\ReviewsRelationManager;
use App\Models\Review;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

describe('user reviews relation manager', function () {
    test('lists the user\'s reviews, drafts included', function () {
        $user = userWithReviews(2);

        Livewire::actingAs(kyle())
            ->test(ReviewsRelationManager::class, [
                'ownerRecord' => $user,
                'pageClass' => ViewUser::class,
            ])
            ->assertCanSeeTableRecords($user->reviews);
    });

    test('deletes through the review action, clearing the explore count', function () {
        $review = publicPublishedReview();

        cache()->put('explore:total-reviews', 1);

        Livewire::actingAs(kyle())
            ->test(ReviewsRelationManager::class, [
                'ownerRecord' => $review->user,
                'pageClass' => ViewUser::class,
            ])
            ->callAction(TestAction::make(DeleteAction::class)->table($review));

        expect(Review::find($review->getKey()))->toBeNull();
        expect(cache()->has('explore:total-reviews'))->toBeFalse();
    });
});
