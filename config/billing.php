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

    /*
    |--------------------------------------------------------------------------
    | Tier List Limits
    |--------------------------------------------------------------------------
    |
    | How many tier lists an account may own, per type. A map rather than a
    | single number, so the free allowance can differ between artist, album
    | and track lists without a migration. `null` means unlimited.
    |
    | These only apply once the `tierlists` feature is active for a user.
    |
    */

    'tierlist_limits' => [
        'free' => [
            'artist' => 1,
            'album' => 1,
            'track' => 1,
        ],
        'pro' => [
            'artist' => null,
            'album' => null,
            'track' => null,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tier List Size
    |--------------------------------------------------------------------------
    |
    | How many entries one list may hold. This one is never null: a board has
    | to stay draggable, and every tile is an image the browser has to paint.
    |
    */

    'tierlist_item_limits' => [
        'free' => 100,
        'pro' => 500,
    ],
];
