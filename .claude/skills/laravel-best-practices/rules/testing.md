# Testing Best Practices

## Database Refresh Is Already Decided

`tests/Pest.php` binds `RefreshDatabase` to the `Feature` and `Filament` suites and deliberately leaves the `Platform` suite without it. Do not add the trait to individual test files, and do not swap in `LazilyRefreshDatabase` — the suite runs against in-memory SQLite, where there is no existing schema to skip migrating, and CI runs `pest --parallel`.

See the `pest-testing` skill for this project's full test conventions: the three suites, `describe()` blocks, `use function Pest\Laravel\…`, and the shared helpers in `tests/Helpers/`.

## Pick the Assertion That Matches the Claim

`assertModelExists($user)` is the right call when existence is the whole claim — it is more expressive and fails more clearly than `assertDatabaseHas('users', ['id' => $user->id])`.

It is the wrong call when the **column values** are the point. The billing suite asserts on what Stripe produced:

```php
assertDatabaseHas('pro_licenses', [
    'id' => $license->getKey(),
    'status' => ProLicenseStatus::ACTIVE->value,
    'amount_total' => 1000,
    'currency' => 'usd',
]);
```

`assertModelExists` cannot express that, and `expect($license->fresh()->status)` would only check one field at a time. Use `assertDatabaseHas` for state, `assertModelExists` for existence, `assertDatabaseCount` for idempotency.

## Use Factory States and Sequences

Named states make tests self-documenting. Sequences eliminate repetitive setup.

Incorrect: `User::factory()->create(['email_verified_at' => null]);`

Correct: `User::factory()->unverified()->create();`

## Use `Exceptions::fake()` to Assert Exception Reporting

Instead of `withoutExceptionHandling()`, use `Exceptions::fake()` to assert the correct exception was reported while the request completes normally.

## Call `Event::fake()` After Factory Setup

Model factories rely on model events (e.g., `creating` to generate UUIDs). Calling `Event::fake()` before factory calls silences those events, producing broken models.

Incorrect: `Event::fake(); $user = User::factory()->create();`

Correct: `$user = User::factory()->create(); Event::fake();`

## Use `recycle()` to Share Relationship Instances Across Factories

Without `recycle()`, nested factories create separate instances of the same conceptual entity.

```php
Ticket::factory()
    ->recycle(Airline::factory()->create())
    ->create();
```
