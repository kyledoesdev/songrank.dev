<?php

namespace App\Observers;

use App\Models\ProLicense;
use App\Models\User;

/**
 * Keeps `users.is_pro` in step with the licenses table.
 *
 * The licenses are the source of truth; the flag on the user is a projection
 * of them, so that `$user->is_pro` — called from the nav, the search guard,
 * the middleware and the billing page on a single page load — is a column
 * read rather than a query. Syncing here rather than in each action means it
 * cannot drift, whichever code path changed the license.
 *
 * The one gap is mass assignment: `ProLicense::query()->update(...)` fires no
 * model events, so anything doing that must call `User::syncProStatus()`
 * itself. DeleteUserJob is the only place that currently does.
 */
class ProLicenseObserver
{
    public function saved(ProLicense $license): void
    {
        $this->sync($license);
    }

    public function deleted(ProLicense $license): void
    {
        $this->sync($license);
    }

    public function restored(ProLicense $license): void
    {
        $this->sync($license);
    }

    private function sync(ProLicense $license): void
    {
        /* The original owner matters too when a license is detached or reassigned. */
        collect([$license->getOriginal('user_id'), $license->user_id])
            ->filter()
            ->unique()
            ->each(fn (int $userId) => User::find($userId)?->syncProStatus());
    }
}
