<?php

namespace App\Livewire;

use App\Models\ApplicationDashboard;
use App\Models\LandingPageContent;
use App\Seo\AboutPage;
use Illuminate\View\View;
use Livewire\Component;

class About extends Component
{
    public function mount(): void
    {
        (new AboutPage)->handle();
    }

    public function render(): View
    {
        return view('livewire.about', [
            'aboutPage' => ApplicationDashboard::first()?->about_page,
            'content' => LandingPageContent::cached(),
        ]);
    }
}
