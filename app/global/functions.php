<?php

use App\Models\Artist;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * The name of the route the visitor came from: null when they followed a link
 * from another site, or came from a page of ours that no route matches.
 */
function prev_route(): ?string
{
    $previous = url()->previous();

    if (parse_url($previous, PHP_URL_HOST) !== request()->getHost()) {
        return null;
    }

    try {
        return Route::getRoutes()->match(request()->create($previous))->getName();
    } catch (HttpException) {
        return null;
    }
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

function logo(): string
{
    return asset(auth()->user()?->is_pro ? 'images/pro-logo.png' : 'images/logo.png');
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
