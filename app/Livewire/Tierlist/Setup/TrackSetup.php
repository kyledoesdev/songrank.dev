<?php

namespace App\Livewire\Tierlist\Setup;

use App\Actions\Playlists\ResolvePlaylist;
use App\Actions\Spotify\GetArtistSongs;
use App\Actions\Spotify\GetPlaylistTracks;
use App\Actions\Spotify\SearchArtists;
use App\Actions\Spotify\SearchTracks;
use App\Actions\Tierlists\ResolveTierlistEntries;
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

    /** 'track' searches one at a time; 'playlist' imports a whole one; 'artist' imports a discography. */
    public string $mode = 'track';

    public array $selectedPlaylist = [];

    public ?array $selectedArtist = null;

    public ?array $artistResults = null;

    public function render()
    {
        return view('livewire.tierlist.setup.track-setup');
    }

    public function tierlistType(): TierlistType
    {
        return TierlistType::TRACK;
    }

    public function source(): ?Model
    {
        if (filled($this->selectedPlaylist)) {
            return (new ResolvePlaylist)->handle($this->selectedPlaylist);
        }

        if ($this->selectedArtist) {
            return (new ResolveTierlistEntries)
                ->handle(TierlistType::ARTIST, collect([$this->selectedArtist]))
                ->first();
        }

        return null;
    }

    public function switchMode(string $mode): void
    {
        $this->mode = $mode;
        $this->searchResults = null;
        $this->artistResults = null;
        $this->reset(['searchTerm', 'selectedPlaylist', 'selectedArtist']);
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

        if ($this->mode === 'artist') {
            $this->searchForArtist();

            return;
        }

        $this->searchResults = (new SearchTracks)->handle(Auth::user(), $this->searchTerm);

        if (is_null($this->searchResults) || $this->searchResults->isEmpty()) {
            $this->nothingFound($this->tierlistType(), $this->searchTerm);
        }
    }

    public function loadDiscography(string $artistId): void
    {
        $this->selectedArtist = collect($this->artistResults)->firstWhere('id', $artistId);
        $this->artistResults = null;

        $songs = (new GetArtistSongs)->handle(Auth::user(), $artistId);

        if (is_null($songs) || $songs->isEmpty()) {
            $this->discographyUnavailable(data_get($this->selectedArtist, 'name', 'this artist'));
            $this->selectedArtist = null;

            return;
        }

        $this->addEntries(
            $songs->map(fn (array $song) => [
                'id' => $song['id'],
                'name' => $song['name'],
                'cover' => $song['cover'],
                'artist_id' => $this->selectedArtist['id'],
                'artist_name' => $this->selectedArtist['name'],
                'album_name' => $song['album_name'] ?? null,
            ])
        );
    }

    public function resetSetup(): void
    {
        $this->reset(['searchTerm', 'selectedPlaylist', 'selectedArtist', 'artistResults']);
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
