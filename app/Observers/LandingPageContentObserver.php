<?php

namespace App\Observers;

use App\Models\LandingPageContent;

class LandingPageContentObserver
{
    public function saved(LandingPageContent $content): void
    {
        cache()->forget(LandingPageContent::CACHE_KEY);
    }

    public function deleted(LandingPageContent $content): void
    {
        cache()->forget(LandingPageContent::CACHE_KEY);
    }
}
