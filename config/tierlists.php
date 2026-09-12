<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tier Ceiling
    |--------------------------------------------------------------------------
    |
    | How many placement tiers one list may hold. The bank does not count
    | toward it, and a list can never drop below a single placement tier.
    |
    */

    'max_tiers' => 12,

    /*
    |--------------------------------------------------------------------------
    | Default Tiers
    |--------------------------------------------------------------------------
    |
    | Every new tier list starts here, and the owner customises from there.
    | Positions are assigned by order in this array, starting after the bank.
    |
    */

    'default_tiers' => [
        ['name' => 'S', 'slug' => 's', 'color' => '#FF7F7F'],
        ['name' => 'A', 'slug' => 'a', 'color' => '#FFBF7F'],
        ['name' => 'B', 'slug' => 'b', 'color' => '#FFDF7F'],
        ['name' => 'C', 'slug' => 'c', 'color' => '#FFFF7F'],
        ['name' => 'D', 'slug' => 'd', 'color' => '#BFFF7F'],
        ['name' => 'F', 'slug' => 'f', 'color' => '#7FFF7F'],
    ],

    /*
    |--------------------------------------------------------------------------
    | The Bank
    |--------------------------------------------------------------------------
    |
    | The holding area every unplaced entry starts in. It is a tier row like
    | any other so that every item carries a tier id and every drag target is
    | uniform — but it always sits at position zero, cannot be renamed,
    | reordered or deleted, and must be emptied before a list can finish.
    |
    */

    'bank' => ['name' => 'Bank', 'slug' => 'bank', 'color' => '#E4E4E7'],

];
