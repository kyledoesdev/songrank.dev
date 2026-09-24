<?php

namespace App\Livewire\Tierlist;

use App\Models\Tierlist;
use Laravel\Head\Facades\Head;
use Livewire\Component;

class TierlistShow extends Component
{
    public Tierlist $tierlist;

    public function mount($id): void
    {
        $this->tierlist = Tierlist::query()
            ->with('user', 'source')
            ->withBoard()
            ->findOrFail($id);

        if (! $this->tierlist->canBeSeen()) {
            abort(404);
        }

        Head::title($this->tierlist->name);
    }

    public function render()
    {
        return view('livewire.tierlist.tierlist-show', [
            'tierlist' => $this->tierlist,
        ]);
    }
}
