<?php

use App\Livewire\Tierlist\TierlistSetup;
use App\Livewire\Tierlist\TierlistShow;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;
use Laravel\Pennant\Middleware\EnsureFeaturesAreActive;

/*
|--------------------------------------------------------------------------
| Tier List Routes
|--------------------------------------------------------------------------
|
| Everything behind the `tierlists` flag. While the feature is inactive these
| 404 rather than 403, so an unreleased product looks absent instead of
| forbidden. Required from routes/web.php.
|
| The show route is public because a finished, public list is meant to be
| shared; the component decides who may actually see each one.
|
*/

Route::middleware(EnsureFeaturesAreActive::using('tierlists'))->group(function () {
    Route::livewire('/tierlist/{id}', TierlistShow::class)
        ->name('tierlist');

    Route::middleware(Authenticate::class)->group(function () {
        Route::livewire('/tierlists/create', TierlistSetup::class)
            ->name('tierlists.create')
            ->withHead(title: 'Create a Tier List');
    });
});
