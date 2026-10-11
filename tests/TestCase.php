<?php

namespace Tests;

use App\Services\Audit\HostResolver;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /**
     * Replaces DNS lookups with a fixed host-to-IP map so audits never touch the network.
     *
     * @param  array<string, list<string>>  $hosts
     */
    protected function fakeDns(array $hosts): void
    {
        $this->app->instance(HostResolver::class, new class($hosts) extends HostResolver
        {
            /**
             * @param  array<string, list<string>>  $hosts
             */
            public function __construct(private array $hosts) {}

            public function resolve(string $host): array
            {
                return filter_var($host, FILTER_VALIDATE_IP) ? [$host] : ($this->hosts[$host] ?? []);
            }
        });
    }
}
