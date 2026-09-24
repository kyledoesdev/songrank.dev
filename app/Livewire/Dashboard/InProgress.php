<?php

namespace App\Livewire\Dashboard;

use App\Models\Ranking;
use App\Models\Tierlist;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Laravel\Pennant\Feature;
use Livewire\Attributes\Computed;
use Livewire\Component;

class InProgress extends Component
{
    public function render()
    {
        return view('livewire.dashboard.in-progress');
    }

    /**
     * @return Collection<int, Ranking>
     */
    #[Computed]
    public function rankings(): Collection
    {
        return Ranking::query()
            ->forDashboard(Auth::user())
            ->inProgress()
            ->get();
    }

    /**
     * @return Collection<int, Tierlist>
     */
    #[Computed]
    public function tierlists(): Collection
    {
        if (Feature::inactive('tierlists')) {
            return collect();
        }

        return Tierlist::query()
            ->forDashboard(Auth::user())
            ->inProgress()
            ->get();
    }

    public function hasAnything(): bool
    {
        return $this->rankings->isNotEmpty() || $this->tierlists->isNotEmpty();
    }
}
