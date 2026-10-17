<?php

namespace App\Observers;

use App\Models\Ranking;
use App\Models\Review;
use App\Models\Tierlist;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

/**
 * The explore page caches how many public, finished records of each kind it
 * can show. Any change that could move a record in or out of that count drops
 * the cache, after the commit so it is never refilled from the old rows.
 */
class ExploreCountObserver implements ShouldHandleEventsAfterCommit
{
    public function saved(Ranking|Tierlist|Review $record): void
    {
        cache()->forget($record::EXPLORE_COUNT_CACHE_KEY);
    }

    public function deleted(Ranking|Tierlist|Review $record): void
    {
        cache()->forget($record::EXPLORE_COUNT_CACHE_KEY);
    }
}
