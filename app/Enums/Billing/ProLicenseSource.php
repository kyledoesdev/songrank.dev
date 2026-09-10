<?php

namespace App\Enums\Billing;

enum ProLicenseSource: string
{
    case PURCHASE = 'purchase';
    case COMP = 'comp';
    case GIFT = 'gift';

    public function label(): string
    {
        return match ($this) {
            self::PURCHASE => 'Purchase',
            self::COMP => 'Complimentary',
            self::GIFT => 'Gift',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PURCHASE => 'fa-credit-card',
            self::COMP => 'fa-hand-holding-heart',
            self::GIFT => 'fa-gift',
        };
    }

    public function filamentColor(): string
    {
        return match ($this) {
            self::PURCHASE => 'success',
            self::COMP => 'warning',
            self::GIFT => 'info',
        };
    }

    /**
     * Whether a license from this source is backed by a Stripe payment.
     */
    public function isPaid(): bool
    {
        return $this === self::PURCHASE;
    }
}
