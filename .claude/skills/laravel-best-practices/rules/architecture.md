# Architecture Best Practices

## Single-Purpose Action Classes

Business logic lives in `app/Actions/<Domain>/` — never in a controller, never in a Livewire component. Scaffold one with the generator from `kyledoesdev/essentials`:

```bash
php artisan make:action Tierlists/StoreTierlist
```

It writes into `app/Actions`, appends an `Action` suffix, and stubs a `handle()` wrapped in `DB::transaction()`. **This codebase drops that suffix** everywhere except the two comment actions (`FilterCommentProfanityAction`, `CustomResolveMentionsAutocompleteAction`), so rename the generated class to match its siblings.

The shape:

```php
final class StoreTierlist
{
    /**
     * @param  array{type: TierlistType, entries: iterable, source?: ?Model}  $attributes
     */
    public function handle(User $user, array $attributes): Tierlist
    {
        return DB::transaction(function () use ($user, $attributes) {
            // ...
        });
    }
}
```

- `final class`, one public `handle()`, explicit parameter and return types
- `DB::transaction()` as soon as the action writes more than one row — but do any HTTP read *before* opening it. `FulfillProPurchase` fetches the Stripe invoice first on purpose: no network call inside a database call
- PHPDoc array shapes for `$attributes` bags
- No constructor unless it genuinely takes a dependency, and no `__invoke()` except for an event listener (`HandleStripeWebhook`)
- Actions compose by instantiation: `(new CreateDefaultTiers)->handle($tierlist)`
- Controllers and Livewire components take them by **method injection** where the container is already resolving the call: `public function store(StartProCheckout $startCheckout)`

A one-line wrapper around a single `Model::create()` is not an action. `GrantProLicense` earns its place because status, source and `purchased_at` are policy; `$user->preferences()->create()` does not.

## Use Dependency Injection

Always use constructor injection. Avoid `app()` or `resolve()` inside classes.

Incorrect:
```php
class ProCheckoutController extends Controller
{
    public function store(): Checkout
    {
        return app(StartProCheckout::class)->handle(Auth::user());
    }
}
```

Correct — let the container hand it over:
```php
class ProCheckoutController extends Controller
{
    public function store(StartProCheckout $startCheckout): Checkout
    {
        return $startCheckout->handle(Auth::user());
    }
}
```

Method injection is the form this codebase uses for controllers and Livewire lifecycle hooks, because the container is already resolving those calls. Actions calling other actions instantiate directly (`(new ResolveTierlistEntries)->handle(...)`) — they take no dependencies, so there is nothing for the container to do.

## External Boundaries Are Not Behind Interfaces Here

This application has exactly one contract, `App\Contracts\SpotifyEntity`, and it is a **shared model shape** — `name()`, `cover()`, `spotifyId()`, `spotifyUrl()` — implemented by `Artist`, `Album`, `Track`, `Playlist` and `Show`. That is what lets a ranking source, a tier list source and a tier list entry each be any of them. It is not a swappable adapter.

There is no `PaymentGateway` interface and no gateway binding. Do not add one:

- **Spotify** is called with the `Http` facade from inside `app/Actions/Spotify/`, and faked in tests with `Http::fake()` (see `fakeSearch()` / `fakeDiscography()` in `tests/Helpers/spotify.php`).
- **Stripe** goes through Cashier directly. Tests bind a fake `StripeClient` into the container instead of hiding Cashier behind an abstraction.

Both boundaries are already testable without an interface, and there is no second provider on the horizon for either. Introduce a contract when a second implementation actually exists — not in anticipation of one.

## Always Order Explicitly

Without an explicit `ORDER BY`, row order is undefined. Every page-shaped scope in `app/QueryBuilders/` ends in an order, and there are two established orderings to reuse:

```php
// Public feeds — newest finished first
->orderBy('completed_at', 'desc')

// Anything the owner sees (dashboard, profile) — in-progress first, then newest
->orderByRaw('completed_at IS NULL DESC, completed_at DESC')
```

The second one is the convention for a user's own records: an unfinished ranking or tier list is the thing they came back to continue, so it sorts above finished work rather than falling to the bottom on a null date.

## Use Atomic Locks for Race Conditions

Prevent race conditions with `Cache::lock()` or `lockForUpdate()`.

```php
Cache::lock('order-processing-'.$order->id, 10)->block(5, function () use ($order) {
    $order->process();
});

// Or at query level
$product = Product::where('id', $id)->lockForUpdate()->first();
```

## Use `mb_*` String Functions

When no Laravel helper exists, prefer `mb_strlen`, `mb_strtolower`, etc. for UTF-8 safety. Standard PHP string functions count bytes, not characters.

Incorrect:
```php
strlen('José');          // 5 (bytes, not characters)
strtolower('MÜNCHEN');  // 'mÜnchen' — fails on multibyte
```

Correct:
```php
mb_strlen('José');             // 4 (characters)
mb_strtolower('MÜNCHEN');     // 'münchen'

// Prefer Laravel's Str helpers when available
Str::length('José');          // 4
Str::lower('MÜNCHEN');        // 'münchen'
```

## Use `defer()` for Post-Response Work

For lightweight tasks that don't need to survive a crash (logging, analytics, cleanup), use `defer()` instead of dispatching a job. The callback runs after the HTTP response is sent — no queue overhead.

Incorrect (job overhead for trivial work):
```php
dispatch(new LogPageView($page));
```

Correct (runs after response, same process):
```php
defer(fn () => PageView::create(['page_id' => $page->id, 'user_id' => auth()->id()]));
```

Use jobs when the work must survive process crashes or needs retry logic. Use `defer()` for fire-and-forget work.

## Use `Context` for Request-Scoped Data

The `Context` facade passes data through the entire request lifecycle — middleware, controllers, jobs, logs — without passing arguments manually.

```php
// In middleware
Context::add('tenant_id', $request->header('X-Tenant-ID'));

// Anywhere later — controllers, jobs, log context
$tenantId = Context::get('tenant_id');
```

Context data automatically propagates to queued jobs and is included in log entries. Use `Context::addHidden()` for sensitive data that should be available in queued jobs but excluded from log context. If data must not leave the current process, do not store it in `Context`.

## Use `Concurrency::run()` for Parallel Execution

Run independent operations in parallel using child processes — no async libraries needed.

```php
use Illuminate\Support\Facades\Concurrency;

[$users, $orders] = Concurrency::run([
    fn () => User::count(),
    fn () => Order::where('status', 'pending')->count(),
]);
```

Each closure runs in a separate process with full Laravel access. Use for independent database queries, API calls, or computations that would otherwise run sequentially.

## Convention Over Configuration

Follow Laravel conventions. Don't override defaults unnecessarily.

Incorrect:
```php
class Customer extends Model
{
    protected $table = 'Customer';
    protected $primaryKey = 'customer_id';

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_customer', 'customer_id', 'role_id');
    }
}
```

Correct:
```php
class Customer extends Model
{
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }
}
```
