<?php

use App\Http\Controllers\SpotifyAuthController;
use App\Livewire\About;
use App\Livewire\Dashboard\Dashboard;
use App\Livewire\Explorer;
use App\Livewire\Faq;
use App\Livewire\Leaderboards;
use App\Livewire\Legal\Privacy;
use App\Livewire\Legal\Terms;
use App\Livewire\Notifications\ShowAll as Notifications;
use App\Livewire\Profile\Profile;
use App\Livewire\Profile\Settings;
use App\Livewire\Ranking\EditRanking;
use App\Livewire\Ranking\Ranking;
use App\Livewire\Welcome\Welcome;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;
use Kyledoesdev\Essentials\Middleware\IsDeveloper;
use Spatie\Health\Http\Controllers\HealthCheckResultsController;

Route::livewire('/', Welcome::class)
    ->name('welcome');

Route::livewire('/about', About::class)
    ->name('about')
    ->withHead(title: 'About');

Route::livewire('/terms', Terms::class)
    ->name('terms')
    ->withHead(title: 'Terms of Service');

Route::livewire('/privacy', Privacy::class)
    ->name('privacy')
    ->withHead(title: 'Privacy Policy');

Route::livewire('/faq', Faq::class)
    ->name('faq')
    ->withHead(title: 'FAQ');

Route::livewire('/explore', Explorer::class)
    ->name('explore')
    ->withHead(title: 'Explore');

Route::livewire('/leaderboards', Leaderboards::class)
    ->name('leaderboards')
    ->withHead(title: 'Leaderboards');

Route::livewire('/rank/{id}', Ranking::class)
    ->name('ranking');

Route::livewire('/profile/{id}', Profile::class)
    ->name('profile');

Route::get('/login/spotify', [SpotifyAuthController::class, 'login'])->name('spotify.login');
Route::get('/login/spotify/callback', [SpotifyAuthController::class, 'processLogin'])->name('spotify.process_login');
Route::get('/logout', [SpotifyAuthController::class, 'logout'])->name('logout');

Route::middleware(Authenticate::class)->group(function () {
    Route::livewire('/dashboard', Dashboard::class)
        ->name('dashboard')
        ->withHead(title: 'Dashboard');

    Route::livewire('/rank/{id}/edit', EditRanking::class)
        ->name('rank.edit')
        ->withHead(title: 'Edit Ranking');

    Route::livewire('/settings', Settings::class)
        ->name('settings')
        ->withHead(title: 'Settings');

    Route::livewire('/notifications', Notifications::class)
        ->name('notifications')
        ->withHead(title: 'Notifications');

    Route::supportBubble();

    Route::middleware(IsDeveloper::class)->group(function () {
        Route::get('health', HealthCheckResultsController::class);
    });
});

require __DIR__.'/billing.php';
