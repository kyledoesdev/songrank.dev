---
name: pest-testing
description: "Use this skill for Pest PHP testing in this Laravel project. Trigger whenever any test is being written, edited, fixed, or refactored — including fixing tests that broke after a code change, adding assertions, adding datasets, and TDD workflows. Always activate when the user asks how to write something in Pest, mentions test files or the Feature, Filament or Platform suites, or needs Livewire, Filament or architecture tests. Covers: describe()/it()/expect() syntax, which assertions this project accepts, the shared helpers in tests/Helpers, faking Spotify and Stripe, datasets, mocking, and arch(). Do not use for factories, seeders, migrations, controllers, models, or non-test PHP code."
license: MIT
metadata:
  author: laravel
  customized-for: songrank.dev
---

# Pest Testing 4

Every change must be covered by a test. Do **not** delete a test without approval. Two kinds of change are the exception and go untested: a one-line log message that only reaches the owner's own Discord, and code behind a feature switched off in config (comment mentions).

## The Three Suites

Registered in `phpunit.xml`, all bound to `Tests\TestCase` in `tests/Pest.php`:

| Suite | Directory | `RefreshDatabase` | Covers |
| --- | --- | --- | --- |
| Feature | `tests/Feature/` | yes | application behaviour, grouped by product area |
| Filament | `tests/Filament/` | yes | the admin panel, mirroring `app/Filament/` |
| Platform | `tests/Platform/` | no | codebase-wide checks (arch tests) |

**There is no `tests/Unit` and no `tests/Browser`.** Do not create them. `pest-plugin-browser` is installed and CI installs Playwright browsers, but no browser tests exist yet — adding the first one means registering a new suite in `phpunit.xml`, so raise it rather than dropping a file somewhere.

Feature subdirectories are product areas: `Account/` (the signed-in user's own surfaces: profile, settings, the dashboard's in-progress list, the bell, comment emails), `Auth/`, `System/`, `Discovery/`, `Pages/`, `Rankings/`, `Tierlists/`, `Reviews/`, `Billing/`, `Catalog/`. Add a new one only when an area genuinely has no home. Anything under `app/Filament/` is tested in the Filament suite, never in Feature, at the same path as the code it covers.

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
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

describe('who gets the builder', function () {
    it('is what an owner sees while the board is unfinished', function () {
        $tierlist = Tierlist::factory()->for(User::factory())->create();

        actingAs($tierlist->user);

        get(route('tierlist', ['id' => $tierlist->getKey()]))
            ->assertOk()
            ->assertSeeLivewire(TierlistBuilder::class)
            ->assertSee('wire:click="confirmFinish"', escape: false);
    });
});
```

Non-negotiables:

- **Use the `Pest\Laravel` function imports** — `get()`, `post()`, `actingAs()`, `assertGuest()`, `assertDatabaseHas()` — not `$this->get()`. Intelephense cannot resolve `$this` inside a Pest closure, so `$this->` costs editor support for no gain.
- **Avoid `$this->property` state in `beforeEach()`** for the same reason. Reach for a helper function that builds and returns what the test needs.
- **`it()` or `test()`: match the file you are editing.** Both are in use, so there is no project-wide rule, only a per-file one. Never mix the two inside one file.
- Describe names read as subjects (`'dragging'`, `'checkout.session.completed'`, `'who gets the builder'`); `it()` names complete a sentence about behaviour, not about code.

## Assertions

**A test passes or fails on behaviour, never on wording.** Copy changes all the time; a test that breaks when a heading is reworded, or keeps passing after the copy it checks for has gone, is worse than none. In order of preference, assert:

1. **State** — a component's computed collection (`->instance()->tierlists->modelKeys()`), `assertSet`, `assertViewHas('document', fn ($d) => $d->is($current))`, model and database state.
2. **Structure** — a route or URL (`assertSee(route('billing'), escape: false)`), a `wire:` hook (`'wire:click="confirmFinish"'`), a `wire:key` (`'wire:key="tier-'.$tier->getKey().'"'`), `assertSeeLivewire()`.
3. **Behaviour** — redirects, dispatched events, `assertForbidden()` / `assertNotFound()`, alert payloads.

Never assert:

- **Interface copy** — headings, labels, button text, placeholders, empty-state sentences, alert titles and messages. When the only proof is a rendered string, derive it from the app's own source of truth instead: `$license->status->label()`, `$license->formattedTotal()`, `$feature->renderedLabel()`.
- **A record's name in rendered HTML** — Faker names get truncated by cards. Assert the record's id or URL, or the component's collection.
- **A bare number or a short word** — `assertSee('5')` or `assertSee('DE')` matches something on any page.

Watch for vacuous assertions — ones that pass whether or not the behaviour exists:

- **The layout answers for you.** The nav links to the dashboard and the viewer's own profile; the `<head>` carries `og:image` and a canonical link. Test the page component with `Livewire::test()` (no layout), or target the element precisely (`'src="'.$cover.'"'` rather than the bare URL).
- **Something else renders the same thing.** Comments forms show the viewer's own avatar and profile link; turn comments off when testing a banner's profile link.
- **Shared factory values.** Every factory image is unique (`https://example.test/<kind>/<random>.png`) so a cover can only come from the record it belongs to. Keep it that way.
- **Negative-only checks.** Every `assertDontSee` / "hidden" assertion needs a positive control somewhere — the same hook seen in the case where it should appear.

