<?php

namespace App\Livewire\Reviews\Setup;

use App\Actions\Spotify\SearchArtists;
use App\Enums\ReviewType;
use App\Livewire\Reviews\Concerns\HasReviewForm;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ArtistSetup extends Component
{
    use HasReviewForm;

    public function render()
    {
        return view('livewire.reviews.setup.artist-setup', ['type' => $this->reviewType()]);
    }

    public function reviewType(): ReviewType
    {
        return ReviewType::ARTIST;
    }

    public function search(): void
    {
        if (! $this->ensureCanCreateReview()) {
            return;
        }

        if ($this->searchTerm === '') {
            $this->invalidSearchTerm();

            return;
        }

        $this->searchResults = (new SearchArtists)->handle(Auth::user(), $this->searchTerm)?->all();

        if (blank($this->searchResults)) {
            $this->nothingFound($this->reviewType(), $this->searchTerm);
        }
    }

    public function resetSetup(): void
    {
        $this->reset(['searchTerm', 'searchResults', 'subject']);
        $this->resetReviewForm();
    }
}
