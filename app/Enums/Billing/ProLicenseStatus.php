<?php

namespace App\Enums\Billing;

enum ProLicenseStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case ABANDONED = 'abandoned';
    case REFUNDED = 'refunded';
    case REVOKED = 'revoked';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Awaiting Payment',
            self::ACTIVE => 'Active',
            self::ABANDONED => 'Abandoned',
            self::REFUNDED => 'Refunded',
            self::REVOKED => 'Revoked',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PENDING => 'fa-hourglass-half',
            self::ACTIVE => 'fa-circle-check',
            self::ABANDONED => 'fa-ghost',
            self::REFUNDED => 'fa-rotate-left',
            self::REVOKED => 'fa-ban',
        };
    }

    public function filamentColor(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::ACTIVE => 'success',
            self::ABANDONED => 'gray',
            self::REFUNDED => 'info',
            self::REVOKED => 'danger',
        };
    }

    public function grantsAccess(): bool
    {
        return $this === self::ACTIVE;
    }
}
