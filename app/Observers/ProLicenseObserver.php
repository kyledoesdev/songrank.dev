<?php

namespace App\Observers;

use App\Models\ProLicense;
use App\Models\User;

/**
 * Keeps `users.is_pro` in step with the licenses table.
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
