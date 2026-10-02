<?php

namespace App\Actions\Reviews;

use App\Models\Review;
use Illuminate\Support\Facades\DB;

final class DestroyReview
{
    public function handle(Review $review): void
    {
        if ($review->is_public && $review->is_published) {
            cache()->forget('explore:total-reviews');
        }

        DB::transaction(fn () => $review->delete());
    }
}
