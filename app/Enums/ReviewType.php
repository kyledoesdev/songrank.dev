<?php

namespace App\Enums;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Track;

enum ReviewType: string
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
            self::ALBUM => 'green',
            self::TRACK => 'blue',
        };
    }

    public function filamentColor(): string
    {
        return match ($this) {
            self::ARTIST => 'primary',
            self::ALBUM => 'success',
            self::TRACK => 'info',
        };
    }

    public function subjectLabel(): string
    {
        return match ($this) {
            self::ARTIST => 'artist',
            self::ALBUM => 'album',
            self::TRACK => 'track',
        };
    }

    /**
     * "an album", "a track" — so copy does not have to work it out inline.
     */
    public function article(): string
    {
        return $this === self::ALBUM ? 'an' : 'a';
    }

    public function withArticle(): string
    {
        return $this->article().' '.$this->subjectLabel();
    }

    public function spotifySearchType(): string
    {
        return $this->value;
    }

    /**
     * @return class-string<Artist|Album|Track>
     */
    public function subjectModel(): string
    {
        return match ($this) {
            self::ARTIST => Artist::class,
            self::ALBUM => Album::class,
            self::TRACK => Track::class,
        };
    }

    public function spotifyIdColumn(): string
    {
        return match ($this) {
            self::ARTIST => 'artist_id',
            self::ALBUM => 'album_id',
            self::TRACK => 'track_id',
        };
    }
}
