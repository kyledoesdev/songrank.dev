<?php

use Bugsnag\BugsnagLaravel\BugsnagServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Laravel\Socialite\SocialiteServiceProvider;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use SocialiteProviders\Manager\ServiceProvider;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        SocialiteServiceProvider::class,
        ServiceProvider::class,
        BugsnagServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        // api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        // channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');

        $middleware->redirectGuestsTo(fn () => route('welcome'));
        $middleware->redirectUsersTo(fn () => route('dashboard'));

        $middleware->throttleApi();

        $middleware->preventRequestForgery(except: [
            'support-bubble',
            'newsletter/*/unsubscribe',
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {})
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->report(function (TypeError $e) {
            if (str_contains($e->getMessage(), 'Filament\Notifications\Collection')) {
                return false;
            }
        });

        $exceptions->dontReport([
            CannotUpdateLockedPropertyException::class,
        ]);
    })
    ->create();
