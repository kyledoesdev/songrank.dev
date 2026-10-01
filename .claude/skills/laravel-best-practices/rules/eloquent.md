# Eloquent Best Practices

## Use Correct Relationship Types

Use `hasMany`, `belongsTo`, `morphMany`, etc. with proper return type hints.

```php
public function comments(): HasMany
{
    return $this->hasMany(Comment::class);
}

public function author(): BelongsTo
{
    return $this->belongsTo(User::class, 'user_id');
}
```

## Reusable Queries Go in a Custom Builder

**This application does not use `scopeFoo()` local scopes.** Every model with non-trivial reads has a dedicated builder in `app/QueryBuilders/`, attached with `#[UseEloquentBuilder]`, where scopes are plain methods returning `static`:

```php
#[UseEloquentBuilder(RankingQueryBuilder::class)]
class Ranking extends Model {}
```

```php
class RankingQueryBuilder extends Builder
{
    public function public(): static
    {
        return $this->where('is_public', true);
    }

    public function completed(): static
    {
        return $this->where('is_ranked', true);
    }
}
```

Then `Ranking::query()->public()->completed()`. Page-shaped scopes (`forExplorePage()`, `forDashboard()`) compose the small ones, so a page's query lives in one place instead of being assembled in the Livewire component. See `rules/advanced-queries.md` for the fuller picture.

Add a builder when you add a model that gets queried more than trivially, and name it `<Model>QueryBuilder`.

## Apply Global Scopes Sparingly

Global scopes silently modify every query on the model, making debugging difficult. Prefer local scopes and reserve global scopes for truly universal constraints like soft deletes or multi-tenancy.

Incorrect (global scope for a conditional filter):
```php
class PublishedScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->where('published', true);
    }
}
// Now admin panels, reports, and background jobs all silently skip drafts
```

Correct (a builder method you opt into):
```php
public function completed(): static
{
    return $this->where('is_ranked', true);
}

Ranking::query()->completed()->paginate(); // Explicit
Ranking::query()->paginate();              // Filament sees everything
```

The one global scope in this codebase is `Song`'s `default_order`, which orders songs by `rank` ascending. That qualifies because a song's rank *is* its identity in a ranking — there is no surface that wants songs in insertion order. `SoftDeletes` on `Ranking` and `Tierlist` is the other accepted case.

## Define Attribute Casts

Use the `casts()` method (or `$casts` property following project convention) for automatic type conversion.

```php
protected function casts(): array
{
    return [
        'is_active' => 'boolean',
        'metadata' => 'array',
        'total' => 'decimal:2',
    ];
}
```

## Cast Date Columns Properly

Always cast date columns. Use Carbon instances in templates instead of formatting strings manually.

Incorrect:
```blade
{{ Carbon::createFromFormat('Y-d-m H-i', $order->ordered_at)->toDateString() }}
```

Correct:
```php
protected function casts(): array
{
    return [
        'ordered_at' => 'datetime',
    ];
}
```

```blade
{{ $order->ordered_at->toDateString() }}
{{ $order->ordered_at->format('m-d') }}
```

## Use `whereBelongsTo()` for Relationship Queries

Cleaner than manually specifying foreign keys.

Incorrect:
```php
Post::where('user_id', $user->id)->get();
```

Correct:
```php
Post::whereBelongsTo($user)->get();
Post::whereBelongsTo($user, 'author')->get();
```

The builders in this codebase take a `User` and write `->where('user_id', $user->getKey())`. `getKey()` rather than `->id` is the convention worth keeping; match the surrounding file on the rest rather than converting one method in isolation.

## Avoid Hardcoded Table Names in Queries

Never use string literals for table names in raw queries, joins, or subqueries. Hardcoded table names make it impossible to find all places a model is used and break refactoring (e.g., renaming a table requires hunting through every raw string).

Incorrect:
```php
DB::table('users')->where('active', true)->get();

$query->join('companies', 'companies.id', '=', 'users.company_id');

DB::select('SELECT * FROM orders WHERE status = ?', ['pending']);
```

Correct — reference the model's table:
```php
DB::table((new User)->getTable())->where('active', true)->get();

// Even better — use Eloquent or the query builder instead of raw SQL
User::where('active', true)->get();
Order::where('status', 'pending')->get();
```

Prefer Eloquent queries and relationships over `DB::table()` whenever possible — they already reference the model's table. When `DB::table()` or raw joins are unavoidable, always use `(new Model)->getTable()` to keep the reference traceable.

**Exception — migrations:** In migrations, hardcoded table names via `DB::table('settings')` are acceptable and preferred. Models change over time but migrations are frozen snapshots — referencing a model that is later renamed or deleted would break the migration.