Alerts (`tests/Helpers/alerts.php`): `alertsFrom($component)->pluck('icon')->all()` toBe `['error']`, and `alertsFrom($component, 'confirm')->first()['action']` for the method a confirm calls back. Tell two error alerts apart by the state around them, not by their titles.

Prefer the specific HTTP assertions: `assertOk()`, `assertNotFound()`, `assertForbidden()` over `assertStatus()`. `assertDatabaseHas` for column state, `assertModelExists` for existence, `assertDatabaseCount` for idempotency.

**Prove a new assertion has teeth.** Break the behaviour it covers (invert a condition, drop a scope, empty a Blade branch), watch the test fail, restore. After editing a Blade file to do this, run `php artisan view:clear` — Blade compares modification times to the second, and a restored template can keep serving its broken compiled copy.

## Decoupling

- **Limits come from config.** Derive them (`userWithTierlists(config('billing.tierlist_limits.free.artist'))`) or pin them in a `beforeEach` (`config()->set('billing.review_limits.free', 5)`) — never hard-code production's numbers.
- **Seeded rows are not fixtures.** Crate icons, plan features and FAQs are seeded by migrations. Build what the test needs with a factory, and clear the seeded set first when the test needs to see a whole table.
- **Factory defaults are not facts.** A factory ranking happens to get ten songs; count them (`$ranking->songs()->count()`) instead of writing `10`. The default tiers live in `config('tierlists.default_tiers')`.
- **Orders are explicit.** Compare collections only when the query or relation orders them (`orderBy`, a scope, a relation's own `orderBy('position')`), or use `toEqualCanonicalizing()`. Rankings start from a shuffle; tests start them from `sortingStateInOrder()` instead.

## Helpers

Cross-file helpers live in `tests/Helpers/`, grouped by the domain they build for and required from `tests/Pest.php`. **Helper names are global, so they must be unique across the whole suite** — anything used by one file stays at the bottom of that file, and even then gets a specific name (`tierlistBoard()`, not `board()`).

| File | Helpers |
| --- | --- |
| `users.php` | `kyle()`, `proUser()`, `revokePro()`, `userWithRankings()`, `userWithTierlists()`, `userWithReviews()`, `userWithCrates()`, `profilePage()` |
| `rankings.php` | `publicCompletedRanking()`, `algorithmRanking()`, `simulateRankingComparisons()`, `expectedSongTitles()`, `sortingStateInOrder()` |
| `tierlists.php` | `publicCompletedTierlist()`, `artistEntry()`, `albumEntry()`, `trackEntry()`, `queriesRunBy()` |
| `reviews.php` | `publicPublishedReview()` |
| `catalog.php` | `crateEntry()`, `crateContents()`, `crateNamesShown()` |
| `spotify.php` | `fakeSearch()`, `fakeDiscography()`, `fakePlaylist()`, `spotifyArtist()`, `spotifyAlbum()`, `spotifyTrack()` |
| `stripe.php` | `fakeStripeServices()`, `unreachableStripeService()` |
| `alerts.php` | `alertsFrom()` |
| `filament.php` | `widgetStats()`, `widgetChartData()` |

Check this list before writing a factory call by hand, and before naming a new helper.

## Users and the Admin Panel

The feature flags are released and nothing checks them, so ordinary tests act as an ordinary user:

- `User::factory()->createOne()` for anybody signed in; `proUser()` for a paid licence; `revokePro()` to take Pro away again
- `kyle()` is the admin persona and belongs **only** in the Filament suite — the panel is gated on `is_dev`. It runs in UTC, so the dashboard widgets' date windows hold still
- Guests: no `actingAs()` at all. Signed-out pages redirect to `welcome`

## Livewire and Filament

Every page here is a Livewire component, and Filament pages and widgets are too:

```php
Livewire::actingAs(User::factory()->createOne())
    ->test(TierlistBuilder::class, ['tierlist' => $tierlist])
    ->assertForbidden();

Livewire::actingAs(kyle())
    ->test(ListTierlists::class)
    ->filterTable('hide_incomplete', false)
    ->assertCanSeeTableRecords([$completed, $incomplete]);
```

Pass `['record' => $model->getKey()]` for Filament view and edit pages. `call('moveItem', ...)` is how a `wire:sort` handler gets exercised — drive the method, then assert on the refreshed model.

Filament surfaces have their own assertions; use them instead of text:

- **Table columns:** `assertTableColumnStateSet('source', $name, $record)`, `assertTableColumnFormattedStateSet()`
- **Infolist sections and entries:** give a section a `->key('money')` and assert `assertSchemaComponentVisible('money')` / `assertSchemaComponentHidden('money')`; entries are keyed by their state path, prefixed by a keyed section (`assertSchemaComponentStateSet('money.billing_country', 'DE')`)
- **Actions:** `assertActionVisible(TestAction::make('viewUser')->schemaComponent('user.name'))`, `assertActionDoesNotExist(TestAction::make('edit')->table($record))`
- **Widgets:** `[$paid, $allPro] = widgetStats($widget)` and `[$rankings, $tierlists] = widgetChartData($widget)` read a widget's own numbers in display order

## Faking the Outside World

`Tests\TestCase::setUp()` seals the suite: `Http::preventStrayRequests()`, a Stripe client whose every call fails as if Stripe could not be reached, and every Discord log channel sent to a `NullHandler`. `phpunit.xml` pins dummy Spotify and Stripe credentials, empty Discord webhooks, `LOG_CHANNEL=null` and `NIGHTWATCH_ENABLED=false`, so a run never uses your `.env` secrets or writes to `storage/logs`. The whole suite passes offline (`unshare -rn php vendor/bin/pest`).

- **Spotify:** `fakeSearch()`, `fakeDiscography()` and `fakePlaylist()` fake `accounts.spotify.com` and `api.spotify.com`. The fixtures describe Tame Impala's *Currents*, so a failing assertion reads like a record rather than like `album-1`.
- **A guard that should stop a Spotify call:** the Spotify actions catch every exception, so a blocked stray request is swallowed rather than failing the test. Call `Http::fake()` first and `Http::assertNothingSent()` last.
- **Stripe:** `fakeStripeServices(['checkout' => ..., 'invoices' => ...])` binds stand-ins for the services a test needs; anything else stays unreachable. See the `cashier-stripe-development` skill's `references/testing.md`.
- **Notifications:** `Notification::fake()` in `beforeEach`, and call it **after** factory setup — factories rely on model events, so faking first produces broken models.

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
php -d memory_limit=512M artisan test --compact --parallel
```

Run the narrowest thing that proves the change, then `vendor/bin/pint --dirty --format agent`. CI runs `php vendor/bin/pest --parallel` with `memory_limit=512M` — the ranking export test builds an xlsx in memory, so a lower limit fails there first. A random-order run (`vendor/bin/pest --order-by=random`) catches tests that lean on each other.

## Pitfalls

- Asserting copy, a record's name, or a bare number (see Assertions)
- An assertion the layout or `<head>` satisfies on its own
- A `assertDontSee` with no positive control
- Hard-coding a config limit, a seeded row or a factory default
- Comparing an unordered query's results with `toBe([...])`
- `kyle()` or `is_dev` in a Feature test
- Writing `$this->get()` instead of the imported `get()`
- Dropping a test at the root of `tests/Feature/` instead of its product area
- Creating `tests/Unit/` or `tests/Browser/` for a test that belongs in an existing suite
- Reusing a helper name that already exists somewhere in `tests/Helpers/`, or giving a new one a generic name
- `Notification::fake()` or `Event::fake()` before factory calls
- Editing a Blade file to break behaviour and forgetting `php artisan view:clear` afterwards
