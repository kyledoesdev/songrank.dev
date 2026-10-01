---
name: pennant-development
description: "Use when working with Laravel Pennant feature flags in this application. Trigger whenever a task mentions Pennant, feature flags, feature toggles, rollout, the songrank-pro or tierlists flags, EnsureFeaturesAreActive, or gating an unreleased feature. Covers defining class-based features in app/Features, checking them, the 404-not-403 route convention, testing with flags, and pennant:purge. Do not trigger for authorization policies, entitlement, or generic configuration."
license: MIT
metadata:
  author: laravel
  customized-for: songrank.dev
---

# Pennant Features

## A Flag Is a Rollout Switch, Never an Entitlement

This is the single most important rule in this codebase:

- **A flag** answers "does this feature exist for this user yet?"
- **A paid licence row** answers "has this person paid?"

**Never grant Song Rank Pro by activating the `songrank-pro` flag.** Entitlement is a `pro_licenses` row, projected onto `users.is_pro` by `ProLicenseObserver`. The flag only decides whether the Pro *system* is visible at all. Activating a flag to give somebody Pro would make the two sources of truth disagree and bypass every record of a purchase.

## The Flags

Both are class-based, in `app/Features/`, and both currently resolve on `is_dev`:

```php
#[Name('songrank-pro')]
class SongRankPro
{
    public function resolve(?User $user): bool
    {
        if (is_null($user)) {
            return false;
        }

        return $user->is_dev;
    }
}
```

| Class | Name | Gates |
| --- | --- | --- |
| `App\Features\SongRankPro` | `songrank-pro` | `routes/billing.php`, the upgrade UI, allowance copy |
| `App\Features\Tierlists` | `tierlists` | `routes/tierlists.php`, tier list nav and dashboard surfaces |

Conventions to keep:

- `#[Name('kebab-case')]` — the string, not the class name, is what routes and Blade use
- `resolve(?User $user)` is nullable and returns `false` for a guest. A guest is never inside a rollout
- No `Feature::define()` calls in a service provider; class-based features are discovered from `app/Features`

## Gated Routes 404, They Do Not 403

```php
Route::middleware(EnsureFeaturesAreActive::using('tierlists'))->group(function () {
    // ...
});
```

An unreleased product should look **absent**, not forbidden — a 403 confirms the feature exists. This is deliberate and tests depend on it: a user without the flag gets `assertNotFound()`, not `assertForbidden()`.

Entitlement is separate middleware (`RedirectIfAlreadyPro`, `EnsureUserIsPro`) applied inside the flagged group.

## Checking a Flag

```php
if (Feature::active('tierlists')) { /* ... */ }

Feature::for($user)->active('songrank-pro');
```

```blade
@feature('tierlists')
    <x-tierlists.card :$tierlist />
@endfeature
```

Prefer the route middleware for whole surfaces and `@feature` for a nav item or a card. Reaching for `Feature::active()` deep inside an action usually means the gate belongs further out.

## Testing

```php
beforeEach(function () {
    Feature::define('songrank-pro', true);
});
```

- `PENNANT_STORE=array` is set in `phpunit.xml`, so flags never leak between tests
- `Feature::define('name', true)` is the override when the test is about the *feature*, not about the gating
- To test the gating itself, act as a user **without** `is_dev` and assert 404
- `kyle()` (from `tests/Helpers/users.php`) is `is_dev`, so both flags resolve true for them — this is how admin and flagged-feature tests reach their surfaces
- `proUser()` gives a licence but **not** `is_dev`; pass `proUser(['is_dev' => true])` when the test needs both

## Deployment

`php artisan pennant:purge` is a routine deploy step. Resolved values are stored, so changing a `resolve()` method does not change what an existing user sees until the stored value is purged.

## Pitfalls

- Activating a flag to hand out a paid feature
- Expecting 403 from a gated route
- Forgetting `?User` on `resolve()`, which throws for guests
- Assuming a `resolve()` change takes effect without `pennant:purge`
- Adding a `Feature::define()` in a provider when a class in `app/Features/` is the convention
