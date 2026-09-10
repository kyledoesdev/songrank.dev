<?php

namespace App\Actions\Billing;

use App\Enums\Billing\ProLicenseStatus;
use App\Enums\Billing\StripePaymentStatus;
use App\Models\ProLicense;
use App\Notifications\ProPurchaseReceipt;
use App\Services\Billing\StripeReceiptService;
use Illuminate\Support\Facades\Log;

final class FulfillProPurchase
{
    /**
     * Turn a paid checkout session into an active license.
     *     *
     * @param  array<string, mixed>  $session  A Stripe Checkout Session
     */
    public function handle(array $session): ?ProLicense
    {
        $paymentStatus = StripePaymentStatus::tryFrom(data_get($session, 'payment_status', ''));

        if (! $paymentStatus?->isSettled()) {
            return null;
        }

        $license = ProLicense::query()
            ->whereUuid(data_get($session, 'metadata.pro_license_uuid'))
            ->first();

        if (is_null($license)) {
            Log::warning('A paid Stripe session referenced no known Pro license.', [
                'stripe_checkout_session_id' => data_get($session, 'id'),
            ]);

            return null;
        }

        /* Read the invoice before opening a write: no HTTP inside a database call. */
        $documents = (new StripeReceiptService)->forInvoice(data_get($session, 'invoice'));

        if (! $this->claim($license, $session, $documents)) {
            return $this->backfill($license->fresh(), $session, $documents);
        }

        $license = $license->fresh();

        $this->announce($license);

        return $license;
    }

    /**
     * Take the license from pending to active
     *
     * @param  array<string, mixed>  $session
     * @param  array<string, string|null>  $documents
     */
    private function claim(ProLicense $license, array $session, array $documents): bool
    {
        $claimed = ProLicense::query()
            ->whereKey($license->getKey())
            ->where('status', ProLicenseStatus::PENDING)
            ->update([
                'status' => ProLicenseStatus::ACTIVE,
                'stripe_checkout_session_id' => data_get($session, 'id'),
                'stripe_payment_intent_id' => data_get($session, 'payment_intent'),
                'stripe_invoice_id' => data_get($session, 'invoice'),
                'stripe_customer_id' => data_get($session, 'customer'),
                'amount_subtotal' => data_get($session, 'amount_subtotal'),
                'amount_tax' => data_get($session, 'total_details.amount_tax') ?? 0,
                'amount_total' => data_get($session, 'amount_total'),
                'currency' => data_get($session, 'currency'),
                'billing_country' => data_get($session, 'customer_details.address.country'),
                'purchased_at' => now(),
                'updated_at' => now(),
                ...$documents,
            ]);

        if ($claimed === 0) {
            return false;
        }

        /* A mass update fires no model events, so the observer never runs. */
        $license->user?->syncProStatus();

        return true;
    }

    /**
     * Fill in what the winner of the claim could not.
     *
     * @param  array<string, mixed>  $session
     * @param  array<string, string|null>  $documents
     */
    private function backfill(ProLicense $license, array $session, array $documents): ProLicense
    {
        $missing = array_filter([
            'stripe_invoice_id' => $license->stripe_invoice_id ?? data_get($session, 'invoice'),
            'hosted_invoice_url' => $license->hosted_invoice_url ?? ($documents['hosted_invoice_url'] ?? null),
            'invoice_pdf_url' => $license->invoice_pdf_url ?? ($documents['invoice_pdf_url'] ?? null),
        ]);

        if ($missing !== []) {
            $license->fill($missing)->save();
        }

        return $license->fresh();
    }

    private function announce(ProLicense $license): void
    {
        $buyer = $license->user;

        if (is_null($buyer)) {
            Log::warning("Fulfilled a Pro license to $buyer who deleted themself during the checkout process. This payment likely needs refunding.", [
                'pro_license_uuid' => $license->uuid,
                'stripe_payment_intent_id' => $license->stripe_payment_intent_id,
            ]);

            return;
        }

        $buyer->notify(new ProPurchaseReceipt($license));

        Log::channel('discord_user_updates')->info("$buyer has gone pro!");
    }
}
