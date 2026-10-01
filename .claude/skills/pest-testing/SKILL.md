---
name: pest-testing
description: "Use this skill for Pest PHP testing in this Laravel project. Trigger whenever any test is being written, edited, fixed, or refactored — including fixing tests that broke after a code change, adding assertions, adding datasets, and TDD workflows. Always activate when the user asks how to write something in Pest, mentions test files or the Feature, Filament or Platform suites, or needs Livewire, Filament, feature-flag or architecture tests. Covers: describe()/it()/expect() syntax, the shared helpers in tests/Helpers, faking Spotify and Stripe, datasets, mocking, and arch(). Do not use for factories, seeders, migrations, controllers, models, or non-test PHP code."
license: MIT
metadata:
  author: laravel
  customized-for: songrank.dev
---

# Pest Testing 4

Every change must be covered by a test. Do **not** delete a test without approval.

## The Three Suites

Registered in `phpunit.xml`, all bound to `Tests\TestCase` in `tests/Pest.php`:

| Suite | Directory | `RefreshDatabase` | Covers |
| --- | --- | --- | --- |
| Feature | `tests/Feature/` | yes | application behaviour, grouped by product area |
| Filament | `tests/Filament/` | yes | the admin panel, mirroring `app/Filament/` |
| Platform | `tests/Platform/` | no | codebase-wide checks (arch tests) |

**There is no `tests/Unit` and no `tests/Browser`.** Do not create them. `pest-plugin-browser` is installed and CI installs Playwright browsers, but no browser tests exist yet — adding the first one means registering a new suite in `phpunit.xml`, so raise it rather than dropping a file somewhere.

Feature subdirectories are product areas: `Account/`, `Auth/`, `Discovery/`, `Pages/`, `Rankings/`, `Tierlists/`, `Billing/`. Add a new one only when an area genuinely has no home. Anything under `app/Filament/` is tested in the Filament suite, never in Feature.

`php artisan make:test --pest SomeTest` drops the file in `tests/Feature/` — **move it into the right subdirectory**. Name Filament files after the surface under test (`TierlistTableTest`, `RankingInfolistTest`, `LoginsWidgetTest`), not after the resource, because one resource has several surfaces.

## File Structure

Top to bottom, every time:

1. **Imports** — class `use` statements, then `use function Pest\Laravel\...`
2. **`beforeEach()`** — shared arrangement, only when needed
3. **`describe()` blocks** — every test lives inside one
4. **Helper functions** — file-specific helpers at the bottom

```php
<?php

use App\Livewire\Tierlist\TierlistBuilder;
use App\Models\Tierlist;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

describe('who gets the builder', function () {
    it('is what an owner sees while the board is unfinished', function () {
        $tierlist = Tierlist::factory()->for(kyle())->create();

        actingAs($tierlist->user);

        get(route('tierlist', ['id' => $tierlist->getKey()]))
            ->assertOk()
            ->assertSee('Unranked');
    });
});
```

Non-negotiables:

- **Use the `Pest\Laravel` function imports** — `get()`, `post()`, `actingAs()`, `assertGuest()`, `assertDatabaseHas()` — not `$this->get()`. Intelephense cannot resolve `$this` inside a Pest closure, so `$this->` costs editor support for no gain.
- **Avoid `$this->property` state in `beforeEach()`** for the same reason. Reach for a helper function that builds and returns what the test needs.
- **`it()` or `test()`: match the file you are editing.** Both are in use — `it()` is the majority in `Feature/` (265 to 187), `test()` slightly leads in `Filament/` (40 to 29) — so there is no project-wide rule, only a per-file one. Never mix the two inside one file.
- Describe names read as subjects (`'dragging'`, `'checkout.session.completed'`, `'who gets the builder'`); `it()` names complete a sentence about behaviour, not about code.

## Helpers

Cross-file helpers live in `tests/Helpers/`, grouped by the domain they build for and required from `tests/Pest.php`. **Helper names are global, so they must be unique across the whole suite** — anything used by one file stays at the bottom of that file.

