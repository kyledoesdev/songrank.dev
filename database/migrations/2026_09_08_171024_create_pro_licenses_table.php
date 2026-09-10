<?php

use App\Enums\Billing\ProLicenseSource;
use App\Enums\Billing\ProLicenseStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pro_licenses', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('user_id')->nullable()->index()->constrained()->nullOnDelete();
            $table->string('status')->default(ProLicenseStatus::PENDING->value)->index();
            $table->string('source')->default(ProLicenseSource::PURCHASE->value)->index();
            $table->string('stripe_checkout_session_id')->nullable()->unique();
            $table->string('stripe_payment_intent_id')->nullable()->index();
            $table->string('stripe_invoice_id')->nullable();
            $table->string('stripe_customer_id')->nullable()->index();
            $table->unsignedInteger('amount_subtotal')->nullable();
            $table->unsignedInteger('amount_tax')->default(0);
            $table->unsignedInteger('amount_total')->nullable();
            $table->unsignedInteger('amount_refunded')->default(0);
            $table->char('currency', 3)->nullable();
            $table->char('billing_country', 2)->nullable()->index();
            $table->text('hosted_invoice_url')->nullable();
            $table->text('invoice_pdf_url')->nullable();
            $table->timestamp('purchased_at')->nullable()->index();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pro_licenses');
    }
};
