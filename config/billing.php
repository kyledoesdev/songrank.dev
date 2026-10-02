<?php

return [

    'pro' => [
        'price_id' => env('STRIPE_PRICE_SONG_RANK_PRO_ID'),
        'amount' => 1000,
        'currency' => 'usd',
    ],

    'tax' => [
        'automatic' => env('STRIPE_AUTOMATIC_TAX', false),
    ],

    'ranking_limits' => [
        'free' => 100,
        'pro' => null,
    ],

    'tierlist_limits' => [
        'free' => [
            'artist' => 3,
            'album' => 3,
            'track' => 3,
        ],
        'pro' => [
            'artist' => null,
            'album' => null,
            'track' => null,
        ],
    ],

    'review_limits' => [
        'free' => 5,
        'pro' => null,
    ],

    'catalog_limits' => [
        'free' => [
            'folders' => 5,
            'depth' => 1,
        ],
        'pro' => [
            'folders' => null,
            'depth' => 2,
        ],
    ],

    'experience_multipliers' => [
        'free' => 1,
        'pro' => 2,
    ],

    'tierlist_item_limits' => [
        'free' => 100,
        'pro' => 500,
    ],
];
