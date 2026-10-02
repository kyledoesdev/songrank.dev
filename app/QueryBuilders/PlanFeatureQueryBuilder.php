<?php

namespace App\QueryBuilders;

use Illuminate\Database\Eloquent\Builder;

class PlanFeatureQueryBuilder extends Builder
{
    /**
     * What a plan includes first, then what it leaves out, then what is
     * coming soon — and within each group, the order set in the admin panel.
     */
    public function forPricingCards(): static
    {
        return $this
            ->orderBy('is_coming_soon')
            ->orderByDesc('is_included')
            ->orderBy('order');
    }
}
