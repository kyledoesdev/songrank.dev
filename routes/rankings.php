<?php

use App\Livewire\Ranking\EditRanking;
use App\Livewire\Ranking\Ranking;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Ranking Routes
|--------------------------------------------------------------------------
|
| The core ranking domain. Required from routes/web.php.
|
| The show route is public because a finished, public ranking is meant to be
| shared; the component decides who may actually see each one, and serves the
| sorting process itself to an owner whose ranking is not finished yet.
|
*/

Route::livewire('/rank/{id}', Ranking::class)
    ->name('ranking');

Route::middleware(Authenticate::class)->group(function () {
    Route::livewire('/rank/{id}/edit', EditRanking::class)
        ->name('rank.edit')
        ->withHead(title: 'Edit Ranking');
});
