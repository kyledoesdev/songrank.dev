<?php

use App\Http\Controllers\TierlistEmbedController;
use App\Livewire\Tierlist\EditTierlist;
use App\Livewire\Tierlist\TierlistSetup;
use App\Livewire\Tierlist\TierlistShow;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;

Route::livewire('/tierlist/{id}', TierlistShow::class)
    ->name('tierlist');

Route::get('/tierlist/{id}/embed', TierlistEmbedController::class)
    ->name('tierlist.embed');

Route::middleware(Authenticate::class)->group(function () {
    Route::livewire('/tierlists/create', TierlistSetup::class)
        ->name('tierlists.create')
        ->withHead(title: 'Create a Tier List');

    Route::livewire('/tierlist/{id}/edit', EditTierlist::class)
        ->name('tierlist.edit')
        ->withHead(title: 'Edit Tier List');
});
