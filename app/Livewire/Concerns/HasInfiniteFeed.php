<?php

namespace App\Livewire\Concerns;

use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

/**
 * The scroll-to-load behaviour the explore feeds share. A filtered feed is not
 * paged: a search is expected to return everything it found.
 */
trait HasInfiniteFeed
{
    public ?string $search = null;

    public int $perPage = 12;

    /**
     * The rows the implementing feed is currently showing.
     *
     * @return Collection<int, mixed>
     */
    abstract protected function feedItems(): Collection;

    #[Computed]
    public function hasMorePages(): bool
    {
        return $this->feedItems()->count() === $this->perPage;
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
