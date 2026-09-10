<?php

namespace App\Enums\Billing;

enum StripePaymentStatus: string
{
    case PAID = 'paid';
    case UNPAID = 'unpaid';
    case NO_PAYMENT_REQUIRED = 'no_payment_required';

    public function isSettled(): bool
    {
        return $this === self::PAID;
    }
}
