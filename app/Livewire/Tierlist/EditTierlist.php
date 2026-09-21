<?php

namespace App\Livewire\Tierlist;

use App\Actions\Tierlists\UpdateTierlist;
use App\Livewire\Concerns\InteractsWithAlerts;
use App\Livewire\Forms\TierlistForm;
use App\Models\Tierlist;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class EditTierlist extends Component
{
    use InteractsWithAlerts;

    public Tierlist $tierlist;

    public TierlistForm $form;

    public function mount($id): void
    {
        $this->tierlist = Tierlist::query()
            ->withBoard()
            ->findOrFail($id);

        abort_unless($this->tierlist->canBeEdited(), 404);

        $this->form->fill([
            'name' => $this->tierlist->name,
            'is_public' => $this->tierlist->is_public ? '1' : '0',
            'comments_enabled' => $this->tierlist->comments_enabled ? '1' : '0',
            'comments_replies_enabled' => $this->tierlist->comments_replies_enabled ? '1' : '0',
        ]);
    }

    public function render()
    {
        return view('livewire.tierlist.edit-tierlist', [
            'canEditEntries' => Auth::user()->is_pro,
            'tiers' => $this->tierlist->placementTiers(),
            'bank' => $this->tierlist->bank,
        ]);
    }

    public function update(): void
    {
        $this->validate([
            'form.name' => ['required', 'string', 'max:30'],
            'form.is_public' => ['required'],
            'form.comments_enabled' => ['required'],
            'form.comments_replies_enabled' => ['required'],
        ]);

        (new UpdateTierlist)->handle($this->tierlist, [
            'name' => $this->form->name,
            'is_public' => $this->form->is_public === '1' || $this->form->is_public === true,
            'comments_enabled' => $this->form->comments_enabled === '1' || $this->form->comments_enabled === true,
            'comments_replies_enabled' => $this->form->comments_replies_enabled === '1' || $this->form->comments_replies_enabled === true,
        ]);

        $this->js("window.flash({
            title: 'Tier List Updated!',
        })");
    }

    public function confirmDestroy(): void
    {
        $this->confirmAction(
            action: 'destroy',
            title: 'Delete this tier list?',
            message: 'This will remove it from your profile and the explore feed. You cannot undo this.',
            confirmText: 'Delete it',
        );
    }

    public function destroy(): void
    {
        $user = $this->tierlist->user;

        $this->tierlist->delete();

        session()->flash('success', 'Tier list removed successfully.');

        $this->redirect(route('profile', ['id' => $user->spotify_id]));
    }
}
