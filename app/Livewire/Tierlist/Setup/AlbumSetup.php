<?php

namespace App\Livewire\Tierlist\Setup;

use App\Actions\Spotify\GetArtistAlbums;
use App\Actions\Spotify\SearchAlbums;
use App\Actions\Spotify\SearchArtists;
use App\Actions\Tierlists\ResolveTierlistEntries;
use App\Enums\TierlistType;
use App\Livewire\Tierlist\Concerns\HasEntryBank;
use App\Livewire\Tierlist\Concerns\HasTierlistFlashErrors;
use App\Livewire\Tierlist\Concerns\HasTierlistForm;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AlbumSetup extends Component
{
    use HasEntryBank;
    use HasTierlistFlashErrors;
    use HasTierlistForm;

    /** 'album' searches records directly; 'artist' imports a discography. */
    public string $mode = 'album';

    public ?array $selectedArtist = null;

    public ?array $artistResults = null;

    public function render()
    {
        return view('livewire.tierlist.setup.album-setup');
    }

    public function tierlistType(): TierlistType
    {
        return TierlistType::ALBUM;
    }

    /**
     * A list built from one artist's discography remembers whose it was. One
     * assembled record by record came from nowhere in particular.
     */
    public function source(): ?Model
    {
        if (is_null($this->selectedArtist)) {
            return null;
        }

        return (new ResolveTierlistEntries)
            ->handle(TierlistType::ARTIST, collect([$this->selectedArtist]))
            ->first();
    }

    public function switchMode(string $mode): void
    {
        $this->mode = $mode;
        $this->searchResults = null;
        $this->artistResults = null;
        $this->reset(['searchTerm', 'selectedArtist']);
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

        if ($this->mode === 'artist') {
            $this->searchForArtist();

            return;
        }

        $this->searchResults = (new SearchAlbums)->handle(Auth::user(), $this->searchTerm);

        if (is_null($this->searchResults) || $this->searchResults->isEmpty()) {
            $this->nothingFound($this->tierlistType(), $this->searchTerm);
        }
    }

    public function loadDiscography(string $artistId): void
    {
        $this->selectedArtist = collect($this->artistResults)->firstWhere('id', $artistId);
        $this->artistResults = null;

        $albums = (new GetArtistAlbums)->handle(Auth::user(), $artistId);

        if (is_null($albums) || $albums->isEmpty()) {
            $this->discographyUnavailable(data_get($this->selectedArtist, 'name', 'this artist'));
            $this->selectedArtist = null;

            return;
        }

        $this->searchResults = $albums;
    }

    public function addDiscography(): void
    {
        $this->addEntries(collect($this->searchResults));
    }

    public function resetSetup(): void
    {
        $this->reset(['searchTerm', 'selectedArtist', 'artistResults']);
        $this->resetBank();
        $this->resetTierlistForm();
    }

    private function searchForArtist(): void
    {
        $this->searchResults = null;
        $this->selectedArtist = null;

        $artists = (new SearchArtists)->handle(Auth::user(), $this->searchTerm);

        if (is_null($artists) || $artists->isEmpty()) {
            $this->nothingFound(TierlistType::ARTIST, $this->searchTerm);

            return;
        }

        $this->artistResults = $artists->all();
    }
}
