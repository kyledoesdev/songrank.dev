<?php

namespace App\Livewire\Tierlist;

use App\Enums\TierlistType;
use App\Livewire\Tierlist\Setup\AlbumSetup;
use App\Livewire\Tierlist\Setup\ArtistSetup;
use App\Livewire\Tierlist\Setup\TrackSetup;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

class TierlistSetup extends Component
{
    #[Url(nullable: true)]
    public ?TierlistType $type = null;

    public function mount(): void
    {
        $this->type ??= TierlistType::ARTIST;
    }

    public function render()
    {
        return view('livewire.tierlist.tierlist-setup');
    }

    #[On('switch-tierlist-type')]
    public function switchType(string $type): void
    {
        $this->type = TierlistType::from($type);
    }

    public function setupComponent(): string
    {
        return match ($this->type) {
            TierlistType::ARTIST => ArtistSetup::class,
            TierlistType::ALBUM => AlbumSetup::class,
            TierlistType::TRACK => TrackSetup::class,
        };
    }
}
