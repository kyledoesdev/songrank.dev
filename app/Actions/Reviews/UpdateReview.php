<?php

namespace App\Actions\Reviews;

use App\Models\Review;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class UpdateReview
{
    /**
     * The subject is not editable: a review of one album cannot become a review
     * of another without becoming a different review.
     *
     * @param  array{name?: ?string, stars?: ?float, body: ?string, is_public?: bool, comments_enabled?: bool, comments_replies_enabled?: bool}  $attributes
     */
    public function handle(Review $review, array $attributes): Review
    {
        $cleaned = (new CleanReviewBody)->handle($attributes['body']);

        return DB::transaction(function () use ($review, $attributes, $cleaned) {
            $review->update([
                'name' => Str::limit($attributes['name'] ?? $review->name, 60, ''),
                'stars' => $attributes['stars'] ?? null,
                'body' => $cleaned['body'],
                'body_text' => $cleaned['body_text'],
                'is_public' => (bool) ($attributes['is_public'] ?? $review->is_public),
                'comments_enabled' => (bool) ($attributes['comments_enabled'] ?? $review->comments_enabled),
                'comments_replies_enabled' => (bool) ($attributes['comments_replies_enabled'] ?? $review->comments_replies_enabled),
            ]);

            return $review->fresh();
        });
    }
}
