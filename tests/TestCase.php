<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;
use Monolog\Handler\NullHandler;

abstract class TestCase extends BaseTestCase
{
    /**
     * Nothing in the suite reaches the outside world: an unfaked HTTP request
     * throws, Stripe answers as if it cannot be reached, and the Discord log
     * channels write nowhere. A test fakes whatever it needs on top.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        fakeStripeServices();

        config([
            'logging.channels.discord_user_updates' => ['driver' => 'monolog', 'handler' => NullHandler::class],
            'logging.channels.discord_other_updates' => ['driver' => 'monolog', 'handler' => NullHandler::class],
        ]);
    }
}
