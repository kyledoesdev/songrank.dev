<?php

namespace App\Enums;

enum RankingType: string
{
    case ARTIST = 'artist';
    case PLAYLIST = 'playlist';
    case SHOW = 'show';

    public function label(): string
    {
        return match ($this) {
            self::ARTIST => 'Artist',
            self::PLAYLIST => 'Playlist',
            self::SHOW => 'Podcast / Show',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::ARTIST => 'fa-microphone-lines',
            self::PLAYLIST => 'fa-bars-staggered',
            self::SHOW => 'fa-podcast',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ARTIST => 'purple',
            self::PLAYLIST => 'green',
            self::SHOW => 'blue',
        };
    }

    public function filamentColor(): string
    {
        return match ($this) {
            self::ARTIST => 'primary',
            self::PLAYLIST => 'success',
            self::SHOW => 'info',
        };
    }

    public function itemLabel(): string
    {
        return match ($this) {
            self::SHOW => 'episodes',
            default => 'tracks',
        };
    }
}
