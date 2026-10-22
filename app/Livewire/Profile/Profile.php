<?php

namespace App\Livewire\Profile;

use App\Models\Ranking;
use App\Models\Review;
use App\Models\Tierlist;
use App\Models\User;
use App\Seo\ProfilePage;
use Illuminate\Support\Collection;
use Laravel\Head\Facades\Head;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class Profile extends Component
{
    public User $user;

    public function mount(string $id): void
    {
        $this->user = User::where('spotify_id', $id)->firstOrFail();

        Head::title(possessive($this->user->name).' Profile');
        (new ProfilePage)->handle($this->user);
    }

    public function render()
    {
        $hasRankings = $this->rankings->isNotEmpty();
        $hasTierlists = $this->tierlists->isNotEmpty();
        $hasReviews = $this->reviews->isNotEmpty();

        return view('livewire.profile.profile', [
            'name' => possessive($this->user->name),
            'hasRankings' => $hasRankings,
            'hasTierlists' => $hasTierlists,
            'hasReviews' => $hasReviews,
            'showTabs' => collect([$hasRankings, $hasTierlists, $hasReviews])->filter()->count() > 1,
        ]);
    }

    #[Computed]
    #[On('rankings-updated')]
    public function rankings(): Collection
    {
        return Ranking::query()
            ->forProfilePage($this->user)
            ->get();
    }

    #[Computed]
    #[On('reviews-updated')]
    public function reviews(): Collection
    {
        return Review::query()
            ->forProfilePage($this->user)
            ->get();
    }

    #[Computed]
    #[On('tierlists-updated')]
    public function tierlists(): Collection
    {
        return Tierlist::query()
            ->forProfilePage($this->user)
            ->get();
    }
}
