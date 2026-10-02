<?php

namespace App\Livewire\Reviews;

use App\Enums\ReviewType;
use App\Livewire\Reviews\Setup\AlbumSetup;
use App\Livewire\Reviews\Setup\ArtistSetup;
use App\Livewire\Reviews\Setup\TrackSetup;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

class ReviewSetup extends Component
{
    #[Url(nullable: true)]
    public ?ReviewType $type = null;

    public function mount(): void
    {
        $this->type ??= ReviewType::ALBUM;
    }

    public function render()
    {
        return view('livewire.reviews.review-setup');
    }

    #[On('switch-review-type')]
    public function switchType(string $type): void
    {
        $this->type = ReviewType::from($type);
    }

    public function setupComponent(): string
    {
        return match ($this->type) {
            ReviewType::ARTIST => ArtistSetup::class,
            ReviewType::ALBUM => AlbumSetup::class,
            ReviewType::TRACK => TrackSetup::class,
        };
    }
}
