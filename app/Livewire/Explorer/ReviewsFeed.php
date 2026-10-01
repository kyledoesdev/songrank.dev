<?php

namespace App\Livewire\Explorer;

use App\Enums\ReviewType;
use App\Livewire\Concerns\HasInfiniteFeed;
use App\Models\Review;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ReviewsFeed extends Component
{
    use HasInfiniteFeed;

    /** Null is "every type", which is the default view. */
    public ?string $type = null;

    public function render()
    {
        return view('livewire.explorer.reviews-feed', [
            'totalReviews' => cache()->remember('explore:total-reviews', now()->addDay(), fn () => Review::query()->explorableCount()),
        ]);
    }

    #[Computed]
    public function reviews(): Collection
    {
        return Review::query()
            ->forExplorePage($this->search, $this->selectedType())
            ->when(! $this->isFiltered, fn ($query) => $query->limit($this->perPage))
            ->get();
    }

    #[Computed]
    public function isFiltered(): bool
    {
        return filled($this->search) || filled($this->type);
    }

    public function filterByType(?string $type): void
    {
        $this->type = $this->type === $type ? null : $type;
        $this->perPage = 12;
    }

    public function resetSearch(): void
    {
        $this->search = null;
        $this->type = null;
        $this->perPage = 12;
    }

    protected function feedItems(): Collection
    {
        return $this->reviews;
    }

    private function selectedType(): ?ReviewType
    {
        return filled($this->type) ? ReviewType::tryFrom($this->type) : null;
    }
}
