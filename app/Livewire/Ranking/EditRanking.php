<?php

namespace App\Livewire\Ranking;

use App\Actions\Rankings\UpdateRanking;
use App\Livewire\Concerns\InteractsWithAlerts;
use App\Livewire\Forms\RankingForm;
use App\Models\Ranking;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class EditRanking extends Component
{
    use InteractsWithAlerts;

    public ?Ranking $ranking;

    public RankingForm $form;

    public function mount($id)
    {
        $this->ranking = Ranking::query()
            ->with('songs')
            ->findOrFail($id);

        if (! $this->ranking->canBeSeen()) {
            abort(404);
        }

        $this->form->fill([
            'name' => $this->ranking->name,
            'is_public' => $this->ranking->is_public ? '1' : '0',
            'comments_enabled' => $this->ranking->comments_enabled ? '1' : '0',
            'comments_replies_enabled' => $this->ranking->comments_replies_enabled ? '1' : '0',
        ]);
    }

    public function render()
    {
        return view('livewire.ranking.edit-ranking');
    }

    public function update(): void
    {
        $this->form->validate();

        (new UpdateRanking)->handle(Auth::user(), $this->ranking, $this->form);

        $this->flash('Ranking Updated!');
    }
}
