<?php

namespace App\Livewire\Catalog;

use App\Actions\Catalog\AddCrateItem;
use App\Actions\Catalog\RemoveCrateItem;
use App\Contracts\CrateEntity;
use App\Enums\CrateEntityType;
use App\Models\Crate;
use App\Models\CrateItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

class AddToCrateModal extends Component
{
    use WithoutUrlPagination;
    use WithPagination;

    private const int PER_PAGE = 8;

    private const string PAGE_NAME = 'add-items';

    public Crate $crate;

    public CrateEntityType $tab = CrateEntityType::RANKING;

    public string $search = '';

    public function mount(): void
    {
        abort_unless($this->crate->canBeEdited(), 403);
    }

    public function render()
    {
        return view('livewire.catalog.add-to-crate-modal');
    }

    public function updatedTab(): void
    {
        $this->resetPage(self::PAGE_NAME);
    }

    public function updatedSearch(): void
    {
        $this->resetPage(self::PAGE_NAME);
    }

    /**
     * @return LengthAwarePaginator<int, CrateEntity>
     */
    #[Computed]
    public function results(): LengthAwarePaginator
    {
        return $this->tab->query()
            ->forAddItemsModal(Auth::user(), $this->search)
            ->paginate(self::PER_PAGE, pageName: self::PAGE_NAME);
    }

    /**
     * @return Collection<int, CrateItem>
     */
    #[Computed]
    public function filed(): Collection
    {
        return $this->crate->items()->get(['entity_type', 'entity_id']);
    }

    public function isFiled(CrateEntity $entity): bool
    {
        return $this->filed->contains(fn (CrateItem $item) => $item->holds($entity));
    }

    /**
     * Only finds what the modal could have listed, so nobody files a record
     * that is not theirs.
     */
    public function toggle(int $id): void
    {
        $entity = $this->tab->query()
            ->forAddItemsModal(Auth::user())
            ->findOrFail($id);

        $item = $this->crate->items()->whereEntity($entity)->first();

        if ($item) {
            (new RemoveCrateItem)->handle($item);
        } else {
            (new AddCrateItem)->handle($this->crate, $entity);
        }

        unset($this->filed);

        $this->dispatch('crate-items-updated');
    }
}
