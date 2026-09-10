<?php

use App\Http\Controllers\Billing\ProCheckoutController;
use App\Http\Middleware\RedirectIfAlreadyPro;
use App\Livewire\Billing\Billing;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;
use Laravel\Pennant\Middleware\EnsureFeaturesAreActive;

/*
|--------------------------------------------------------------------------
| Billing Routes
|--------------------------------------------------------------------------
|
| Everything behind the `songrank-pro` flag. While the feature is inactive
| these 404 rather than 403, so an unreleased product looks absent instead
| of forbidden. Required from routes/web.php.
|
| The checkout session is treated as a resource. Stripe redirects the
| customer's browser back to `show` and `cancel`, so both answer GET.
|
*/

Route::middleware([Authenticate::class, EnsureFeaturesAreActive::using('songrank-pro')])->group(function () {
    Route::livewire('/billing', Billing::class)
        ->name('billing')
        ->withHead(title: 'Billing');

    Route::post('/billing/checkout', [ProCheckoutController::class, 'store'])
        ->middleware(RedirectIfAlreadyPro::class)
        ->name('billing.checkout');

    Route::get('/billing/checkout/success', [ProCheckoutController::class, 'show'])
        ->name('billing.success');

    Route::get('/billing/checkout/cancel', [ProCheckoutController::class, 'cancel'])
        ->name('billing.cancel');
});
