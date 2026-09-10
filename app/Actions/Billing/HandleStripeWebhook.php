<?php

namespace App\Actions\Billing;

use App\Enums\Billing\ProLicenseStatus;
use App\Enums\Billing\StripeWebhookEvent;
use App\Models\ProLicense;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Events\WebhookReceived;

/**
 * Routes the Stripe events Cashier does not handle itself.
 *
 * Cashier's own controller covers eight subscription- and customer-shaped
 * events; none of the one-off purchase lifecycle is among them. It does,
 * however, dispatch WebhookReceived for every payload it accepts, which is
 * where this hooks in.
 */
final class HandleStripeWebhook
{
    public function __invoke(WebhookReceived $event): void
    {
        $type = StripeWebhookEvent::tryFrom(data_get($event->payload, 'type', ''));
        $object = data_get($event->payload, 'data.object', []);

        /* Anything unrecognised dies. Throwing here would have Stripe throw us into a retry loop */
        match ($type) {
            StripeWebhookEvent::CHECKOUT_COMPLETED,
            StripeWebhookEvent::CHECKOUT_ASYNC_SUCCEEDED => (new FulfillProPurchase)->handle($object),

            StripeWebhookEvent::CHECKOUT_EXPIRED,
            StripeWebhookEvent::CHECKOUT_ASYNC_FAILED => $this->abandon($object),

            StripeWebhookEvent::CHARGE_REFUNDED => $this->refund($object),
            StripeWebhookEvent::DISPUTE_CREATED => $this->dispute($object),

            default => null,
        };
    }

    /**
     * A checkout that can no longer complete.
     *     *
     * @param  array<string, mixed>  $session
     */
    private function abandon(array $session): void
    {
        ProLicense::query()
            ->whereUuid(data_get($session, 'metadata.pro_license_uuid'))
            ->pending()
            ->update(['status' => ProLicenseStatus::ABANDONED]);
    }

    /**
     * Handles refunding the buyer some $charge
     *     *
     * @param  array<string, mixed>  $charge
     */
    private function refund(array $charge): void
    {
        $license = $this->licenseForPaymentIntent(data_get($charge, 'payment_intent'));

        if (is_null($license)) {
            $this->reportOrphanedMoney('refund', $charge);

            return;
        }

        $refunded = (int) data_get($charge, 'amount_refunded', 0);
        $charged = (int) data_get($charge, 'amount', 0);

        if ($charged > 0 && $refunded < $charged) {
            $license->update(['amount_refunded' => $refunded]);

            return;
        }

        (new RevokeProLicense)->handle(
            license: $license,
            status: ProLicenseStatus::REFUNDED,
            attributes: ['amount_refunded' => $refunded],
        );
    }

    /**
     * @param  array<string, mixed>  $dispute
     */
    private function dispute(array $dispute): void
    {
        $license = $this->licenseForPaymentIntent(data_get($dispute, 'payment_intent'));

        if (is_null($license)) {
            $this->reportOrphanedMoney('dispute', $dispute);

            return;
        }

        (new RevokeProLicense)->handle(
            license: $license,
            status: ProLicenseStatus::REVOKED,
            attributes: ['notes' => 'Revoked automatically: chargeback opened on '.now()->toDateString().'.'],
        );

        Log::warning('Chargeback opened on a Pro license.', [
            'pro_license_uuid' => $license->uuid,
            'dispute_id' => data_get($dispute, 'id'),
        ]);
    }

    private function licenseForPaymentIntent(?string $paymentIntentId): ?ProLicense
    {
        if (blank($paymentIntentId)) {
            return null;
        }

        return ProLicense::query()
            ->wherePaymentIntent($paymentIntentId)
            ->first();
    }

    /**
     * Money moved for a payment we cannot tie to a license.
     *
     * @param  array<string, mixed>  $object
     */
    private function reportOrphanedMoney(string $kind, array $object): void
    {
        Log::warning("Stripe reported a {$kind} for a payment no Pro license claims.", [
            'stripe_payment_intent_id' => data_get($object, 'payment_intent'),
            'stripe_object_id' => data_get($object, 'id'),
            'amount_refunded' => data_get($object, 'amount_refunded'),
        ]);
    }
}
