<?php

namespace App\Http\Controllers;

use App\Services\SpotifyAuthenticationService;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Kyledoesdev\Essentials\Stats\LoginStat;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class SpotifyAuthController extends Controller
{
    public function login(Request $request)
    {
        if ($request->query('to') === 'billing') {
            redirect()->setIntendedUrl(route('billing'));
        }

        return Socialite::driver('spotify')
            ->scopes(['user-read-email'])
            ->redirect();
    }

    public function processLogin()
    {
        try {
            $spotifyUser = Socialite::driver('spotify')->user();
        } catch (InvalidStateException|ClientException) {
            return redirect(route('welcome'))->withErrors(['error' => 'There was an issue with your spotify authorization token. Please try logging in again.']);
        }

        $authService = new SpotifyAuthenticationService($spotifyUser);

        if ($authService->restoreUserIfAccountWasPreviouslyDeleted()) {
            session()->flash('success', "Welcome back {$spotifyUser->name}.. we've been expecting you.. To revive your rankings - reach out via the support bubble in the bottom right.");
        }

        Session::regenerate();

        Auth::login($authService->getSongRankUser());

        LoginStat::increase();

        return redirect()->intended(route('dashboard'));
    }

    public function logout()
    {
        Auth::logout();

        Session::invalidate();
        Session::regenerateToken();

        return redirect(route('welcome'))->with('success', "You've logged out. See ya next time!");
    }
}
