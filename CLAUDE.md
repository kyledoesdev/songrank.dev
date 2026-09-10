# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

songrank.dev — a Laravel 13 application that ranks songs via a merge-sort algorithm. Users authenticate with Spotify, select an artist or playlist, then make pairwise song comparisons until a full ranking is produced.

## Common Commands

### Development
- `npm run dev` — Start Vite dev server (hot reload for assets)
- `php artisan serve` — Start PHP dev server (or use Laravel Herd)
- `php artisan migrate` — Run database migrations

### Testing
- `php artisan test` — Run all Pest tests
- `php artisan test --filter=RankingCreationTest` — Run a specific test file
- `php artisan test --filter="test name here"` — Run a single test by name
- `php artisan test --testsuite=Feature` / `--testsuite=Filament` / `--testsuite=Platform` — Run one suite
- Tests use SQLite in-memory database (configured in `phpunit.xml`)

#### Test Suites and Directories
Three top-level suites are registered in `phpunit.xml`, and all are bound to `Tests\TestCase` in `tests/Pest.php`:

- **Feature** (`tests/Feature/`) — application behaviour; gets `RefreshDatabase`. Grouped by product area:
    - `Account/` — signed-in user's own surfaces (profile, settings, notification bell)
    - `Auth/` — Spotify OAuth login, callback, and logout
    - `Discovery/` — public browse surfaces (explore, leaderboards)
    - `Pages/` — simple content-driven pages (faq, support)
    - `Rankings/` — the core ranking domain (setup, algorithm, comments, export, management)
    - `Billing/` — Song Rank Pro: checkout, Stripe webhooks, licences, the billing page
- **Filament** (`tests/Filament/`) — the admin panel; gets `RefreshDatabase`. **Mirrors the `app/Filament/` directory structure**, so a test sits at the same path as the code it covers:
    - `app/Filament/Resources/Rankings/…` → `tests/Filament/Resources/Rankings/…`
    - `app/Filament/Widgets/…` → `tests/Filament/Widgets/…`
    - Name files after the surface under test (`RankingTableFiltersTest`, `RankingInfolistTest`, `LoginsWidgetTest`), not after the resource as a whole — one resource has several surfaces (form, table, infolist, filters, relation managers)
- **Platform** (`tests/Platform/`) — codebase-wide checks that aren't feature behaviour, e.g. Pest arch tests. No `RefreshDatabase`.

Anything under `app/Filament/` is tested in the **Filament** suite, not `Feature`. Everything else goes in the `Feature/` directory matching its product area; add a new subdirectory only when an area has no existing home.

#### Testing the Admin Panel
- The panel is gated by the `is_dev` flag, so act as an admin with the global `kyle()` helper from `tests/Pest.php` — the project's only admin persona
- Filament pages and widgets are Livewire components — test them with `Livewire::actingAs($user)->test(ListRankings::class)`, passing `['record' => $model->getKey()]` for view/edit pages

#### Test File Structure
Every Pest test file follows this order, top to bottom:
1. **Imports** — model/class `use` statements, then `use function Pest\Laravel\...` function imports
2. **`beforeEach()`** — shared arrangement (only when needed)
3. **`describe()` blocks** — every test lives inside a describe group
4. **Helper functions** — file-specific helpers at the bottom

Additional conventions:
- Use `Pest\Laravel` functions (`get()`, `actingAs()`, `assertGuest()`, ...) instead of `$this->get()` etc. — Intelephense cannot resolve `$this` inside Pest closures
- Avoid `$this->property` state in `beforeEach()` for the same reason; prefer helper functions
- Cross-file helpers (e.g. `publicCompletedRanking()`, `kyle()`) live in `tests/Pest.php`; helper function names are global, so they must be unique across the suite

### Code Formatting
- `./vendor/bin/pint` — Run Laravel Pint (PHP code formatter, PSR-12 + Laravel conventions)

### Build
- `npm run build` — Production Vite build
- `composer install` — Install PHP dependencies (requires Spatie satis auth for licensed packages)

## Tech Stack

