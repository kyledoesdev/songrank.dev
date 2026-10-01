<?php

namespace App\Livewire\Reviews\Setup;

use App\Actions\Spotify\SearchAlbums;
use App\Enums\ReviewType;
use App\Livewire\Reviews\Concerns\HasReviewForm;
use App\Livewire\Reviews\Concerns\HasReviewSubject;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AlbumSetup extends Component
{
    use HasReviewForm;
    use HasReviewSubject;

    public function render()
    {
        return view('livewire.reviews.setup.album-setup', ['type' => $this->reviewType()]);
    }

    public function reviewType(): ReviewType
    {
        return ReviewType::ALBUM;
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

        $this->searchResults = (new SearchAlbums)->handle(Auth::user(), $this->searchTerm)?->all();

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
