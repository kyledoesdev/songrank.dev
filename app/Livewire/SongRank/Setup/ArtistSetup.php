<?php

namespace App\Livewire\SongRank\Setup;

use App\Actions\Rankings\StoreArtistRanking;
use App\Actions\Spotify\GetArtistAppearsOnSongs;
use App\Actions\Spotify\GetArtistSongs;
use App\Actions\Spotify\SearchArtists;
use App\Enums\RankingType;
use App\Livewire\SongRank\Concerns\HasRankingForm;
use App\Livewire\SongRank\Concerns\HasSetupFlashErrors;
use App\Livewire\SongRank\Concerns\HasTrackList;
use App\Models\Artist;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ArtistSetup extends Component
{
    use HasRankingForm;
    use HasSetupFlashErrors;
    use HasTrackList;

    public ?Collection $searchedArtists = null;

    public string $searchTerm = '';

    public string $randomArtist = '';

    public ?array $selectedArtist = null;

    public bool $includeFeaturedTracks = false;

    public int $appearsOnCount = 0;

    public function mount(): void
    {
        $this->randomArtist = Artist::query()
            ->whereNotNull('artist_img')
            ->inRandomOrder()
            ->first()?->artist_name ?? '';
    }

    public function render()
    {
        return view('livewire.song-rank.setup.artist-setup');
    }

    public function rankingType(): RankingType
    {
        return RankingType::ARTIST;
    }

    public function search(): void
    {
        if (! $this->ensureCanCreateRanking()) {
            return;
        }

        $this->selectedArtist = null;
        $this->resetTrackList();
        $this->resetFeaturedTracks();

        if ($this->searchTerm === '') {
            $this->invalidSearchTerm();

            return;
        }

        $this->searchedArtists = (new SearchArtists)->handle(Auth::user(), $this->searchTerm);

        if (is_null($this->searchedArtists) || $this->searchedArtists->isEmpty()) {
            $this->noArtistFound($this->searchTerm);
        }
    }

    public function loadArtistSongs(string $artistId): void
    {
        $this->resetTrackList();
        $this->resetFeaturedTracks();

        $this->selectedArtist = collect($this->searchedArtists)->firstWhere('id', $artistId);
        $this->searchedArtists = null;

        $tracks = (new GetArtistSongs)->handle(Auth::user(), $artistId);

        if (is_null($tracks) || $tracks->count() < 2) {
            $this->notEnoughTracks(data_get($this->selectedArtist, 'name', 'this artist'));
            $this->resetSetup();

            return;
        }

        $this->selectedTracks = $tracks;
        $this->appearsOnCount = (new GetArtistAppearsOnSongs)->count(Auth::user(), $artistId);
    }

    /**
     * Fetching an artist's appearances is expensive for prolific guests, so it
     * waits until the toggle first flips on rather than loading with the catalog.
     */
    public function updatedIncludeFeaturedTracks(bool $value): void
    {
        if ($value && ! $this->hasFeaturedTracks()) {
            $this->featuredTracks = (new GetArtistAppearsOnSongs)->handle(
                Auth::user(),
                data_get($this->selectedArtist, 'id', ''),
                collect($this->selectedTracks),
            ) ?? collect();

            if (! $this->hasFeaturedTracks()) {
                $this->includeFeaturedTracks = false;
                $this->featuredTracksUnavailable(data_get($this->selectedArtist, 'name', 'this artist'));
            }
        }

        if (! $value) {
            $this->featuredTracks = collect();
        }

        $this->js('window.hideLoader()');
    }

    // -- Album filtering --

    public function albums(): Collection
    {
        return collect($this->selectedTracks)
            ->groupBy('album_id')
            ->map(function (Collection $tracks) {
                $first = $tracks->first();
                $trackUuids = $tracks->pluck('uuid');
                $removedCount = $trackUuids->intersect($this->removedTrackUuids)->count();

                return [
                    'id' => $first['album_id'],
                    'name' => $first['album_name'],
                    'type' => $first['album_type'],
                    'cover' => $first['cover'],
                    'track_count' => $tracks->count(),
                    'selected_count' => $tracks->count() - $removedCount,
                    'all_selected' => $removedCount === 0,
                    'none_selected' => $removedCount === $tracks->count(),
                ];
            })
            ->sortBy([['type', 'asc'], ['name', 'asc']])
            ->values();
    }

    public function toggleAlbum(string $albumId): void
    {
        $trackUuids = collect($this->selectedTracks)
            ->where('album_id', $albumId)
            ->pluck('uuid');

        $allRemoved = $trackUuids->intersect($this->removedTrackUuids)->count() === $trackUuids->count();

        if ($allRemoved) {
            $this->removedTrackUuids = $this->removedTrackUuids->diff($trackUuids)->values();
            $this->dispatch('tracks-batch-restored', uuids: $trackUuids->values()->all());
        } else {
            $toRemove = $trackUuids->diff($this->removedTrackUuids)->values();
            $this->removedTrackUuids = $this->removedTrackUuids->merge($toRemove)->values();
            $this->dispatch('tracks-batch-removed', uuids: $toRemove->all());
        }
    }

    protected function resetFeaturedTracks(): void
    {
        $this->includeFeaturedTracks = false;
        $this->appearsOnCount = 0;
    }

    // -- Starting the ranking --

    public function beginRanking(): void
    {
        $ranking = (new StoreArtistRanking)->handle(Auth::user(), [
            ...$this->rankingAttributes(),
            'artist' => $this->selectedArtist,
        ]);

        $this->redirect(route('ranking', ['id' => $ranking->getKey()]));
    }

    public function resetSetup(): void
    {
        $this->reset([
            'searchTerm',
            'searchedArtists',
            'selectedArtist',
            'selectedTracks',
            'featuredTracks',
            'removedTrackUuids',
            'includeFeaturedTracks',
            'appearsOnCount',
        ]);

        $this->resetRankingForm();
    }
}
