<?php

namespace App\Livewire\Catalog;

use App\Actions\Catalog\UpdateCatalogSort;
use App\Enums\CatalogSort;
use App\Livewire\Catalog\Concerns\HasCrateList;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Catalog extends Component
{
    use HasCrateList;

    public function render()
    {
        return view('livewire.catalog.catalog');
    }

    public function sortBy(string $sort): void
    {
        abort_unless(Auth::user()->canArrangeCrates(), 403);

        (new UpdateCatalogSort)->handle(Auth::user(), CatalogSort::tryFrom($sort) ?? CatalogSort::ALPHABETICAL);
    }
}
