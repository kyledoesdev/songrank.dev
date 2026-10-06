<?php

namespace App\Enums;

enum CatalogSort: string
{
    case ALPHABETICAL = 'alphabetical';
    case CUSTOM = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::ALPHABETICAL => 'A–Z',
            self::CUSTOM => 'Custom',
        };
    }
}
