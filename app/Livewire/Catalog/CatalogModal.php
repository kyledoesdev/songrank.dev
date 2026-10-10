<?php

namespace App\Livewire\Catalog;

use App\Models\Crate;
use App\Models\CrateItem;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class CatalogModal extends Component
{
    /** Whose catalog this is. */
    public User $user;

    /** The crate being looked inside, or null for the top level. */
    public ?Crate $crate = null;

    public function mount(): void
    {
        abort_unless($this->canBeViewed(), 404);
    }

    public function render()
    {
        return view('livewire.catalog.catalog-modal');
    }

    /**
     * @return Collection<int, Crate>
     */
    #[Computed]
    public function crates(): Collection
    {
        return Crate::query()
            ->forCatalogModal($this->user, $this->crate, Auth::user())
            ->get();
    }

    /**
     * @return Collection<int, CrateItem>
     */
    #[Computed]
    public function items(): Collection
    {
        if (is_null($this->crate)) {
            return collect();
        }

        return CrateItem::query()
            ->forCatalogModal($this->crate, Auth::user())
            ->get();
    }

    /**
     * Checked on every step, so a catalog made private stops opening at once.
     */
    public function showCrate(int $crateId): void
    {
        abort_unless($this->canBeViewed(), 404);

        $this->crate = Crate::query()
            ->ownedBy($this->user)
            ->findOrFail($crateId);
    }

    public function showTopLevel(): void
    {
        $this->crate = null;
    }

    private function canBeViewed(): bool
    {
        return $this->user->hasPublicCatalog() || $this->user->is(Auth::user());
    }
}
