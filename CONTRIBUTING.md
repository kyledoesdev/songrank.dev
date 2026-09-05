# Contributing to SongRank

Thank you for considering contributing to SongRank! To keep things running smoothly, please review the highlights below before opening an issue or pull request.

## Bug Reports

A bug report should include a clear title, a description of the expected vs. actual behavior, and the steps to reproduce it. The more detail you provide, the easier it is to track down and fix. Please search [existing issues](https://github.com/kyledoesdev/songrank.dev/issues) first to avoid duplicates.

## Feature Requests

Open an issue describing the feature and the problem it solves before writing any code. SongRank is a focused platform, so it's best to align on scope before investing time in a pull request.

## Pull Requests

1. Fork the repo and create a branch off `master` (e.g. `feature/explore-filters` or `fix/ranking-resume`).
2. Keep each pull request focused on a single change.
3. Add or update tests for your change — every change should be covered by a test.
4. Make sure the test suite and formatter pass before pushing (see below).
5. Open the pull request against `master` with a short description of what changed and why.

## Local Setup

Standard Laravel 12 / PHP 8.4 app, served by [Laravel Herd](https://herd.laravel.com). Two project-specific notes: `composer install` needs Spatie satis credentials for the licensed packages, and authentication requires Spotify OAuth credentials (`SPOTIFY_CLIENT_ID`, `SPOTIFY_CLIENT_SECRET`, `SPOTIFY_REDIRECT_URI`) in your `.env`.

## Testing

Every change must be covered by a test. Run the suite with:

```bash
php artisan test
```

Tests use an in-memory SQLite database, so no setup is required.

## Coding Style

SongRank follows the standard Laravel coding style, enforced by [Laravel Pint](https://laravel.com/docs/pint). Run it before committing:

```bash
vendor/bin/pint
```

Business logic lives in `app/Actions/` rather than controllers, and the UI is built with Livewire components in `app/Livewire/`. Please follow the conventions already used in sibling files.

## Security Vulnerabilities

Please do **not** open a public issue for security vulnerabilities. Instead, report them privately to [kyledoesdev](https://github.com/kyledoesdev) so they can be addressed before disclosure.

## Code of Conduct

Be respectful and constructive. Harassment or abusive behavior of any kind will not be tolerated.
