<?php

namespace App\Observers;

use App\Models\PlanFeature;

class PlanFeatureObserver
{
    public function saved(PlanFeature $feature): void
    {
        cache()->forget(PlanFeature::CACHE_KEY);
    }

    public function deleted(PlanFeature $feature): void
    {
        cache()->forget(PlanFeature::CACHE_KEY);
    }
}
