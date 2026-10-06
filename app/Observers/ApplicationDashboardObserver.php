<?php

namespace App\Observers;

use App\Models\ApplicationDashboard;

class ApplicationDashboardObserver
{
    public function saved(ApplicationDashboard $dashboard): void
    {
        cache()->forget(ApplicationDashboard::SEO_TERMS_CACHE_KEY);
    }

    public function deleted(ApplicationDashboard $dashboard): void
    {
        cache()->forget(ApplicationDashboard::SEO_TERMS_CACHE_KEY);
    }
}
