<?php

namespace App\Livewire\SongRank\Setup;

use App\Actions\Rankings\StoreShowRanking;
use App\Actions\Spotify\GetShowEpisodes;
use App\Enums\RankingType;
use App\Livewire\SongRank\Concerns\HasRankingForm;
use App\Livewire\SongRank\Concerns\HasSetupFlashErrors;
use App\Livewire\SongRank\Concerns\HasTrackList;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;

class ShowSetup extends Component
{
    use HasRankingForm;
    use HasSetupFlashErrors;
    use HasTrackList;

    public string $searchTerm = '';

    public array $selectedShow = [];

    public function render()
    {
        return view('livewire.song-rank.setup.show-setup');
    }

    public function rankingType(): RankingType
    {
        return RankingType::SHOW;
    }

    public function search(): void
    {
        if (! $this->ensureCanCreateRanking()) {
            return;
        }

        $this->resetTrackList();
        $this->selectedShow = [];

        if (! $this->isSpotifyShowUrl()) {
            $this->invalidShowUrl();
            $this->searchTerm = '';

            return;
        }

        $showData = (new GetShowEpisodes)->search(Auth::user(), $this->searchTerm);

        if (is_null($showData)) {
            $this->showNotFound($this->searchTerm);
            $this->searchTerm = '';

            return;
        }

        $this->selectedShow = $showData->except('tracks')->toArray();
        $this->selectedTracks = $showData->only('tracks')->first();
    }

    public function beginRanking(): void
    {
        $ranking = (new StoreShowRanking)->handle(Auth::user(), [
            ...$this->rankingAttributes(),
            'show' => $this->selectedShow,
        ]);

        $this->redirect(route('ranking', ['id' => $ranking->getKey()]));
    }

    public function resetSetup(): void
    {
        $this->reset([
            'searchTerm',
            'selectedShow',
            'selectedTracks',
            'removedTrackUuids',
        ]);

        $this->resetRankingForm();
    }

    private function isSpotifyShowUrl(): bool
    {
        return $this->searchTerm !== ''
            && Str::isUrl($this->searchTerm)
            && Str::startsWith($this->searchTerm, 'https://open.spotify.com/show');
    }
}
