<?php

use App\Http\Controllers\Billing\ProCheckoutController;
use App\Http\Middleware\RedirectIfAlreadyPro;
use App\Livewire\Billing\Billing;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;

Route::middleware(Authenticate::class)->group(function () {
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
