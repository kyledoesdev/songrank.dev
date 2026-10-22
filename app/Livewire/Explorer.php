<?php

namespace App\Livewire;

use App\Seo\ExplorePage;
use Livewire\Component;

class Explorer extends Component
{
    public function mount(): void
    {
        (new ExplorePage)->handle();
    }

    public function render()
    {
        return view('livewire.explorer');
    }
}
