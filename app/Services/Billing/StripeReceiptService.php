<?php

namespace App\Services\Billing;

use App\Models\ProLicense;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Cashier;
use Stripe\Exception\ApiErrorException;

/**
 * Resolves the customer-facing documents Stripe holds for a purchase.
 *
 * Stripe generates the invoice and its PDF itself when `invoice_creation` is
 * enabled on the checkout session, so the application never renders one. Both
 * URLs come off a single invoice, and are stored on the license so the billing
 * page renders without calling Stripe at all.
 */
class StripeReceiptService
{
    /**
     * The hosted invoice page and PDF for a Stripe invoice.
     *
     * A lookup failure must never cost the customer their license, so a Stripe
     * error is logged and yields no URLs rather than bubbling out of
     * fulfillment. The billing page offers a refetch for exactly this case.
     *
     * @return array<string, string|null>
     */
    public function forInvoice(?string $invoiceId): array
    {
        if (blank($invoiceId)) {
            return [];
        }

        try {
            $invoice = Cashier::stripe()->invoices->retrieve($invoiceId);
        } catch (ApiErrorException $e) {
            Log::warning('Could not read a Stripe invoice for a Pro license.', [
                'stripe_invoice_id' => $invoiceId,
                'stripe_error' => $e->getMessage(),
            ]);

            return [];
        }

        return [
            'hosted_invoice_url' => $invoice->hosted_invoice_url,
            'invoice_pdf_url' => $invoice->invoice_pdf,
        ];
    }

    /**
     * Re-read a license's documents from Stripe.
     *
     * Used when a stored URL is missing — a lookup that failed at fulfillment,
     * or a purchase made before invoice creation was switched on.
     *
     * @return array<string, string|null>
     */
    public function refresh(ProLicense $license): array
    {
        if (! $license->isPaid()) {
            return [];
        }

        return $this->forInvoice($license->stripe_invoice_id);
    }
}
