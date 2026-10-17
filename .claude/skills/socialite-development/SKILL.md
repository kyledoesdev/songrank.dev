---
name: socialite-development
description: "Manages this application's Spotify OAuth authentication through Laravel Socialite and socialiteproviders/spotify. Activate when touching login, logout, the OAuth callback, Spotify scopes or tokens, token refresh, the SpotifyAuthenticationService, or when the user mentions social login, OAuth, Socialite, Spotify auth, or authentication problems."
license: MIT
metadata:
  author: laravel
  customized-for: songrank.dev
---

# Socialite Authentication

**Spotify is the only identity provider, and it is also the only account system.** There are no passwords, no registration form and no email/password login — a `users` row exists because somebody authorized the Spotify app. Do not add a second provider or a local password flow without asking.

## How It Is Wired

`socialiteproviders/spotify` is a **community provider**, so it is not auto-discovered. Three pieces:

1. `SocialiteProviders\Manager\ServiceProvider` is registered in `bootstrap/app.php`'s `withProviders()`
2. `AppServiceProvider::boot()` registers the driver on the `SocialiteWasCalled` event
3. `config/services.php` has the `spotify` key — `client_id`, `client_secret`, `redirect`, plus `system_id` (`SYSTEM_SPOTIFY_ID`, the Spotify user treated as the app owner)

`SPOTIFY_REDIRECT_URI` is a **path**, not a full URL (`/login/spotify/callback`). It is appended to `APP_URL`, and the result must match a redirect URI registered on the Spotify app — which is why local development has to run on `https://song-ranker.test` rather than `localhost`.

## The Login Flow

Three routes in `routes/web.php`, all on `SpotifyAuthController`:

```php
Route::get('/login/spotify', [SpotifyAuthController::class, 'login'])->name('spotify.login');
Route::get('/login/spotify/callback', [SpotifyAuthController::class, 'processLogin'])->name('spotify.process_login');
Route::get('/logout', [SpotifyAuthController::class, 'logout'])->name('logout');
```

- **`login()`** — `Socialite::driver('spotify')->scopes(['user-read-email'])->redirect()`. `scopes()` *merges* with the provider's defaults; `setScopes()` would replace them. Adding a scope means re-consent for existing users, so only add one the product actually needs.
- **`processLogin()`** — wraps `->user()` in `try { } catch (InvalidStateException|ClientException)` and redirects back to `welcome` with an error. **Always handle the denied grant**: `user()` throws when somebody declines, and a stale session throws `InvalidStateException`.
- Then `Session::regenerate()` **before** `Auth::login()`, and `LoginStat::increase()` from `kyledoesdev/essentials` after.
- **`logout()`** — `Auth::logout()`, `Session::invalidate()`, `Session::regenerateToken()`, redirect to `welcome`.

## `SpotifyAuthenticationService`

The callback hands the Socialite user straight to `App\Services\SpotifyAuthenticationService`, constructed with it. That service owns everything about turning a Spotify identity into a SongRank user:

- `getSongRankUser()` does `User::withTrashed()->updateOrCreate(['spotify_id' => ...], [...])` — `withTrashed()` matters, because a soft-deleted account must be found rather than duplicated
- It stores the access and refresh tokens as `external_token` / `external_refresh_token`, plus timezone (via the `timezone()` helper from essentials), IP, user agent, platform and a location packet
- `restoreUserIfAccountWasPreviouslyDeleted()` is checked first, and flashes the "welcome back" message
- A newly created user gets `preferences()->create()`

Put new identity logic here, not in the controller.

## Tokens Expire — Refresh Before Every API Call

Spotify access tokens are short-lived, and every Spotify action begins by refreshing:

```php
$success = (new RefreshToken)->handle($user);

if (! $success) {
    return null;
}
```

`App\Actions\Spotify\RefreshToken` exchanges `external_refresh_token` at `accounts.spotify.com` and writes the new `external_token` back. A failed refresh means the user has to log in again — the action returns `false` and the caller returns `null` rather than throwing. `users.external_token` and `external_refresh_token` are nullable (`2026_08_19_000001_make_spotify_tokens_nullable_on_users_table`), so never assume a token is present.

## Testing

`tests/Feature/Auth/SpotifyAuthenticationTest.php` covers login, callback and logout. The Spotify HTTP calls are faked with `Http::fake()` through `fakeSearch()` / `fakeDiscography()` in `tests/Helpers/spotify.php`, both of which also stub `https://accounts.spotify.com/*` so the token refresh succeeds. `Socialite::fake()` is available for driving the redirect and callback themselves.

## Pitfalls

- `SPOTIFY_REDIRECT_URI` is a path; concatenating a full URL produces a redirect mismatch Spotify rejects
- The callback must survive a declined grant and an expired state — both are caught already, keep it that way
- `scopes()` merges, `setScopes()` replaces
- Don't skip `RefreshToken` at the top of a new Spotify action; the token in the database is probably stale
- Don't look a user up with `User::where('spotify_id', ...)` without `withTrashed()` if the path can be reached by a returning deleted account
- `stateless()` and PKCE are not used here — this is a session-based web app
