<?php

namespace App\Livewire\Explorer;

use App\Models\Tierlist;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TierlistsFeed extends Component
{
    public ?string $search = null;

    public int $perPage = 12;

    public function render()
    {
        return view('livewire.explorer.tierlists-feed', [
            'totalTierlists' => cache()->remember('explore:total-tierlists', now()->addDay(), fn () => Tierlist::query()->explorableCount()),
        ]);
    }

    #[Computed]
    public function tierlists()
    {
        return Tierlist::query()
            ->forExplorePage($this->search)
            ->when(! $this->isFiltered, fn ($query) => $query->limit($this->perPage))
            ->get();
    }

    #[Computed]
    public function hasMorePages(): bool
    {
        return $this->tierlists->count() === $this->perPage;
    }

    #[Computed]
    public function isFiltered(): bool
    {
        return filled($this->search);
    }

    public function loadMore(): void
    {
        $this->perPage += 12;
    }

    public function performSearch(): void
    {
        $this->perPage = 12;
    }

    public function resetSearch(): void
    {
        $this->search = null;
        $this->perPage = 12;
    }
}
