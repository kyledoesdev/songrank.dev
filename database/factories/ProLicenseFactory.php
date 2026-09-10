<?php

namespace Database\Factories;

use App\Enums\Billing\ProLicenseSource;
use App\Enums\Billing\ProLicenseStatus;
use App\Models\ProLicense;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProLicense>
 */
class ProLicenseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => Str::uuid()->toString(),
            'user_id' => User::factory(),
            'status' => ProLicenseStatus::PENDING,
            'source' => ProLicenseSource::PURCHASE,
            'amount_tax' => 0,
            'amount_refunded' => 0,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => ProLicenseStatus::PENDING,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'status' => ProLicenseStatus::ACTIVE,
        ])->paidWithStripe();
    }

    public function abandoned(): static
    {
        return $this->state(fn () => [
            'status' => ProLicenseStatus::ABANDONED,
        ]);
    }

    public function refunded(): static
    {
        return $this->active()->state(fn (array $attributes) => [
            'status' => ProLicenseStatus::REFUNDED,
            'amount_refunded' => $attributes['amount_total'] ?? 1000,
            'refunded_at' => now(),
        ]);
    }

    public function revoked(): static
    {
        return $this->active()->state(fn () => [
            'status' => ProLicenseStatus::REVOKED,
            'revoked_at' => now(),
        ]);
    }

    /**
     * A complimentary licence: active, but with no payment behind it.
     */
    public function comp(): static
    {
        return $this->state(fn () => [
            'status' => ProLicenseStatus::ACTIVE,
            'source' => ProLicenseSource::COMP,
            'purchased_at' => now(),
            'notes' => 'Beta tester.',
        ]);
    }

    /**
     * Fill in the Stripe trail a real purchase would have left behind.
     */
    public function paidWithStripe(): static
    {
        return $this->state(function () {
            $suffix = Str::lower(Str::random(16));

            return [
                'stripe_checkout_session_id' => "cs_test_{$suffix}",
                'stripe_payment_intent_id' => "pi_test_{$suffix}",
                'stripe_invoice_id' => "in_test_{$suffix}",
                'stripe_customer_id' => "cus_test_{$suffix}",
                'amount_subtotal' => 1000,
                'amount_tax' => 0,
                'amount_total' => 1000,
                'currency' => 'usd',
                'billing_country' => 'US',
                'hosted_invoice_url' => "https://invoice.stripe.com/i/{$suffix}",
                'invoice_pdf_url' => "https://pay.stripe.com/invoice/{$suffix}/pdf",
                'purchased_at' => now(),
            ];
        });
    }

    /**
     * A purchase that attracted VAT, for tax reporting assertions.
     */
    public function withTax(int $tax = 210, string $country = 'DE'): static
    {
        return $this->state(fn (array $attributes) => [
            'amount_subtotal' => ($attributes['amount_total'] ?? 1000) - $tax,
            'amount_tax' => $tax,
            'billing_country' => $country,
        ]);
    }
}
