<?php

namespace App\Models;

use App\Enums\Billing\ProLicenseSource;
use App\Enums\Billing\ProLicenseStatus;
use App\Observers\ProLicenseObserver;
use App\QueryBuilders\ProLicenseQueryBuilder;
use Database\Factories\ProLicenseFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Laravel\Cashier\Cashier;

#[ObservedBy(ProLicenseObserver::class)]
#[UseEloquentBuilder(ProLicenseQueryBuilder::class)]
#[UseFactory(ProLicenseFactory::class)]
class ProLicense extends Model
{
    protected $fillable = [
        'uuid',
        'user_id',
        'status',
        'source',
        'stripe_checkout_session_id',
        'stripe_payment_intent_id',
        'stripe_invoice_id',
        'stripe_customer_id',
        'amount_subtotal',
        'amount_tax',
        'amount_total',
        'amount_refunded',
        'currency',
        'billing_country',
        'hosted_invoice_url',
        'invoice_pdf_url',
        'purchased_at',
        'refunded_at',
        'revoked_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProLicenseStatus::class,
            'source' => ProLicenseSource::class,
            'amount_subtotal' => 'integer',
            'amount_tax' => 'integer',
            'amount_total' => 'integer',
            'amount_refunded' => 'integer',
            'purchased_at' => 'datetime',
            'refunded_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        static::creating(function (ProLicense $license) {
            $license->uuid ??= Str::uuid()->toString();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /* Relationships */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /* Attributes */

    public function isActive(): bool
    {
        return $this->status->grantsAccess();
    }

    public function isPaid(): bool
    {
        return $this->source->isPaid();
    }

    public function formattedTotal(): string
    {
        return $this->formatAmount($this->amount_total);
    }

    public function formattedTax(): string
    {
        return $this->formatAmount($this->amount_tax);
    }

    public function formattedSubtotal(): string
    {
        return $this->formatAmount($this->amount_subtotal);
    }

    public function formattedRefunded(): string
    {
        return $this->formatAmount($this->amount_refunded);
    }

    /**
     * Whether Stripe holds a document the customer can be sent to.
     */
    public function hasReceipt(): bool
    {
        return filled($this->hosted_invoice_url) || filled($this->invoice_pdf_url);
    }

    public function stripeDashboardUrl(): ?string
    {
        if (blank($this->stripe_payment_intent_id)) {
            return null;
        }

        return "https://dashboard.stripe.com/payments/{$this->stripe_payment_intent_id}";
    }

    private function formatAmount(?int $amount): string
    {
        return Cashier::formatAmount(
            $amount ?? 0,
            $this->currency ?? config('billing.pro.currency'),
        );
    }
}
