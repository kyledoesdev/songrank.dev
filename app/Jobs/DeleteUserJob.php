<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\DownloadDataNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class DeleteUserJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private User $user) {}

    public function handle(): void
    {
        /* Get the user's rankings. */
        $rankings = $this->user
            ->rankings()
            ->with('songs', 'source')
            ->get();

        /* Send the user their data. */
        if (count($rankings)) {
            Notification::send($this->user, new DownloadDataNotification($rankings));

            /* Delete the user's rankings & their songs in 1 transaction to be safe */
            DB::transaction(function () use ($rankings) {
                $rankings->each(function ($ranking) {
                    $ranking->songs()->delete();
                    $ranking->delete();
                });
            });
        }

        $this->detachProLicenses();

        $this->user->update([
            'external_token' => null,
            'external_refresh_token' => null,
            'ip_address' => null,
            'user_agent' => null,
            'user_platform' => null,
            'user_packet' => null,
        ]);

        $this->user->delete();
    }

    private function detachProLicenses(): void
    {
        $this->user->proLicenses()->update(['user_id' => null]);

        /* A mass update fires no model events, so ProLicenseObserver never runs. */
        $this->user->syncProStatus();
    }
}