- **Backend:** PHP 8.4, Laravel 13, Livewire (full-stack components)
- **Frontend:** Blade templates, Alpine.js, Tailwind CSS v4, Vite
- **Admin Panel:** Filament 5
- **Auth:** Laravel Socialite with Spotify OAuth provider
- **Testing:** Pest PHP 4 (feature tests), Playwright (browser tests)
- **Database:** MySQL (production), SQLite (testing)
- **Error Tracking:** Bugsnag
- **Deployment:** Laravel Forge

## Architecture

### Action Pattern
Business logic lives in `app/Actions/` rather than controllers or Livewire components. Actions are organized by domain:
- `Actions/Rankings/` — Ranking creation and completion (`CompleteSongRankProcess`, `StoreArtistRanking`, etc.)
- `Actions/Spotify/` — Spotify API interactions (`GetArtistSongs`, `GetArtistAppearsOnSongs`, `GetPlaylistTracks`, `RefreshToken`). `GetArtistAppearsOnSongs` is deliberately separate: featured tracks are expensive to fetch for prolific guests, so `count()` probes cheaply during artist selection and `handle()` only runs when the user toggles the featured list on
- `Actions/Comments/` — Comment operations
- `Actions/Billing/` — Song Rank Pro purchase lifecycle (`StartProCheckout`, `FulfillProPurchase`, `RevokeProLicense`, `GrantProLicense`, `HandleStripeWebhook`)

### Livewire Components
The primary UI layer. Each page is a Livewire component in `app/Livewire/`:
- `SongRank/SongRankProcess` — Core ranking algorithm UI (pair comparisons)
- `SongRank/SongRankSetup` — A shell that renders the setup component for the chosen `RankingType` (mapped in `SongRankSetup::setupComponent()`). Each of `SongRank/Setup/{Artist,Playlist,Show}Setup` owns its own search, track selection, and persistence; shared behaviour lives in `SongRank/Concerns/` traits (`HasTrackList`, `HasRankingForm`, `HasSetupFlashErrors`), and shared markup lives in `resources/views/livewire/song-rank/setup/partials/` Livewire partials
- `Dashboard/Dashboard` — User's rankings overview
- `Explorer` — Browse public rankings
- `Ranking/Ranking` — View a completed ranking
- `Profile/Profile`, `Profile/Settings` — User profile and preferences

### Song Rank Pro (Billing)

A one-time $10 purchase, gated by the `songrank-pro` Pennant flag (`app/Features/SongRankPro.php`, currently `is_dev` only).

Two separate concepts, deliberately never merged:

- **The flag** answers "does the Pro system exist for this user yet?" — a rollout switch. `pennant:purge` is a routine deploy step.
- **A `pro_licenses` row** answers "has this person paid?" — the entitlement. **Never grant Pro by activating a Pennant flag.**

`users.is_pro` is a projection of the licences table, not a second source of truth. `ProLicenseObserver` recomputes it whenever a licence is saved, deleted or restored, so `$user->is_pro` is a column read rather than a query — it is called from the nav, the setup header, the middleware and the billing page on a single page load. The one gap is mass assignment: `ProLicense::query()->update(...)` fires no model events, so anything doing that must call `$user->syncProStatus()` itself (`DeleteUserJob` is the only place that currently does).

Other notes:

