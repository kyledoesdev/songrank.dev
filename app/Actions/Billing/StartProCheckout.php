<?php

namespace App\Actions\Billing;

use App\Enums\Billing\ProLicenseSource;
use App\Enums\Billing\ProLicenseStatus;
use App\Models\ProLicense;
use App\Models\User;
use Laravel\Cashier\Checkout;
use Throwable;

final class StartProCheckout
{
    /**
     * The license row is written before the redirect so a webhook arriving
     * moments later has something to attach to. It stays pending until Stripe
     * confirms the payment.
     */
    public function handle(User $user): Checkout
    {
        $license = $user->proLicenses()->create([
            'status' => ProLicenseStatus::PENDING,
            'source' => ProLicenseSource::PURCHASE,
        ]);

        try {
            return $this->session($user, $license);
        } catch (Throwable $e) {
            /* Stripe issued no session, so nothing will ever attach to this row. */
            $license->update(['status' => ProLicenseStatus::ABANDONED]);

            throw $e;
        }
    }

    private function session(User $user, ProLicense $license): Checkout
    {
        $priceId = config('billing.pro.price_id');
        $quantity = 1;

        return $user->checkout([$priceId => $quantity], [
            'mode' => 'payment',
            'billing_address_collection' => 'required',
            'client_reference_id' => $license->uuid,
            'success_url' => route('billing.success').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('billing.cancel'),
            'metadata' => [
                'pro_license_uuid' => $license->uuid
            ],
            'invoice_creation' => [
                'enabled' => true
            ],
            'customer_update' => [
                'address' => 'auto',
                'name' => 'auto'
            ],
            ...$this->taxOptions(),
        ]);
    }

    private function taxOptions(): array
    {
        if (! config('billing.tax.automatic')) {
            return [];
        }

        return [
            'automatic_tax' => ['enabled' => true],
            'tax_id_collection' => ['enabled' => true],
        ];
    }
}
