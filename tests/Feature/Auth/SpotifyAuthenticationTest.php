<?php

use App\Models\User;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Mockery\MockInterface;
use SocialiteProviders\Manager\OAuth2\User as SpotifyUser;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;

beforeEach(function () {
    Http::fake();
});

describe('redirecting to spotify', function () {
    test('the login route hands off to spotify', function () {
        get(route('spotify.login'))
            ->assertRedirectContains('accounts.spotify.com/authorize');
    });
});

describe('returning to where the user was headed', function () {
    test('a guest upgrading to pro lands on the billing page after logging in', function () {
        get(route('spotify.login', ['to' => 'billing']));

        fakeSpotifyLogin(id: 'spotify-abc', email: 'new@example.com');

        get(route('spotify.process_login'))->assertRedirect(route('billing'));
    });

    test('a guest stopped at a signed-in page is sent back to it', function () {
        get(route('billing'))->assertRedirect(route('welcome'));

        fakeSpotifyLogin(id: 'spotify-abc', email: 'new@example.com');

        get(route('spotify.process_login'))->assertRedirect(route('billing'));
    });

    test('an unknown destination falls back to the dashboard', function () {
        get(route('spotify.login', ['to' => 'https://evil.example.com']));

        fakeSpotifyLogin(id: 'spotify-abc', email: 'new@example.com');

        get(route('spotify.process_login'))->assertRedirect(route('dashboard'));
    });
});

describe('failed callbacks', function () {
    test('an invalid oauth state sends the user back to the welcome page', function () {
        fakeSpotifyCallbackFailure(new InvalidStateException);

        get(route('spotify.process_login'))
            ->assertRedirect(route('welcome'))
            ->assertSessionHasErrors('error');

        assertGuest();
    });

    test('a rejected token request sends the user back to the welcome page', function () {
        fakeSpotifyCallbackFailure(new ClientException('Bad request', new Request('POST', '/api/token'), new Response(400)));

        get(route('spotify.process_login'))
            ->assertRedirect(route('welcome'))
            ->assertSessionHasErrors('error');

        assertGuest();
    });

    test('no account is created when the callback fails', function () {
        fakeSpotifyCallbackFailure(new InvalidStateException);

        get(route('spotify.process_login'));

        expect(User::withTrashed()->count())->toBe(0);
    });
});

describe('first time users', function () {
    test('an account is created and logged in', function () {
        fakeSpotifyLogin(id: 'spotify-abc', email: 'new@example.com', name: 'Kyle');

        get(route('spotify.process_login'))->assertRedirect(route('dashboard'));

        $user = User::firstWhere('spotify_id', 'spotify-abc');

        expect($user)->not->toBeNull()
            ->and($user->name)->toBe('Kyle')
            ->and($user->email)->toBe('new@example.com');

        assertAuthenticatedAs($user);
    });

    test('preferences are created alongside the account', function () {
        fakeSpotifyLogin(id: 'spotify-abc', email: 'new@example.com');

        get(route('spotify.process_login'));

        expect(User::firstWhere('spotify_id', 'spotify-abc')->preferences)->not->toBeNull();
    });

    test('a spotify user without an avatar gets a generated one', function () {
        fakeSpotifyLogin(id: 'spotify-abc', email: 'new@example.com', name: 'Kyle', avatar: null);

        get(route('spotify.process_login'));

        expect(User::firstWhere('spotify_id', 'spotify-abc')->avatar)
            ->toBe('https://api.dicebear.com/7.x/initials/svg?seed=Kyle');
    });
});

describe('returning users', function () {
    test('spotify tokens are refreshed on the existing account', function () {
        $user = User::factory()->createOne([
            'spotify_id' => 'spotify-abc',
            'external_token' => 'stale-token',
            'external_refresh_token' => 'stale-refresh-token',
        ]);

        fakeSpotifyLogin(id: 'spotify-abc', email: $user->email);

        get(route('spotify.process_login'))->assertRedirect(route('dashboard'));

        expect($user->fresh()->external_token)->toBe('token')
            ->and($user->fresh()->external_refresh_token)->toBe('refresh-token');
    });

    test('a second login does not create a second account or a second preferences row', function () {
        fakeSpotifyLogin(id: 'spotify-abc', email: 'new@example.com');

        get(route('spotify.process_login'));
        get(route('spotify.process_login'));

        expect(User::withTrashed()->where('spotify_id', 'spotify-abc')->count())->toBe(1)
            ->and(User::firstWhere('spotify_id', 'spotify-abc')->preferences()->count())->toBe(1);
    });
});