- Cashier's `WebhookController` handles only eight subscription-shaped events. The one-off purchase lifecycle is ours, routed by `HandleStripeWebhook` off the `WebhookReceived` event. Stripe's event names and payment statuses are modelled as `StripeWebhookEvent` and `StripePaymentStatus` — resolve them with `tryFrom()` rather than comparing raw strings, and note that Stripe event names contain dots, so they can never be looked up with `data_get()`.
- Fulfillment runs from both the webhook and the success redirect, and Stripe retries webhooks for three days, so it must stay safe to run repeatedly. It **claims** the license with a single conditional `where status = pending` update and checks the affected row count — reading the status into PHP and then writing would leave a window where two callers both pass the check and both send a receipt. The loser backfills any field the winner could not populate (Stripe raises the invoice asynchronously, so an early caller can see `session.invoice` as null).
- `config/cashier.php` pins `webhook.events` to `StripeWebhookEvent`, **not** Cashier's `DEFAULT_EVENTS`. The defaults are all subscription-shaped; leaving them would make `php artisan cashier:webhook` create an endpoint that never delivers `checkout.session.completed` and silently stops every purchase from fulfilling.
- Never let a money event pass in silence. A refund or chargeback for a payment intent no license claims is logged loudly, because Stripe will keep retrying the original `checkout.session.completed` and could hand Pro to somebody already refunded.
- Money is stored in integer minor units and displayed via `Cashier::formatAmount()`. Never floats.
- **Store as little as possible.** No card brand or last four, no payment-method columns on `users`, no trial columns, and no subscription tables — Cashier's published migrations for those were deliberately removed. Stripe holds the card; we hold an invoice URL. Anything added here has to be disclosed in the privacy policy, so the bar is "the product genuinely cannot work without it".
- Ranking limits live in `config/billing.php` (`null` means unlimited). `$user->rankingAllowance()` returns a `RankingAllowance`. The block is a UI one: `setup-header.blade.php` swaps the search box for an upgrade prompt when `rankingLimitReached()`, so a capped user never reaches a search. `ensureCanCreateRanking()` at the top of each setup component's `search()` is only a backstop for stale pages. **There is no ranking policy** — being told 403 after clicking "begin ranking" is the wrong place to find out.
- The checkout session is treated as a resource: `store`, `show`, `destroy`. `show` and `destroy` answer GET because Stripe redirects the customer's browser to them. Entitlement is handled by `RedirectIfAlreadyPro` on the route so the controller has a single return type.
- Invoice PDFs come from Stripe (`invoice_creation` on the checkout session). There is no PDF library and none should be added.
- Automatic tax is opt-in via `STRIPE_AUTOMATIC_TAX`, and gates both `Cashier::calculateTaxes()` and the session's `automatic_tax`/`tax_id_collection`. Sending those before the Stripe account has a head office address makes Stripe **reject the whole checkout session**, so it stays off until Stripe Tax is genuinely configured.
- `ProUpgradeThankYou` renders an `EmailTemplate` row named in `ProUpgradeThankYou::TEMPLATE`, edited in Filament. Until that row exists the notification sends nothing — `via()` returns no channels — so a missing template can never fail a purchase.

### Custom Query Builders
`app/QueryBuilders/RankingQueryBuilder.php` provides scoped queries: `forExplorePage()`, `forProfilePage()`, `forNewsletter()`.

### Key Models and Relationships
- `User` → has many `Ranking`, has one `UserPreference`
- `Ranking` → belongs to `User`, has one `Artist`/`Playlist`, has many `Song`, has one `RankingSortingState`
- `Song` → `featured_artist` marks tracks the ranked artist only guests on; for those rows `artist_id` points at the track's *primary* artist, while every other row's `artist_id` is the artist the *ranking* belongs to
- `RankingSortingState` — Persists merge-sort algorithm state so rankings can be resumed across sessions

### Global Helpers
`app/global/functions.php` is autoloaded via Composer (see `composer.json` `files` array).

### Admin Panel
Filament admin at `/admin`, restricted to users with `is_dev` flag via `IsDeveloper` middleware. Resources in `app/Filament/Resources/`.

### Routes
All web routes are in `routes/web.php`. Public routes use `Route::livewire()`. Auth-protected routes are wrapped in `auth` middleware. Developer-only routes additionally use `IsDeveloper` middleware.

## CI/CD

GitHub Actions (`.github/workflows/tests.yml`) runs on push/PR to `master`:
- PHP 8.4, Node 22
- Requires `SPATIE_LICENSE_EMAIL` and `SPATIE_LICENSE_KEY` secrets for Spatie Composer auth
- Builds Vite assets and installs Playwright browsers before running tests

## Environment Setup

Copy `.env.example` to `.env` and configure:
- `SPOTIFY_CLIENT_ID`, `SPOTIFY_CLIENT_SECRET`, `SPOTIFY_REDIRECT_URI` — Required for Spotify OAuth
- `DISCORD_WEBHOOK` — Optional, for Discord logging
- Database defaults to MySQL; tests override to SQLite

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4.15
- filament/filament (FILAMENT) - v5
- laravel/framework (LARAVEL) - v12
- laravel/nightwatch (NIGHTWATCH) - v1
- laravel/prompts (PROMPTS) - v0
- laravel/socialite (SOCIALITE) - v5
- livewire/livewire (LIVEWIRE) - v4
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- rector/rector (RECTOR) - v2
- alpinejs (ALPINEJS) - v3
- vue (VUE) - v3
- tailwindcss (TAILWINDCSS) - v4

