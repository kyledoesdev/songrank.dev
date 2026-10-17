<?php

namespace App\Livewire\Explorer;

use App\Livewire\Concerns\HasInfiniteFeed;
use App\Models\Tierlist;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TierlistsFeed extends Component
{
    use HasInfiniteFeed;

    public function render()
    {
        return view('livewire.explorer.tierlists-feed', [
            'totalTierlists' => cache()->remember(Tierlist::EXPLORE_COUNT_CACHE_KEY, now()->addDay(), fn () => Tierlist::query()->explorableCount()),
        ]);
    }

    #[Computed]
    public function tierlists(): Collection
    {
        return Tierlist::query()
            ->forExplorePage($this->search)
            ->when(! $this->isFiltered, fn ($query) => $query->limit($this->perPage))
            ->get();
    }

    protected function feedItems(): Collection
    {
        return $this->tierlists;
    }
}
