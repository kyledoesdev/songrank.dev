<?php

namespace App\Models\Concerns;

trait HasReviewAllowance
{
    /**
     * How many reviews this account may own in total. `null` is unlimited.
     *
     * Unlike tier lists, the allowance is not per type: five reviews is five
     * reviews whether they are all albums or one of each.
     */
    public function reviewLimit(): ?int
    {
        return $this->is_pro
            ? config('billing.review_limits.pro')
            : config('billing.review_limits.free');
    }

    public function canCreateReview(): bool
    {
        $limit = $this->reviewLimit();

        if (is_null($limit)) {
            return true;
        }

        return $this->reviews()->count() < $limit;
    }
}
