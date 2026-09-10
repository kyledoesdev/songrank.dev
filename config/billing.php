<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Song Rank Pro
    |--------------------------------------------------------------------------
    |
    | A single, non-recurring purchase. The price object lives in Stripe; the
    | amount below is only used for display before checkout, and the currency
    | as a fallback when a license predates a recorded currency.
    |
    | The Stripe Price is configured as tax-inclusive, so a buyer pays the same
    | headline amount everywhere and VAT is remitted out of it.
    |
    */

    'pro' => [
        'price_id' => env('STRIPE_PRICE_SONG_RANK_PRO_ID'),
        'amount' => 1000,
        'currency' => 'usd',
    ],

    /*
    |--------------------------------------------------------------------------
    | Automatic Tax
    |--------------------------------------------------------------------------
    |
    | Stripe refuses to create a checkout session with automatic tax until the
    | account has a head office address configured, which a fresh sandbox has
    | not — so this stays off until Stripe Tax is actually set up, and the app
    | works either way.
    |
    | Turn it on once the head office address is set and the Price carries an
    | explicit tax_behavior. Collection still only happens in jurisdictions
    | where a registration exists.
    |
    */

    'tax' => [
        'automatic' => env('STRIPE_AUTOMATIC_TAX', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Ranking Limits
    |--------------------------------------------------------------------------
    |
    | How many rankings an account may own. `null` means unlimited — never a
    | sentinel like PHP_INT_MAX. The count includes in-progress rankings,
    | because those occupy storage just as finished ones do.
    |
    | These only apply once the `songrank-pro` feature is active for a user.
    |
    */

    'ranking_limits' => [
        'free' => 100,
        'pro' => null,
    ],

];
