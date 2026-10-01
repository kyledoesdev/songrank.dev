# Routing & Controllers Best Practices

## Pages Are Livewire Components, Not Controllers

This application has two controllers — `SpotifyAuthController` and `Billing\ProCheckoutController` — and both exist because an external service redirects a browser to them. **Everything else is a Livewire component registered directly on the route:**

```php
Route::livewire('/explore', Explorer::class)
    ->name('explore')
    ->withHead(title: 'Explore');
```

`withHead()` is `laravel/head` and sets the page title for static pages. There is no `routes/api.php` (it is commented out in `bootstrap/app.php`), no `Route::resource()`, and no Eloquent API Resources. Do not add a controller for a page.

## Routes Are Split by Domain

`routes/web.php` holds public and account routes, then requires `routes/rankings.php`, `routes/billing.php` and `routes/tierlists.php`. A new domain gets its own file, required from `web.php`, with a header comment explaining the gating.

## Flagged Domains 404, They Do Not 403

```php
Route::middleware(EnsureFeaturesAreActive::using('tierlists'))->group(function () {
    // ...
});
```

An unreleased product should look absent, not forbidden. Keep this in mind when writing tests: a user without the flag gets **404**, not 403.

## Resolve the Model in `mount()`, Not by Route Binding

Show routes take a plain `{id}` and the component resolves it. This is deliberate — the lookup needs its eager loads, and whether the record is visible is a product decision rather than a binding failure:

```php
Route::livewire('/tierlist/{id}', TierlistShow::class)->name('tierlist');
```

```php
public function mount($id): void
{
    $this->tierlist = Tierlist::query()
        ->with('user', 'source')
        ->withBoard()
        ->findOrFail($id);

    if (! $this->tierlist->canBeSeen()) {
        abort(404);
    }

    Head::title($this->tierlist->name);
}
```

`canBeSeen()` on the model is the single answer to "may this person look at this", and it `abort(404)`s rather than 403 for the same reason as the flags: a private ranking should not confirm it exists. For an edit surface, `canBeEdited()` with `abort_unless(..., 403)` in `mount()`, and `#[Locked]` on the model property so the ownership check holds for the component's life.

Implicit route model binding and `scopeBindings()` are still the right tools in a controller — there just are not many controllers here.

## Keep Controllers Thin

Both controllers delegate immediately. Business logic goes to `app/Actions/`, which arrives by method injection:

```php
public function store(StartProCheckout $startCheckout): Checkout
{
    return $startCheckout->handle(Auth::user());
}
```

A controller that redirects a browser back from a third party may do slightly more — `ProCheckoutController::show` reads the session id, fulfils, and flashes — but it still owns no domain logic.

## Middleware

- `Authenticate::class` wraps authenticated groups; guests are redirected to `welcome` by `redirectGuestsTo()` in `bootstrap/app.php`
- `IsDeveloper` (from `kyledoesdev/essentials`) gates developer-only routes and the Filament panel on `is_dev`
- `EnsureFeaturesAreActive::using(...)` gates a flagged domain
- `RedirectIfAlreadyPro` and `EnsureUserIsPro` handle entitlement on billing routes, so controllers keep a single return type
