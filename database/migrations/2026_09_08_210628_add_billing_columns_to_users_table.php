<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Replaces Cashier's published customer-columns migration.
 *
 * Cashier ships `pm_type`, `pm_last_four` and `trial_ends_at` alongside
 * `stripe_id`. Song Rank Pro never stores a payment method (Stripe Checkout
 * holds the card, and the invoice covers what the customer needs to see) and
 * has no trial, so only `stripe_id` is kept.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('stripe_id')->nullable()->index()->after('is_dev');

            /* Projection of the licences table, kept in sync by ProLicenseObserver. */
            $table->boolean('is_pro')->default(false)->index()->after('is_dev');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['stripe_id']);
            $table->dropIndex(['is_pro']);

            $table->dropColumn(['stripe_id', 'is_pro']);
        });
    }
};
