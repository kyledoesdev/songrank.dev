<?php

use App\Livewire\Catalog\Catalog;
use App\Livewire\Catalog\CrateShow;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;

Route::middleware(Authenticate::class)->group(function () {
    Route::livewire('/catalog', Catalog::class)
        ->name('catalog')
        ->withHead(title: 'Catalog');

    Route::livewire('/catalog/{id}', CrateShow::class)
        ->name('catalog.crate');
});
