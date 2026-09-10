<?php

namespace App\Livewire\Billing;

use App\Models\ProLicense;
use App\Services\Billing\StripeReceiptService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Billing extends Component
{
    public function render()
    {
        return view('livewire.billing.billing', [
            'license' => $this->license(),
        ]);
    }

    public function license(): ?ProLicense
    {
        return Auth::user()
            ->proLicenses()
            ->forBillingPage()
            ->first();
    }

    /**
     * Re-read the receipt links from Stripe.
     *
     * Only reachable when a paid license is missing them — a lookup that
     * failed at fulfillment, or a purchase made before invoice creation was
     * switched on.
     */
    public function refreshReceipt(StripeReceiptService $receipts): void
    {
        $license = $this->license();

        if (is_null($license) || ! $license->isPaid()) {
            return;
        }

        $documents = array_filter($receipts->refresh($license));

        /*
         * Nothing to fetch means Stripe never raised an invoice for this
         * purchase, and retrying will not change that. Saying "refreshed"
         * would be a lie the customer cannot act on.
         */
        if ($documents === []) {
            $this->js("
                window.flash({
                    title: 'No receipt available yet.',
                    message: 'Stripe has not issued an invoice for this purchase. Get in touch and we will sort it out.',
                    icon: 'error',
                });
            ");

            return;
        }

        $license->update($documents);

        $this->js("
            window.flash({
                title: 'Receipt refreshed.',
                icon: 'success',
            });
        ");
    }
}
