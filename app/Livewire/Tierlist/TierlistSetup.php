<?php

namespace App\Livewire\Tierlist;

use App\Enums\TierlistType;
use App\Livewire\Tierlist\Setup\AlbumSetup;
use App\Livewire\Tierlist\Setup\ArtistSetup;
use App\Livewire\Tierlist\Setup\TrackSetup;
use Livewire\Attributes\On;
use Livewire\Component;

class TierlistSetup extends Component
{
    public TierlistType $type = TierlistType::ARTIST;

    /**
     * The dashboard links straight to a type, so the chooser opens where the
     * click pointed rather than always on artists.
     */
    public function mount(): void
    {
        $this->type = TierlistType::tryFrom(request()->string('type')->toString()) ?? $this->type;
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
