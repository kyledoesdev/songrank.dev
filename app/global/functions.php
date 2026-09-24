<?php

use App\Models\Artist;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

function prev_route(): string
{
    return Route::getRoutes()->match(app('request')->create(url()->previous()))->getName();
}

/**
 * A placeholder artist for a search box. Cached, because a random row is an
 * unindexable table scan and nobody notices it is the same name for an hour.
 */
function random_artist(): string
{
    return cache()->remember(
        'random-artist',
        now()->addHour(),
        fn () => Artist::query()->randomName() ?? '',
    );
}

function short_number(int $number): string
{
    return match (true) {
        $number >= 1000000 => round($number / 1000000, 1).'m',
        $number >= 1000 => round($number / 1000, 1).'k',
        default => (string) $number,
    };
}

function title(): string
{
    $title = config('app.name').' - ';

    match (Route::currentRouteName()) {
        'ranking' => $title .= 'Ranking',
        'profile' => $title .= session()->get('profile_name').' Profile',
        'faq' => $title .= 'FAQ',
        'terms' => $title .= 'Terms of Service',
        'privacy' => $title .= 'Privacy Policy',
        default => $title .= Str::title(Str::lower(Str::replace('.', ' ', Route::currentRouteName())))
    };

    return $title;
}
