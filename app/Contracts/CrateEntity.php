<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface CrateEntity
{
    public function cover(): ?string;

    public function label(): string;

    public function progressLabel(): ?string;

    public function crateItems(): MorphMany;
}
