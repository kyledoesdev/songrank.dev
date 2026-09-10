<?php

namespace App\Checks;

use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;

class StripeWebhookSecretCheck extends Check
{
    public function run(): Result
    {
        $result = Result::make()->shortSummary('Configured');

        if (blank(config('cashier.webhook.secret'))) {
            return $result
                ->shortSummary('Missing')
                ->failed('STRIPE_WEBHOOK_SECRET is not set: the Stripe webhook accepts unsigned requests.');
        }

        return $result->ok();
    }
}