## Skills Activation

This project has domain-specific skills available. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

- `pest-testing` — Tests applications using the Pest 4 PHP framework. Activates when writing tests, creating unit or feature tests, adding assertions, testing Livewire components, browser testing, debugging test failures, working with datasets or mocking; or when the user mentions test, spec, TDD, expects, assertion, coverage, or needs to verify functionality works.
- `tailwindcss-development` — Styles applications using Tailwind CSS v4 utilities. Activates when adding styles, restyling components, working with gradients, spacing, layout, flex, grid, responsive design, dark mode, colors, typography, or borders; or when the user mentions CSS, styling, classes, Tailwind, restyle, hero section, cards, buttons, or any visual/UI changes.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan

- Use the `list-artisan-commands` tool when you need to call an Artisan command to double-check the available parameters.

## URLs

- Whenever you share a project URL with the user, you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain/IP, and port.

## Tinker / Debugging

- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.
- Use the `database-schema` tool to inspect table structure before writing migrations or models.

## Reading Browser Logs With the `browser-logs` Tool

- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)

- Boost comes with a powerful `search-docs` tool you should use before trying other approaches when working with Laravel or Laravel ecosystem packages. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic-based queries at once. For example: `['rate limiting', 'routing rate limiting', 'routing']`. The most relevant results will be returned first.
- Do not add package names to queries; package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'.
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit".
3. Quoted Phrases (Exact Position) - query="infinite scroll" - words must be adjacent and in that order.
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit".
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms.

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.

## Constructors

- Use PHP 8 constructor property promotion in `__construct()`.
    - `public function __construct(public GitHub $github) { }`
- Do not allow empty `__construct()` methods with zero parameters unless the constructor is private.

## Type Declarations

- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<!-- Explicit Return Types and Method Params -->
```php
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
```

## Enums

- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.

## Comments

- Prefer PHPDoc blocks over inline comments. Never use comments within the code itself unless the logic is exceptionally complex.

## PHPDoc Blocks

- Add useful array shape type definitions when appropriate.

=== herd rules ===

# Laravel Herd

- The application is served by Laravel Herd and will be available at: `https?://[kebab-case-project-dir].test`. Use the `get-absolute-url` tool to generate valid URLs for the user.
- You must not run any commands to make the site available via HTTP(S). It is always available through Laravel Herd.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.
- Use the Laravel Str facade functions over PHP string functions
- When it makes sense, use Laravel Collection over traditional arrays
- Use Collections and collection chains over foreach loops for data processing.

## Database

- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries.
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations. Explain why you did so.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## Controllers & Validation

- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

## Authentication & Authorization

- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Queues

- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

## Configuration

- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v12 rules ===

# Laravel 12

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

## Laravel 12 Structure

- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app\Console\Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== boost/core rules ===

# Laravel Boost

- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan

- Use the `list-artisan-commands` tool when you need to call an Artisan command to double-check the available parameters.

## URLs

- Whenever you share a project URL with the user, you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain/IP, and port.

## Tinker / Debugging

- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.
- Use the `database-schema` tool to inspect table structure before writing migrations or models.

## Reading Browser Logs With the `browser-logs` Tool

- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)

- Boost comes with a powerful `search-docs` tool you should use before trying other approaches when working with Laravel or Laravel ecosystem packages. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic-based queries at once. For example: `['rate limiting', 'routing rate limiting', 'routing']`. The most relevant results will be returned first.
- Do not add package names to queries; package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'.
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit".
3. Quoted Phrases (Exact Position) - query="infinite scroll" - words must be adjacent and in that order.
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit".
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms.

=== pint/core rules ===

# Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.
- CRITICAL: ALWAYS use `search-docs` tool for version-specific Pest documentation and updated code examples.
- IMPORTANT: Activate `pest-testing` every time you're working with a Pest or testing-related task.

=== tailwindcss/core rules ===

# Tailwind CSS

- Always use existing Tailwind conventions; check project patterns before adding new ones.
- IMPORTANT: Always use `search-docs` tool for version-specific Tailwind CSS documentation and updated code examples. Never rely on training data.
- IMPORTANT: Activate `tailwindcss-development` every time you're working with a Tailwind CSS or styling-related task.

