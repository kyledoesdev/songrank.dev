<?php

namespace App\Enums;

enum ShareTarget: string
{
    case X = 'x';
    case BLUESKY = 'bluesky';

    public function label(): string
    {
        return match ($this) {
            self::X => 'X',
            self::BLUESKY => 'Bluesky',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::X => 'fa-brands fa-x-twitter',
            self::BLUESKY => 'fa-brands fa-bluesky',
        };
    }

    public function intentUrl(string $text, string $url): string
    {
        return match ($this) {
            self::X => 'https://x.com/intent/post?'.http_build_query(['text' => $text, 'url' => $url]),
            self::BLUESKY => 'https://bsky.app/intent/compose?'.http_build_query(['text' => $text.' '.$url]),
        };
    }
}
