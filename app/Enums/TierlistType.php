<?php

namespace App\Enums;

enum TierlistType: string
{
    case ARTIST = 'artist';
    case ALBUM = 'album';
    case TRACK = 'track';

    public function label(): string
    {
        return match ($this) {
            self::ARTIST => 'Artist',
            self::ALBUM => 'Album',
            self::TRACK => 'Track',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::ARTIST => 'fa-microphone-lines',
            self::ALBUM => 'fa-compact-disc',
            self::TRACK => 'fa-music',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ARTIST => 'purple',
            self::ALBUM => 'blue',
            self::TRACK => 'green',
        };
    }

    public function filamentColor(): string
    {
        return match ($this) {
            self::ARTIST => 'primary',
            self::ALBUM => 'info',
            self::TRACK => 'success',
        };
    }

    public function itemLabel(): string
    {
        return match ($this) {
            self::ARTIST => 'artists',
            self::ALBUM => 'albums',
            self::TRACK => 'tracks',
        };
    }

    /**
     * The `type` a Spotify search request expects for this kind of entry.
     */
    public function spotifySearchType(): string
    {
        return $this->value;
    }
}
