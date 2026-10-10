<?php

namespace App\Livewire\Catalog;

use App\Actions\Catalog\StoreCrate;
use App\Actions\Catalog\SyncEntityCrates;
use App\Contracts\CrateEntity;
use App\Livewire\Concerns\InteractsWithAlerts;
use App\Models\Crate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Modelable;
use Livewire\Component;

class CratePicker extends Component
{
    use InteractsWithAlerts;

    /**
     * The record being filed. A setup has none yet, so it binds the ticked
     * crates with wire:model and files the record once it exists.
     */
    public ?CrateEntity $entity = null;

    /** @var list<int|string> */
    #[Modelable]
    public array $crateIds = [];

    public string $newCrateName = '';

    public function mount(): void
    {
        if (is_null($this->entity)) {
            return;
        }

        abort_unless($this->entity->canBeEdited(), 403);

        $this->crateIds = $this->entity->crateItems()->pluck('crate_id')->all();
    }

    public function render()
    {
        return view('livewire.catalog.crate-picker');
    }

    /**
     * @return Collection<int, Crate>
     */
    #[Computed]
    public function crates(): Collection
    {
        return Crate::query()
            ->forPicker(Auth::user())
            ->get();
    }

    /**
     * @return Collection<int, Crate>
     */
    public function childrenOf(?Crate $parent = null): Collection
    {
        return $this->crates->where('parent_id', $parent?->getKey())->values();
    }

    public function updatedCrateIds(): void
    {
        $this->file();
    }

    public function createCrate(): void
    {
        $this->validate(
            ['newCrateName' => ['required', 'string', 'max:40']],
            attributes: ['newCrateName' => 'name'],
        );

        $crate = (new StoreCrate)->handle(Auth::user(), ['name' => $this->newCrateName]);

        if (is_null($crate)) {
            $this->flash(
                title: 'You have used all your crates.',
                message: 'Free accounts can keep '.Auth::user()->crateLimit().' crates. Delete one, or go Pro for as many as you like.',
                icon: 'error',
            );

            return;
        }

        $this->crateIds[] = $crate->getKey();
        $this->reset('newCrateName');

        unset($this->crates);

        $this->file();
    }

    /**
     * A record that exists is filed the moment a box is ticked. A setup's is
     * filed by the setup, once it has been created.
     */
    private function file(): void
    {
        if ($this->entity) {
            (new SyncEntityCrates)->handle(Auth::user(), $this->entity, $this->crateIds);
        }
    }
}
