<?php

namespace App\Livewire\Ranking;

use App\Actions\Rankings\DestroyRanking;
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

        abort_unless($this->ranking->canBeEdited(), 404);

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

    public function confirmDestroy(): void
    {
        $this->confirmAction(
            action: 'destroy',
            title: 'Delete this ranking?',
            message: 'This will remove it from your profile and the explore feed. You cannot undo this.',
            confirmText: 'Delete it',
        );
    }

    public function destroy(): void
    {
        (new DestroyRanking)->handle(Auth::user(), $this->ranking);

        session()->flash('success', 'Ranking removed successfully.');

        $this->redirect(route('profile', ['id' => Auth::user()->spotify_id]));
    }
}
