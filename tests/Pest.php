<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Filament');

pest()->extend(TestCase::class)
    ->in('Platform');

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
|
| Helper names are global, so they have to stay unique across the whole suite.
| They are grouped by the domain they build for rather than piled into this
| file; anything used by a single test file still lives at the bottom of it.
|
*/

require_once __DIR__.'/Helpers/users.php';
require_once __DIR__.'/Helpers/rankings.php';
require_once __DIR__.'/Helpers/tierlists.php';
require_once __DIR__.'/Helpers/spotify.php';
