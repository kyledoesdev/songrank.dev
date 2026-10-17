<?php

namespace App\Livewire\Catalog;

use App\Actions\Catalog\DestroyCrate;
use App\Actions\Catalog\MoveCrate;
use App\Actions\Catalog\UpdateCrate;
use App\Livewire\Concerns\InteractsWithAlerts;
use App\Livewire\Forms\CrateForm;
use App\Models\Crate;
use App\Models\CrateIcon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class CrateSettings extends Component
{
    use InteractsWithAlerts;

    public Crate $crate;

    /**
     * Set on the crate's own page, which deleting it would leave pointing at
     * nothing, so it sends the viewer up a level instead.
     */
    public bool $redirectOnDelete = false;

    public bool $open = false;

    public CrateForm $form;

    /** "top", or the id of the crate to move inside. */
    public string $destination = '';

    public function mount(): void
    {
        abort_unless($this->crate->canBeEdited(), 403);
    }

    public function render()
    {
        return view('livewire.catalog.crate-settings');
    }

    public function openSettings(): void
    {
        $this->form->fill($this->crate->only('name', 'color', 'crate_icon_id'));
        $this->resetValidation();
        $this->reset('destination');

        $this->open = true;
    }

    public function close(): void
    {
        $this->reset('open', 'destination');
    }

    /**
     * @return Collection<int, CrateIcon>
     */
    #[Computed]
    public function icons(): Collection
    {
        return CrateIcon::query()
            ->active()
            ->ordered()
            ->get();
    }

    public function iconIsLocked(CrateIcon $icon): bool
    {
        return $icon->is_pro
            && ! Auth::user()->is_pro
            && $icon->getKey() !== $this->crate->crate_icon_id;
    }

    /**
     * @return Collection<int, Crate>
     */
    #[Computed]
    public function destinations(): Collection
    {
        return Crate::query()
            ->destinationsFor($this->crate, Auth::user())
            ->get();
    }

    public function save(): void
    {
        $this->form->validate();

        $saved = (new UpdateCrate)->handle($this->crate, $this->form->only('name', 'color', 'crate_icon_id'));

        if (! $saved) {
            $this->flash(
                title: 'That icon is part of Pro.',
                message: 'Pick another, or go Pro to use every icon.',
                icon: 'error',
            );

            return;
        }

        $this->close();

        $this->dispatch('crates-updated');
    }

    public function move(): void
    {
        abort_unless(Auth::user()->canArrangeCrates(), 403);

        $this->validate(['destination' => ['required', 'string']]);

        $parent = $this->destination !== 'top'
            ? Crate::query()->ownedBy(Auth::user())->findOrFail($this->destination)
            : null;

        if (! (new MoveCrate)->handle(Auth::user(), $this->crate, $parent)) {
            $this->flash(
                title: 'That crate cannot go there.',
                message: 'A crate cannot go inside itself, or anywhere that would leave it deeper than crates go.',
                icon: 'error',
            );

            return;
        }

        $this->close();

        $this->dispatch('crates-updated');
    }

    public function confirmDestroy(): void
    {
        $this->confirmAction(
            action: 'destroy',
            title: 'Delete '.$this->crate->name.'?',
            message: 'Your rankings, tier lists and reviews will not be deleted.',
            confirmText: 'Delete it',
            destructive: true,
        );
    }

    public function destroy(): void
    {
        $parentId = $this->crate->parent_id;

        (new DestroyCrate)->handle($this->crate);

        if ($this->redirectOnDelete) {
            $this->redirect($parentId ? route('catalog.crate', ['id' => $parentId]) : route('catalog'));

            return;
        }

        $this->dispatch('crates-updated');
    }
}
