<?php

namespace App\Livewire\Catalog\Concerns;

use App\Actions\Catalog\ReorderCrate;
use App\Actions\Catalog\StoreCrate;
use App\Enums\CatalogSort;
use App\Livewire\Concerns\InteractsWithAlerts;
use App\Models\Crate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

/**
 * One list of crates, the top level on the catalog or the crates inside the
 * one being viewed, with a way to add to it and, on Pro, to order it by hand.
 */
trait HasCrateList
{
    use InteractsWithAlerts;

    public bool $creatingCrate = false;

    public string $newCrateName = '';

    public string $randomArtist = '';

    /**
     * @return Collection<int, Crate>
     */
    #[Computed]
    #[On('crates-updated')]
    public function crates(): Collection
    {
        return Crate::query()
            ->forCatalogPage(Auth::user(), $this->parentCrate())
            ->get();
    }

    public function canAddCrate(): bool
    {
        $parent = $this->parentCrate();

        return Auth::user()->canCreateCrate()
            && (is_null($parent) || Auth::user()->canNestIn($parent));
    }

    public function cratesAreSortable(): bool
    {
        return Auth::user()->catalogSort() === CatalogSort::CUSTOM;
    }

    public function startCreatingCrate(): void
    {
        $this->resetValidation();
        $this->reset('newCrateName');

        $this->randomArtist = random_artist();
        $this->creatingCrate = true;
    }

    public function cancelCreatingCrate(): void
    {
        $this->reset('creatingCrate', 'newCrateName');
    }

    public function createCrate(): void
    {
        $this->validate(
            ['newCrateName' => ['required', 'string', 'max:40']],
            attributes: ['newCrateName' => 'name'],
        );

        $crate = (new StoreCrate)->handle(Auth::user(), ['name' => $this->newCrateName], $this->parentCrate());

        if (is_null($crate)) {
            $this->flashNoRoomForCrate();

            return;
        }

        $this->cancelCreatingCrate();

        unset($this->crates);
    }

    /**
     * wire:sort's handler: the crate dragged and the slot it landed in.
     */
    public function reorderCrate(int $crateId, int $position): void
    {
        abort_unless(Auth::user()->canArrangeCrates(), 403);

        $crate = Crate::query()
            ->ownedBy(Auth::user())
            ->inside($this->parentCrate())
            ->findOrFail($crateId);

        (new ReorderCrate)->handle(Auth::user(), $crate, $position);
    }

    /**
     * The crate this list sits inside. The catalog keeps the top level, and a
     * crate's page lists what is inside it instead.
     */
    protected function parentCrate(): ?Crate
    {
        return null;
    }

    /**
     * The backstop for a page left open while the account ran out of crates,
     * or lost the Pro that let it nest them.
     */
    private function flashNoRoomForCrate(): void
    {
        if (! Auth::user()->canCreateCrate()) {
            $this->flash(
                title: 'You have used all your crates.',
                message: 'Free accounts can keep '.Auth::user()->crateLimit().' crates. Delete one, or go Pro for as many as you like.',
                icon: 'error',
            );

            return;
        }

        $this->flash(
            title: 'This crate cannot hold another.',
            message: Auth::user()->is_pro
                ? 'Crates can only go so many levels deep, and this one is as deep as they get.'
                : 'Putting crates inside crates is part of Pro.',
            icon: 'error',
        );
    }
}
