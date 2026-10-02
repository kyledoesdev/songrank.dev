<?php

namespace App\Livewire\Dashboard;

use App\Models\Ranking;
use App\Models\Review;
use App\Models\Tierlist;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class InProgress extends Component
{
    use WithPagination;

    private const int PER_PAGE = 6;

    private const string PAGE_NAME = 'in-progress';

    public function render()
    {
        return view('livewire.dashboard.in-progress');
    }

    /**
     * @return Collection<int, Ranking>
     */
    #[Computed]
    public function rankings(): Collection
    {
        return Ranking::query()
            ->forDashboard(Auth::user())
            ->inProgress()
            ->get();
    }

    /**
     * @return Collection<int, Tierlist>
     */
    #[Computed]
    public function tierlists(): Collection
    {
        return Tierlist::query()
            ->forDashboard(Auth::user())
            ->inProgress()
            ->get();
    }

    /**
     * Unpublished drafts: the review equivalent of an unfinished board.
     *
     * @return Collection<int, Review>
     */
    #[Computed]
    public function reviews(): Collection
    {
        return Review::query()
            ->forDashboard(Auth::user())
            ->drafts()
            ->get();
    }

    /**
     * Every unfinished record in one list, most recently touched first, so a
     * page of six is the six things somebody was most likely in the middle of.
     *
     * @return LengthAwarePaginator<int, Ranking|Tierlist|Review>
     */
    #[Computed]
    public function items(): LengthAwarePaginator
    {
        $items = collect()
            ->concat($this->rankings)
            ->concat($this->tierlists)
            ->concat($this->reviews)
            ->sortByDesc(fn (Model $item) => $item->updated_at)
            ->values();

        $page = $this->getPage(self::PAGE_NAME);

        return new LengthAwarePaginator(
            $items->forPage($page, self::PER_PAGE)->values(),
            $items->count(),
            self::PER_PAGE,
            $page,
            ['pageName' => self::PAGE_NAME],
        );
    }

    /**
     * @return array{0: string, 1: array<string, Ranking|Tierlist|Review>}
     */
    public function card(Ranking|Tierlist|Review $item): array
    {
        return match ($item::class) {
            Ranking::class => ['ranking.card', ['ranking' => $item]],
            Tierlist::class => ['tierlist.card', ['tierlist' => $item]],
            Review::class => ['reviews.card', ['review' => $item]],
        };
    }

    public function hasAnything(): bool
    {
        return $this->rankings->isNotEmpty()
            || $this->tierlists->isNotEmpty()
            || $this->reviews->isNotEmpty();
    }
}
