<?php

namespace App\Livewire\Explorer;

use App\Livewire\Concerns\HasInfiniteFeed;
use App\Models\Ranking;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class RankingsFeed extends Component
{
    use HasInfiniteFeed;

    public function render()
    {
        return view('livewire.explorer.rankings-feed', [
            'totalRankings' => cache()->remember('explore:total-rankings', now()->addDay(), fn () => Ranking::query()->explorableCount()),
        ]);
    }

    #[Computed]
    public function rankings(): Collection
    {
        return Ranking::query()
            ->forExplorePage($this->search)
            ->when(! $this->isFiltered, fn ($query) => $query->limit($this->perPage))
            ->get();
    }

    protected function feedItems(): Collection
    {
        return $this->rankings;
    }
}
