# Task Scheduling Best Practices

## Schedules Live in `routes/console.php`

`withSchedule()` in `bootstrap/app.php` is intentionally empty. Every scheduled task is declared in `routes/console.php`, and console commands in `app/Console/Commands/` are auto-registered — they need no manual binding.

The house pattern: an explicit timezone, and a failure hook that reports to Discord rather than failing silently.

```php
Schedule::command('newsletter:send')
    ->timezone('America/New_York')
    ->monthlyOn(15, '3:00')
    ->onSuccess(function () {
        Log::channel('discord_other_updates')->info('Newsletter sent successfully.');
    })
    ->onFailure(function () {
        Log::channel('discord_other_updates')->info('Something went wrong sending news letter emails.');
    });
```

- **Always set `->timezone('America/New_York')`.** The app's users are mostly US-based and the digest and newsletter times are product decisions, not server-clock decisions.
- **Always add `->onFailure()`** logging to `discord_other_updates` (the channels are defined in `config/logging.php`). A scheduled task that fails quietly is a task nobody knows is broken.
- `onOneServer()` is not used — this deploys to a single Forge server. Add it if that ever changes, and note it needs a shared cache driver.

Current schedule: `artists:update-images` daily at 08:00, `daily-digest:send` at midnight, `newsletter:send` monthly on the 15th, plus Spatie Health's heartbeat every minute and a daily `model:prune`.

## Use `withoutOverlapping()` on Variable-Duration Tasks

Without it, a long-running task spawns a second instance on the next tick, causing double-processing or resource exhaustion.

## Use `onOneServer()` on Multi-Server Deployments

Without it, every server runs the same task simultaneously. Requires a shared cache driver (Redis, database, Memcached).

## Use `runInBackground()` for Concurrent Long Tasks

By default, tasks at the same tick run sequentially. A slow first task delays all subsequent ones. `runInBackground()` runs them as separate processes.

## Use `environments()` to Restrict Tasks

Prevent accidental execution of production-only tasks (billing, reporting) on staging.

```php
Schedule::command('billing:charge')->monthly()->environments(['production']);
```

## Use `takeUntilTimeout()` for Time-Bounded Processing

A task running every 15 minutes that processes an unbounded cursor can overlap with the next run. Bound execution time.

## Use Schedule Groups for Shared Configuration

Avoid repeating `->onOneServer()->timezone('America/New_York')` across many tasks.

```php
Schedule::daily()
    ->onOneServer()
    ->timezone('America/New_York')
    ->group(function () {
        Schedule::command('emails:send --force');
        Schedule::command('emails:prune');
    });
```
