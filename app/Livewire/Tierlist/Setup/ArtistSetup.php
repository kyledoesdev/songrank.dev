<?php

namespace App\Livewire\Tierlist\Setup;

use App\Actions\Spotify\SearchArtists;
use App\Enums\TierlistType;
use App\Livewire\Tierlist\Concerns\HasEntryBank;
use App\Livewire\Tierlist\Concerns\HasTierlistFlashErrors;
use App\Livewire\Tierlist\Concerns\HasTierlistForm;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ArtistSetup extends Component
{
    use HasEntryBank;
    use HasTierlistFlashErrors;
    use HasTierlistForm;

    public string $randomArtist = '';

    public function mount(): void
    {
        $this->randomArtist = random_artist();
    }

    public function render()
    {
        return view('livewire.tierlist.setup.artist-setup');
    }

    public function tierlistType(): TierlistType
    {
        return TierlistType::ARTIST;
    }

    /**
     * An artist list is assembled a name at a time, so it has no one source.
     */
    public function source(): ?Model
    {
        return null;
    }

    public function search(): void
    {
        if (! $this->ensureCanCreateTierlist()) {
            return;
        }

        if ($this->searchTerm === '') {
            $this->invalidSearchTerm();

            return;
        }

        $this->searchResults = (new SearchArtists)->handle(Auth::user(), $this->searchTerm);

        if (is_null($this->searchResults) || $this->searchResults->isEmpty()) {
            $this->nothingFound($this->tierlistType(), $this->searchTerm);
        }
    }

    public function resetSetup(): void
    {
        $this->reset(['searchTerm']);
        $this->resetBank();
        $this->resetTierlistForm();
    }
}
