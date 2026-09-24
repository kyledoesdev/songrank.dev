<?php

namespace App\Livewire\Tierlist;

use App\Livewire\Concerns\InteractsWithAlerts;
use App\Models\Tierlist;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Card extends Component
{
    use InteractsWithAlerts;

    public Tierlist $tierlist;

    public function render()
    {
        return view('livewire.tierlist.card', ['tierlist' => $this->tierlist]);
    }

    public function destroy(): void
    {
        abort_unless(Auth::check() && $this->tierlist->user_id == Auth::id(), 403);

        $this->tierlist->delete();

        $this->dispatch('tierlists-updated');

        $this->flash('Tier List Deleted!');
    }
}
