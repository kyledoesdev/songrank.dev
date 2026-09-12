<?php

namespace App\Livewire\Tierlist\Setup;

use App\Actions\Playlists\ResolvePlaylist;
use App\Actions\Spotify\GetPlaylistTracks;
use App\Actions\Spotify\SearchTracks;
use App\Enums\TierlistType;
use App\Livewire\Tierlist\Concerns\HasEntryBank;
use App\Livewire\Tierlist\Concerns\HasTierlistFlashErrors;
use App\Livewire\Tierlist\Concerns\HasTierlistForm;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;

class TrackSetup extends Component
{
    use HasEntryBank;
    use HasTierlistFlashErrors;
    use HasTierlistForm;

    /** 'track' searches songs one at a time; 'playlist' imports a whole one. */
    public string $mode = 'track';

    public array $selectedPlaylist = [];

    public function render()
    {
        return view('livewire.tierlist.setup.track-setup');
    }

    public function tierlistType(): TierlistType
    {
        return TierlistType::TRACK;
    }

    /**
     * A list poured out of a playlist remembers which one. One assembled track
     * by track came from nowhere in particular.
     */
    public function source(): ?Model
    {
        if (empty($this->selectedPlaylist)) {
            return null;
        }

        return (new ResolvePlaylist)->handle($this->selectedPlaylist);
    }

    public function switchMode(string $mode): void
    {
        $this->mode = $mode;
        $this->searchResults = null;
        $this->reset(['searchTerm', 'selectedPlaylist']);
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

        if ($this->mode === 'playlist') {
            $this->importPlaylist();

            return;
        }

        $this->searchResults = (new SearchTracks)->handle(Auth::user(), $this->searchTerm);

        if (is_null($this->searchResults) || $this->searchResults->isEmpty()) {
            $this->nothingFound($this->tierlistType(), $this->searchTerm);
        }
    }

    public function resetSetup(): void
    {
        $this->reset(['searchTerm', 'selectedPlaylist']);
        $this->resetBank();
        $this->resetTierlistForm();
    }

    /**
     * A playlist import goes straight onto the board rather than into a result
     * list — picking through a hundred tracks one at a time is not a flow.
     */
    private function importPlaylist(): void
    {
        $this->searchResults = null;
        $this->selectedPlaylist = [];

        if (! Str::contains($this->searchTerm, 'open.spotify.com/playlist/')) {
            $this->invalidPlaylistUrl();
            $this->searchTerm = '';

            return;
        }

        $playlist = (new GetPlaylistTracks)->search(Auth::user(), $this->searchTerm);

        if (is_null($playlist)) {
            $this->playlistNotFound($this->searchTerm);
            $this->searchTerm = '';

            return;
        }

        $this->selectedPlaylist = $playlist->except('tracks')->toArray();

        $this->addEntries(
            collect($playlist->get('tracks'))->map(fn (array $track) => [
                'id' => $track['id'],
                'name' => $track['name'],
                'cover' => $track['cover'],
                'artist_id' => $track['artist_id'],
                'artist_name' => $track['artist_name'],
                'album_name' => null,
            ])
        );
    }
}