=== filament/filament rules ===

## Filament

- Filament is used by this application. Follow existing conventions for how and where it's implemented.
- Filament is a Server-Driven UI (SDUI) framework for Laravel that lets you define user interfaces in PHP using structured configuration objects. Built on Livewire, Alpine.js, and Tailwind CSS.
- Use the `search-docs` tool for official documentation on Artisan commands, code examples, testing, relationships, and idiomatic practices.

### Artisan

- Use Filament-specific Artisan commands to create files. Find them with `list-artisan-commands` or `php artisan --help`.
- Inspect required options and always pass `--no-interaction`.

### Patterns

Use static `make()` methods to initialize components. Most configuration methods accept a `Closure` for dynamic values.

Use `Get $get` to read other form field values for conditional logic:

<code-snippet name="Conditional form field" lang="php">
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;

Select::make('type')
    ->options(CompanyType::class)
    ->required()
    ->live(),

TextInput::make('company_name')
    ->required()
    ->visible(fn (Get $get): bool => $get('type') === 'business'),

</code-snippet>

Use `state()` with a `Closure` to compute derived column values:

<code-snippet name="Computed table column" lang="php">
use Filament\Tables\Columns\TextColumn;

TextColumn::make('full_name')
    ->state(fn (User $record): string => "{$record->first_name} {$record->last_name}"),

</code-snippet>

Actions encapsulate a button with optional modal form and logic:

<code-snippet name="Action with modal form" lang="php">
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;

Action::make('updateEmail')
    ->form([
        TextInput::make('email')->email()->required(),
    ])
    ->action(fn (array $data, User $record): void => $record->update($data)),

</code-snippet>

### Testing

Authenticate before testing panel functionality. Filament uses Livewire, so use `livewire()` or `Livewire::test()`:

<code-snippet name="Filament Table Test" lang="php">
    livewire(ListUsers::class)
        ->assertCanSeeTableRecords($users)
        ->searchTable($users->first()->name)
        ->assertCanSeeTableRecords($users->take(1))
        ->assertCanNotSeeTableRecords($users->skip(1));

</code-snippet>

<code-snippet name="Filament Create Resource Test" lang="php">
    livewire(CreateUser::class)
        ->fillForm([
            'name' => 'Test',
            'email' => 'test@example.com',
        ])
        ->call('create')
        ->assertNotified()
        ->assertRedirect();

    assertDatabaseHas(User::class, [
        'name' => 'Test',
        'email' => 'test@example.com',
    ]);

</code-snippet>

<code-snippet name="Testing Validation" lang="php">
    livewire(CreateUser::class)
        ->fillForm([
            'name' => null,
            'email' => 'invalid-email',
        ])
        ->call('create')
        ->assertHasFormErrors([
            'name' => 'required',
            'email' => 'email',
        ])
        ->assertNotNotified();

</code-snippet>

<code-snippet name="Calling Actions" lang="php">
    use Filament\Actions\DeleteAction;
    use Filament\Actions\Testing\TestAction;

    livewire(EditUser::class, ['record' => $user->id])
        ->callAction(DeleteAction::class)
        ->assertNotified()
        ->assertRedirect();

    livewire(ListUsers::class)
        ->callAction(TestAction::make('promote')->table($user), [
            'role' => 'admin',
        ])
        ->assertNotified();

</code-snippet>

### Common Mistakes

**Commonly Incorrect Namespaces:**
- Form fields (TextInput, Select, etc.): `Filament\Forms\Components\`
- Infolist entries (for read-only views) (TextEntry, IconEntry, etc.): `Filament\Infolists\Components\`
- Layout components (Grid, Section, Fieldset, Tabs, Wizard, etc.): `Filament\Schemas\Components\`
- Schema utilities (Get, Set, etc.): `Filament\Schemas\Components\Utilities\`
- Actions: `Filament\Actions\` (no `Filament\Tables\Actions\` etc.)
- Icons: `Filament\Support\Icons\Heroicon` enum (e.g., `Heroicon::PencilSquare`)

**Recent breaking changes to Filament:**
- File visibility is `private` by default. Use `->visibility('public')` for public access.
- `Grid`, `Section`, and `Fieldset` no longer span all columns by default.

</laravel-boost-guidelines>
