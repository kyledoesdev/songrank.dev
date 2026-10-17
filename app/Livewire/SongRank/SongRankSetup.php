<?php

namespace App\Livewire\SongRank;

use App\Enums\RankingType;
use App\Livewire\SongRank\Setup\ArtistSetup;
use App\Livewire\SongRank\Setup\PlaylistSetup;
use App\Livewire\SongRank\Setup\ShowSetup;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

class SongRankSetup extends Component
{
    #[Url(nullable: true)]
    public ?RankingType $type = null;

    public function mount(): void
    {
        $this->type ??= RankingType::ARTIST;
    }

    public function render()
    {
        return view('livewire.song-rank.song-rank-setup');
    }

    #[On('switch-ranking-type')]
    public function switchType(string $type): void
    {
        $this->type = RankingType::from($type);
    }

    public function setupComponent(): string
    {
        return match ($this->type) {
            RankingType::ARTIST => ArtistSetup::class,
            RankingType::PLAYLIST => PlaylistSetup::class,
            RankingType::SHOW => ShowSetup::class,
        };
    }
}
