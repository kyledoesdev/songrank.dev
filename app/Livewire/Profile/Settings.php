<?php

namespace App\Livewire\Profile;

use App\Jobs\DeleteUserJob;
use App\Livewire\Concerns\InteractsWithAlerts;
use App\Models\Ranking;
use App\Notifications\DownloadDataNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

class Settings extends Component
{
    use InteractsWithAlerts;

    public function render()
    {
        return view('livewire.profile.settings');
    }

    public function updateSetting(string $name, mixed $value)
    {
        Auth::user()->preferences()->update([
            $name => $value,
        ]);

        $this->flash('Settings Updated!', 'Your settings have been saved.');
    }

    public function destroy($userId)
    {
        // Security check
        abort_unless(Auth::check() && $userId == Auth::id(), 403);

        $user = Auth::user();

        // Log them out
        Auth::logout();

        /* kill session */
        Session::invalidate();
        Session::regenerateToken();

        // Queue deletion
        DeleteUserJob::dispatch($user);

        // Flash message and redirect
        session()->flash('success', "We're sorry to see you go {$user->name}. Be Well.");

        $this->redirect('/');
    }

    public function download()
    {
        $rankings = Ranking::query()
            ->where('user_id', Auth::id())
            ->with('songs', 'source')
            ->get();

        $user = Auth::user();

        dispatch(fn () => Notification::send($user, new DownloadDataNotification($rankings)));

        $this->flash('Download Started!', 'Your data download has started and will be emailed to you when it is complete.');
    }
}
