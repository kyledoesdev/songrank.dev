<?php

namespace App\Livewire\SongRank\Setup;

use App\Actions\Rankings\StorePlaylistRanking;
use App\Actions\Spotify\GetPlaylistTracks;
use App\Enums\RankingType;
use App\Livewire\SongRank\Concerns\HasRankingForm;
use App\Livewire\SongRank\Concerns\HasSetupFlashErrors;
use App\Livewire\SongRank\Concerns\HasTrackList;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;

class PlaylistSetup extends Component
{
    use HasRankingForm;
    use HasSetupFlashErrors;
    use HasTrackList;

    public string $searchTerm = '';

    public array $selectedPlaylist = [];

    public function render()
    {
        return view('livewire.song-rank.setup.playlist-setup');
    }

    public function rankingType(): RankingType
    {
        return RankingType::PLAYLIST;
    }

    public function search(): void
    {
        if (! $this->ensureCanCreateRanking()) {
            return;
        }

        $this->resetTrackList();
        $this->selectedPlaylist = [];

        if (! $this->isSpotifyPlaylistUrl()) {
            $this->invalidPlaylistUrl();
            $this->searchTerm = '';

            return;
        }

        $playlistData = (new GetPlaylistTracks)->search(Auth::user(), $this->searchTerm);

        if (is_null($playlistData)) {
            $this->playlistNotFound($this->searchTerm);
            $this->searchTerm = '';

            return;
        }

        $this->selectedPlaylist = $playlistData->except('tracks')->toArray();
        $this->selectedTracks = $playlistData->only('tracks')->first();

        if (! empty($playlistData['deleted_tracks'])) {
            $names = collect($playlistData['deleted_tracks'])
                ->map(fn ($name) => "• {$name}")
                ->implode(PHP_EOL);

            $this->acknowledgeDeletedTracks($names);
        }
    }

    public function beginRanking(): void
    {
        $ranking = (new StorePlaylistRanking)->handle(Auth::user(), [
            ...$this->rankingAttributes(),
            'playlist' => $this->selectedPlaylist,
        ]);

        $this->redirect(route('ranking', ['id' => $ranking->getKey()]));
    }

    public function resetSetup(): void
    {
        $this->reset([
            'searchTerm',
            'selectedPlaylist',
            'selectedTracks',
            'removedTrackUuids',
        ]);

        $this->resetRankingForm();
    }

    private function isSpotifyPlaylistUrl(): bool
    {
        return $this->searchTerm !== ''
            && Str::isUrl($this->searchTerm)
            && Str::startsWith($this->searchTerm, 'https://open.spotify.com/playlist');
    }
}
