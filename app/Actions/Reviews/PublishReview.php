<?php

namespace App\Actions\Reviews;

use App\Models\Review;

final class PublishReview
{
    /**
     * Publishing is one way. A published review can still be edited by a Pro
     * account, but it does not go back to being a draft — people may already
     * have seen it, and comments may already hang off it.
     */
    public function handle(Review $review): Review
    {
        if ($review->is_published) {
            return $review;
        }

        $review->update([
            'is_published' => true,
            'published_at' => now(),
        ]);

        return $review->fresh();
    }
}