| File | Helpers |
| --- | --- |
| `users.php` | `kyle()`, `proUser()`, `userWithRankings()`, `userWithTierlists()` |
| `rankings.php` | `publicCompletedRanking()`, `algorithmRanking()`, `simulateRankingComparisons()`, `expectedSongTitles()` |
| `tierlists.php` | `publicCompletedTierlist()`, `artistEntry()`, `albumEntry()`, `trackEntry()` |
| `spotify.php` | `fakeSearch()`, `fakeDiscography()`, `spotifyArtist()`, `spotifyAlbum()`, `spotifyTrack()` |

Check this list before writing a factory call by hand, and before naming a new helper.

## Feature Flags and the Admin Panel

`songrank-pro` and `tierlists` both resolve on `is_dev`, and their routes **404 rather than 403** when inactive. So:

- `kyle()` is the project's only admin persona — act as them for the Filament panel and for anything flagged
- `proUser(['is_dev' => true])` for a user who both sees the Pro system and holds a paid licence. `proUser()` alone gives a licence but no flag
- `Feature::define('songrank-pro', true)` in `beforeEach` when the test is about billing rather than about gating
- `PENNANT_STORE=array` is already set in `phpunit.xml`, so flags never leak between tests

## Livewire and Filament

Every page here is a Livewire component, and Filament pages and widgets are too:

```php
Livewire::actingAs(kyle())
    ->test(TierlistBuilder::class, ['tierlist' => $tierlist])
    ->assertForbidden();

Livewire::actingAs(kyle())
    ->test(ListTierlists::class)
    ->filterTable('hide_incomplete', false)
    ->assertCanSeeTableRecords([$completed, $incomplete]);
```

Pass `['record' => $model->getKey()]` for Filament view and edit pages. `call('moveItem', ...)` is how a `wire:sort` handler gets exercised — drive the method, then assert on the refreshed model.

## Faking the Outside World

Nothing in the suite touches the network.

- **Spotify:** `fakeSearch()` / `fakeDiscography()` set up `Http::fake()` for `accounts.spotify.com` and `api.spotify.com`. The default fixtures all describe Tame Impala's *Currents*, so a failing assertion reads like a record rather than like `album-1`.
- **Stripe:** bind a fake `StripeClient` into the container. See the `cashier-stripe-development` skill's `references/testing.md`.
- **Notifications:** `Notification::fake()` in `beforeEach`, and call it **after** factory setup — factories rely on model events, so faking first produces broken models.

## Assertions

Prefer the specific ones:

| Use | Instead of |
| --- | --- |
| `assertOk()` / `assertSuccessful()` | `assertStatus(200)` |
| `assertNotFound()` | `assertStatus(404)` |
| `assertForbidden()` | `assertStatus(403)` |

`assertDatabaseHas` for column state, `assertModelExists` for existence, `assertDatabaseCount` for idempotency. Chain `expect()` for model state: `expect($license->fresh()->status)->toBe(ProLicenseStatus::ACTIVE)`.

## Architecture Tests

`tests/Platform/ArchTest.php`, named with a `System: ` prefix:

```php
arch('System: Uses no debug methods')->expect(['dd', 'dump', 'die', 'ray'])->not->toBeUsed();
```

## Running Tests

```bash
php artisan test --compact --filter=TierlistBuilderTest
php artisan test --compact tests/Feature/Billing
php artisan test --testsuite=Filament
```

Run the narrowest thing that proves the change, then `vendor/bin/pint --dirty --format agent`. CI runs `php vendor/bin/pest --parallel` with `memory_limit=512M` — the ranking export test builds an xlsx in memory, so a lower limit fails there first.

## Pitfalls

- Writing `$this->get()` instead of the imported `get()`
- Dropping a test at the root of `tests/Feature/` instead of its product area
- Creating `tests/Unit/` or `tests/Browser/` for a test that belongs in an existing suite
- Reusing a helper name that already exists somewhere in `tests/Helpers/`
- Expecting 403 from a flagged route — it 404s
- `Notification::fake()` or `Event::fake()` before factory calls
- Forgetting `is_dev` on a user that needs to see a flagged feature