describe('previously deleted users', function () {
    test('a soft deleted account is restored rather than duplicated', function () {
        $user = User::factory()->createOne(['spotify_id' => 'spotify-abc']);
        $user->delete();

        fakeSpotifyLogin(id: 'spotify-abc', email: $user->email);

        get(route('spotify.process_login'))->assertRedirect(route('dashboard'));

        expect($user->fresh()->deleted_at)->toBeNull()
            ->and(User::withTrashed()->where('spotify_id', 'spotify-abc')->count())->toBe(1);

        assertAuthenticatedAs($user);
    });

    test('a restored user is welcomed back', function () {
        $user = User::factory()->createOne(['spotify_id' => 'spotify-abc']);
        $user->delete();

        fakeSpotifyLogin(id: 'spotify-abc', email: $user->email);

        get(route('spotify.process_login'))->assertSessionHas('success');
    });

    test('an active user is not welcomed back', function () {
        $user = User::factory()->createOne(['spotify_id' => 'spotify-abc']);

        fakeSpotifyLogin(id: 'spotify-abc', email: $user->email);

        get(route('spotify.process_login'))->assertSessionMissing('success');
    });
});

describe('resolving emails', function () {
    test('the spotify email is used when there is one', function () {
        fakeSpotifyLogin(id: 'spotify-abc', email: 'real@example.com');

        get(route('spotify.process_login'));

        expect(User::firstWhere('spotify_id', 'spotify-abc')->email)->toBe('real@example.com');
    });

    test('a spotify user without an email falls back to a generated address', function () {
        fakeSpotifyLogin(id: 'spotify-abc', email: null);

        get(route('spotify.process_login'))->assertRedirect(route('dashboard'));

        expect(User::firstWhere('spotify_id', 'spotify-abc')->email)
            ->toBe('spotify-abc@songrank.dev');
    });

    test('an existing email is never overwritten', function () {
        $user = User::factory()->createOne([
            'spotify_id' => 'spotify-abc',
            'email' => 'real@example.com',
        ]);

        fakeSpotifyLogin(id: 'spotify-abc', email: 'changed@example.com');

        get(route('spotify.process_login'))->assertRedirect(route('dashboard'));

        expect($user->fresh()->email)->toBe('real@example.com');
    });

    test('login succeeds when a trashed account under another spotify id owns the address', function () {
        $trashed = User::factory()->createOne([
            'spotify_id' => 'spotify-old',
            'email' => 'shared@example.com',
        ]);

        $trashed->delete();

        fakeSpotifyLogin(id: 'spotify-new', email: 'shared@example.com');

        get(route('spotify.process_login'))->assertRedirect(route('dashboard'));

        expect(User::firstWhere('spotify_id', 'spotify-new')->email)->toBeNull()
            ->and($trashed->fresh()->trashed())->toBeTrue();
    });
});

describe('logging out', function () {
    test('a logged in user can log out', function () {
        actingAs(User::factory()->createOne());

        get(route('logout'))
            ->assertRedirect(route('welcome'))
            ->assertSessionHas('success');

        assertGuest();
    });
});

function fakeSpotifyLogin(string $id, ?string $email, string $name = 'Test User', ?string $avatar = 'https://example.com/avatar.png'): void
{
    $spotifyUser = new SpotifyUser;
    $spotifyUser->id = $id;
    $spotifyUser->name = $name;
    $spotifyUser->email = $email;
    $spotifyUser->avatar = $avatar;
    $spotifyUser->token = 'token';
    $spotifyUser->refreshToken = 'refresh-token';

    fakeSpotifyProvider(fn (MockInterface $provider) => $provider->shouldReceive('user')->andReturn($spotifyUser));
}

function fakeSpotifyCallbackFailure(Throwable $exception): void
{
    fakeSpotifyProvider(fn (MockInterface $provider) => $provider->shouldReceive('user')->andThrow($exception));
}

function fakeSpotifyProvider(Closure $expectation): void
{
    $provider = Mockery::mock(Provider::class);

    $expectation($provider);

    Socialite::shouldReceive('driver')->with('spotify')->andReturn($provider);
}
