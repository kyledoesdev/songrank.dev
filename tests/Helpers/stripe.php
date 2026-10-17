<?php

use Stripe\Exception\ApiConnectionException;
use Stripe\StripeClient;

/*
| Stripe stand-ins. Cashier resolves its client from the container, so a bound
| client reaches every call the app makes. Nothing in the suite talks to the
| real API: any service a test does not stand in for fails the way Stripe does
| when it cannot be reached.
*/

/**
 * Bind a Stripe client whose services are the given stand-ins.
 *
 * @param  array<string, object>  $services  keyed by service name: `checkout`, `customers`, `invoices`
 */
function fakeStripeServices(array $services = []): void
{
    $client = new class(['api_key' => 'sk_test_songrank'], $services) extends StripeClient
    {
        /**
         * @param  array<string, object>  $stubs
         */
        public function __construct(array $config, private array $stubs)
        {
            parent::__construct($config);
        }

        public function getService($name)
        {
            return $this->stubs[$name] ?? unreachableStripeService();
        }
    };

    /*
     * bind(), not instance(): Cashier::stripe() resolves the client with build
     * parameters, and the container skips shared instances when any are given.
     */
    app()->bind(StripeClient::class, fn () => $client);
}

/**
 * A service whose every call fails as if Stripe could not be reached.
 */
function unreachableStripeService(): object
{
    return new class
    {
        public function __get(string $name): static
        {
            return $this;
        }

        public function __call(string $method, array $arguments): never
        {
            throw ApiConnectionException::factory('Stripe cannot be reached from the test suite.');
        }
    };
}
