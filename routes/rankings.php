<?php

use App\Livewire\Ranking\EditRanking;
use App\Livewire\Ranking\Ranking;
use App\Livewire\SongRank\SongRankSetup;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;

Route::livewire('/rank/{id}', Ranking::class)
    ->name('ranking');

Route::middleware(Authenticate::class)->group(function () {
    Route::livewire('/rankings/create', SongRankSetup::class)
        ->name('rankings.create')
        ->withHead(title: 'Create a Ranking');

    Route::livewire('/rank/{id}/edit', EditRanking::class)
        ->name('rank.edit')
        ->withHead(title: 'Edit Ranking');
});
