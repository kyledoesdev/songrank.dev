<?php

namespace App\Livewire\Explorer;

use App\Models\Ranking;
use Livewire\Attributes\Computed;
use Livewire\Component;

class RankingsFeed extends Component
{
    public ?string $search = null;

    public int $perPage = 12;

    public function render()
    {
        return view('livewire.explorer.rankings-feed', [
            'totalRankings' => cache()->remember('explore:total-rankings', now()->addDay(), fn () => Ranking::query()->explorableCount()),
        ]);
    }

    #[Computed]
    public function rankings()
    {
        return Ranking::query()
            ->forExplorePage($this->search)
            ->when(! $this->isFiltered, fn ($query) => $query->limit($this->perPage))
            ->get();
    }

    #[Computed]
    public function hasMorePages(): bool
    {
        return $this->rankings->count() === $this->perPage;
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
