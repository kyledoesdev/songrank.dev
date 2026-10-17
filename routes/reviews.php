<?php

use App\Livewire\Reviews\EditReview;
use App\Livewire\Reviews\ReviewSetup;
use App\Livewire\Reviews\ReviewShow;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;

Route::livewire('/review/{id}', ReviewShow::class)
    ->name('review');

Route::middleware(Authenticate::class)->group(function () {
    Route::livewire('/reviews/create', ReviewSetup::class)
        ->name('reviews.create')
        ->withHead(title: 'Write a Review');

    Route::livewire('/review/{id}/edit', EditReview::class)
        ->name('review.edit')
        ->withHead(title: 'Edit Review');
});
