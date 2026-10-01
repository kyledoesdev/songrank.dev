# Advanced Query Patterns

Every example here is from this codebase. Read the builder before adding to it — `app/QueryBuilders/` is where these live.

## Reusable Queries Go in a Custom Builder, Not a `scopeX` Method

This application does not use `scopeFoo()` local scopes. Each model with non-trivial reads has a dedicated builder attached with `#[UseEloquentBuilder]`, and scopes are plain methods returning `static`:

```php
#[UseEloquentBuilder(TierlistQueryBuilder::class)]
class Tierlist extends Model {}

class TierlistQueryBuilder extends Builder
{
    public function public(): static
    {
        return $this->where('is_public', true);
    }

    public function completed(): static
    {
        return $this->where('is_complete', true);
    }
}
```

Page-shaped scopes are named after the surface they serve and compose the small ones — `forExplorePage()`, `forProfilePage()`, `forDashboard()`, `forNewsletter()`. A page's query belongs in the builder, not assembled inside the Livewire component.

Note the `->newQuery()` at the top of each page-shaped scope: these are entry points, and starting fresh keeps a caller's leftover constraints from leaking in.

## Use `addSelect()` Subqueries for a Single Derived Value

When a card needs one flag or one timestamp, pull it in the main query instead of eager-loading a relationship to compute it. `RankingQueryBuilder::withHasPodcastEpisode()` answers "does this ranking contain a podcast episode?" without touching the songs relation:

```php
public function withHasPodcastEpisode()
{
    return $this->addSelect([
        'has_podcast_episode' => function ($q) {
            $q->selectRaw('CASE WHEN rankings.type = ? OR EXISTS (
                SELECT 1 FROM songs
                INNER JOIN artists ON songs.artist_id = artists.id
                WHERE songs.ranking_id = rankings.id
                AND artists.is_podcast = 1
            ) THEN 1 ELSE 0 END', [RankingType::SHOW->value]);
        },
    ]);
}
```

Add `->withCasts(['column' => 'datetime'])` when the derived value is a date — a subquery column arrives as a raw string otherwise.

## Use One Grouped Aggregate Instead of a Count per Case

`TierlistQueryBuilder::countsByTypeFor()` replaces three per-type counts with one query. Types the user has none of are simply absent from the result, which the caller handles with a default:

```php
public function countsByTypeFor(User $user): Collection
{
    return $this->newQuery()
        ->where('user_id', $user->getKey())
        ->selectRaw('type, count(*) as total')
        ->groupBy('type')
        ->pluck('total', 'type');
}
```

`selectRaw("count(case when status = 'x' then 1 end) as x")` is the variant to reach for when the buckets are fixed rather than data-driven. Add `->toBase()` when you only need scalars and no model hydration.

## Constrain Eager Loads for Feed Cards

A feed of twelve tier lists must not drag every tier of every list into memory. `withTopTier()` loads just enough board to draw a preview, while `withBoard()` is the full load reserved for the builder and show pages:

```php
public function withTopTier(int $tiers = 5, int $items = 5): static
{
    return $this->with([
        'tiers' => fn (Relation $query) => $query->where('is_bank', false)->orderBy('position')->limit($tiers),
        'tiers.items' => fn (Relation $query) => $query->orderBy('position')->limit($items),
        'tiers.items.entryable',
    ]);
}
```

Laravel 12+ limits eagerly loaded records natively, so `limit()` inside a `with()` closure is the supported way to do this — no external package.

The same idea with a single row: `->with('songs', fn ($query) => $query->where('rank', 1))` on the rankings feed, where a card only shows the number one song.

## Polymorphic Sources Need `whereHasMorph`

The general advice — prefer `whereIn()` with a subquery over `whereHas()`, because a correlated `EXISTS` re-executes per row — **does not apply to a morph**. A ranking's `source` may be an `Artist`, `Playlist` or `Show`, each with its own table and its own name column, so there is no single foreign key to feed a `whereIn`:

```php
public function whereSourceNameLike(string $search): static
{
    return $this->whereHasMorph('source', [Artist::class, Playlist::class, Show::class],
        fn (Builder $query, string $type) => $query->where(
            $type === Artist::class ? 'artist_name' : 'name',
            'LIKE',
            "%{$search}%",
        ),
    );
}
```

Keep the type list explicit. `whereHasMorph('source', '*')` would query every morphable table the map knows about.

A tier list is usually named after nothing in particular and often has no source at all, so `TierlistQueryBuilder::whereNameOrSourceLike()` wraps the morph in an `orWhere` alongside the list's own name. Use `whereIn()` + subquery for the ordinary single-table relationship case.

## Use `setRelation()` to Prevent Circular N+1

When a parent is loaded with its children and the view also needs `$child->parent`, inject the already-loaded parent rather than letting Eloquent fire N more queries:

```php
$ranking->load('songs');
$ranking->songs->each->setRelation('ranking', $ranking);
```

## Index to Match the Query, Including `ORDER BY` Order

Individual single-column indexes cannot combine for a multi-column sort — without a compound index in the same order as the `ORDER BY`, the database filesorts.

```php
$table->index(['last_name', 'first_name']);
```

`2026_01_19_233601_add_index_to_ranking_sorting_states.php` is the example here: resuming a ranking looks that row up on every comparison, so it earned an index of its own.

## Sometimes Two Simple Queries Beat One Complex Query

A small, highly selective secondary query passed through `whereIn` often beats a single correlated subquery or join. The extra round-trip is worth it when the secondary query uses its own index — and joins against a has-many duplicate rows, which is why `mostSongs()` reaches for `withCount('songs')` and orders on `songs_count` instead.
