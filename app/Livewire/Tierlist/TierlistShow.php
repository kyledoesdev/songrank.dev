<?php

namespace App\Livewire\Tierlist;

use App\Actions\Tierlists\ApplyTierlistShareTags;
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

        if ($this->tierlist->is_public && $this->tierlist->is_complete) {
            (new ApplyTierlistShareTags)->handle($this->tierlist);
        }
    }

    public function render()
    {
        return view('livewire.tierlist.tierlist-show', [
            'tierlist' => $this->tierlist,
        ]);
    }
}
