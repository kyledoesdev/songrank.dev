<?php

use function Pest\Laravel\actingAs;

describe('review comments', function () {
    test('displays comments component when comments are enabled', function () {
        $review = publicPublishedReview(['comments_enabled' => true]);

        actingAs(kyle())
            ->get(route('review', ['id' => $review->getKey()]))
            ->assertOk()
            ->assertSeeLivewire('comments');
    });

    test('hides comments component when comments are disabled', function () {
        $review = publicPublishedReview(['comments_enabled' => false]);

        actingAs(kyle())
            ->get(route('review', ['id' => $review->getKey()]))
            ->assertOk()
            ->assertDontSeeLivewire('comments');
    });

    test('names the review and links back to it from a comment', function () {
        $review = publicPublishedReview(['name' => 'Still holds up']);

        expect($review->commentableName())->toBe('Still holds up')
            ->and($review->commentUrl())->toBe(route('review', ['id' => $review->getKey()]));
    });

    test('masks profanity in a comment left on a review', function () {
        $review = publicPublishedReview(['comments_enabled' => true]);

        $comment = $review->comment('a fucking great review', $review->user);

        expect($comment->fresh()->text)->toContain('a ******* great review');
    });
});

describe('review comment replies', function () {
    test('allows replies when comment replies are enabled', function () {
        $review = publicPublishedReview([
            'comments_enabled' => true,
            'comments_replies_enabled' => true,
        ]);

        actingAs(kyle())
            ->get(route('review', ['id' => $review->getKey()]))
            ->assertOk()
            ->assertSee('&quot;showReplies&quot;:true', false);
    });

    test('disables replies when comment replies are disabled', function () {
        $review = publicPublishedReview([
            'comments_enabled' => true,
            'comments_replies_enabled' => false,
        ]);

        actingAs(kyle())
            ->get(route('review', ['id' => $review->getKey()]))
            ->assertOk()
            ->assertSee('&quot;showReplies&quot;:false', false);
    });
});
