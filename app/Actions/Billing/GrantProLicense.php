<?php

namespace App\Actions\Billing;

use App\Enums\Billing\ProLicenseSource;
use App\Enums\Billing\ProLicenseStatus;
use App\Models\ProLicense;
use App\Models\User;

final class GrantProLicense
{
    public function handle(User $user, ProLicenseSource $source, array $attributes = []): ProLicense
    {
        return $user->proLicenses()->create([
            'status' => ProLicenseStatus::ACTIVE,
            'source' => $source,
            'purchased_at' => now(),
            ...$attributes,
        ]);
    }
}
