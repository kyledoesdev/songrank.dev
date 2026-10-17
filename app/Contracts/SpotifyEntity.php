<?php

namespace App\Contracts;

interface SpotifyEntity
{
    public function cover(): ?string;

    public function name(): string;

    public function spotifyId(): string;

    public function spotifyUrl(): string;
}
