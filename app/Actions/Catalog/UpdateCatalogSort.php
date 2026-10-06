<?php

namespace App\Actions\Catalog;

use App\Enums\CatalogSort;
use App\Models\User;

final class UpdateCatalogSort
{
    public function handle(User $user, CatalogSort $sort): void
    {
        $user->preferences->update(['catalog_sort' => $sort]);
    }
}
