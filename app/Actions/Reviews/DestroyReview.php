<?php

namespace App\Actions\Reviews;

use App\Models\Review;
use Illuminate\Support\Facades\DB;

final class DestroyReview
{
    public function handle(Review $review): void
    {
        DB::transaction(fn () => $review->delete());
    }
}
