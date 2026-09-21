<?php

namespace App\Livewire;

use Laravel\Pennant\Feature;
use Livewire\Component;

class Explorer extends Component
{
    public function render()
    {
        return view('livewire.explorer', [
            'showTierlists' => Feature::active('tierlists'),
        ]);
    }
}
