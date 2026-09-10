<?php

namespace App\Actions\Billing;

use App\Enums\Billing\ProLicenseStatus;
use App\Models\ProLicense;

final class RevokeProLicense
{
    public function handle(ProLicense $license, ProLicenseStatus $status, array $attributes = []): ProLicense
    {
        if ($license->status === $status) {
            return $license;
        }

        $license->update([
            'status' => $status,
            'refunded_at' => $status === ProLicenseStatus::REFUNDED ? now() : $license->refunded_at,
            'revoked_at' => $status === ProLicenseStatus::REVOKED ? now() : $license->revoked_at,
            ...$attributes,
        ]);

        return $license->fresh();
    }
}
