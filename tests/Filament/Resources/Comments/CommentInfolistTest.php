<?php

use App\Filament\Resources\Comments\CommentResource;
use App\Filament\Resources\Comments\Pages\ViewComment;
use App\Filament\Resources\Rankings\RankingResource;
use App\Filament\Resources\Reviews\ReviewResource;
use App\Filament\Resources\Tierlists\TierlistResource;
use App\Models\Ranking;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\Comments\Models\Comment;

describe('commented on link', function () {
    test('links a top level comment to the ranking it belongs to', function () {
        $ranking = publicCompletedRanking();

        $comment = $ranking->comment('great list', $ranking->user);

        viewComment($comment)
            ->assertSee($ranking->name)
            ->assertSee(RankingResource::getUrl('view', ['record' => $ranking]));
    });

    test('links a top level comment to the tier list it belongs to', function () {
        $tierlist = publicCompletedTierlist();

        $comment = $tierlist->comment('great tiers', $tierlist->user);

        viewComment($comment)
            ->assertSee($tierlist->name)
            ->assertSee(TierlistResource::getUrl('view', ['record' => $tierlist]));
    });

    test('links a top level comment to the review it belongs to', function () {
        $review = publicPublishedReview();

        $comment = $review->comment('great review', $review->user);

        viewComment($comment)
            ->assertSee($review->name)
            ->assertSee(ReviewResource::getUrl('view', ['record' => $review]));
    });

    test('links a reply to its parent comment rather than an unrelated ranking', function () {
        $ranking = publicCompletedRanking();

        $parent = $ranking->comment('great list', $ranking->user);
        $reply = $parent->comment('thanks!', $ranking->user);

        /** A ranking sharing the parent comment's id is what the old rankings-only link resolved to. */
        Ranking::factory()->createOne(['id' => $parent->getKey() + 1000]);

        viewComment($reply)
            ->assertSee("Comment #{$parent->getKey()}")
            ->assertSee(CommentResource::getUrl('view', ['record' => $parent]))
            ->assertDontSee(RankingResource::getUrl('view', ['record' => $parent->getKey()]))
            ->assertDontSee(RankingResource::getUrl('edit', ['record' => $parent->getKey()]));
    });

    test('links the parent comment field back to the parent', function () {
        $ranking = publicCompletedRanking();

        $parent = $ranking->comment('great list', $ranking->user);
        $reply = $parent->comment('thanks!', $ranking->user);

        viewComment($reply)
            ->assertSee(CommentResource::getUrl('view', ['record' => $parent]));
    });
});

function viewComment(Comment $comment): Testable
{
    return Livewire::actingAs(kyle())
        ->test(ViewComment::class, ['record' => $comment->getKey()])
        ->assertOk();
}
