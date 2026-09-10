<?php

namespace App\QueryBuilders;

use App\Enums\Billing\ProLicenseStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ProLicenseQueryBuilder extends Builder
{
    public function active(): static
    {
        return $this->where('status', ProLicenseStatus::ACTIVE);
    }

    public function pending(): static
    {
        return $this->where('status', ProLicenseStatus::PENDING);
    }

    public function forUser(User $user): static
    {
        return $this->where('user_id', $user->getKey());
    }

    public function wherePaymentIntent(string $paymentIntentId): static
    {
        return $this->where('stripe_payment_intent_id', $paymentIntentId);
    }

    public function whereUuid(?string $uuid): static
    {
        return $this->where('uuid', $uuid);
    }

    /**
     * The license the billing page reports on.
     *
     * Deliberately not newQuery(): this is chained off `$user->proLicenses()`,
     * and resetting the builder would drop the ownership constraint and hand
     * back somebody else's license.
     */
    public function forBillingPage(): static
    {
        return $this
            ->whereIn('status', [ProLicenseStatus::ACTIVE, ProLicenseStatus::REFUNDED])
            ->orderByRaw('status = ? DESC', [ProLicenseStatus::ACTIVE->value])
            ->orderByDesc('purchased_at');
    }
}
