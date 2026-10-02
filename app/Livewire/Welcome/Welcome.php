<?php

namespace App\Livewire\Welcome;

use App\Models\LandingPageContent;
use App\Models\PlanFeature;
use Livewire\Component;

class Welcome extends Component
{
    public function render()
    {
        return view('livewire.welcome.welcome', [
            'content' => LandingPageContent::cached(),
            'planFeatures' => PlanFeature::cached(),
        ]);
    }
}
