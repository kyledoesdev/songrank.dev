<?php

namespace App\Livewire\Welcome;

use App\Models\LandingPageContent;
use App\Models\PlanFeature;
use App\Seo\WelcomePage;
use Livewire\Component;

class Welcome extends Component
{
    public function mount(): void
    {
        (new WelcomePage)->handle();
    }

    public function render()
    {
        return view('livewire.welcome.welcome', [
            'content' => LandingPageContent::cached(),
            'planFeatures' => PlanFeature::cached(),
        ]);
    }
}
