<?php

use App\Models\Review;

/**
 * A review anyone with the flag may open: published and public.
 */
function publicPublishedReview(array $attributes = []): Review
{
    return Review::factory()
        ->published()
        ->public()
        ->create($attributes);
}
