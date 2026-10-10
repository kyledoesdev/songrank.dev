<?php

namespace App\Livewire\Catalog;

use App\Actions\Catalog\MoveCrateItem;
use App\Actions\Catalog\RemoveCrateItem;
use App\Livewire\Catalog\Concerns\HasCrateList;
use App\Models\Crate;
use App\Models\CrateItem;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Head\Facades\Head;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class CrateShow extends Component
{
    use HasCrateList;

    public Crate $crate;

    public function mount($id): void
    {
        $this->crate = Crate::query()->findOrFail($id);

        abort_unless($this->crate->canBeSeen(), 404);

        Head::title($this->crate->name);
    }

    public function render()
    {
        return view('livewire.catalog.crate-show');
    }

    /**
     * @return Collection<int, CrateItem>
     */
    #[Computed]
    #[On('crate-items-updated')]
    public function items(): Collection
    {
        return CrateItem::query()
            ->forCratePage($this->crate)
            ->get();
    }

    /**
     * wire:sort's handler: the item dragged and the slot it landed in.
     */
    public function moveItem(int $itemId, int $position): void
    {
        abort_unless($this->crate->canBeEdited(), 403);

        (new MoveCrateItem)->handle($this->crate, ['item_id' => $itemId, 'position' => $position]);
    }

    public function removeItem(int $itemId): void
    {
        abort_unless($this->crate->canBeEdited(), 403);

        (new RemoveCrateItem)->handle($this->crate->items()->findOrFail($itemId));
    }

    protected function parentCrate(): ?Crate
    {
        return $this->crate;
    }
}
