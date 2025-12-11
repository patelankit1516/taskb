<?php

namespace TaskB\UserDiscounts\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use TaskB\UserDiscounts\Providers\DiscountServiceProvider;

/**
 * Base test case for the package tests.
 */
abstract class TestCase extends Orchestra
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Run migrations
        $this->artisan('migrate')->run();
    }

    /**
     * Get package providers.
     */
    protected function getPackageProviders($app): array
    {
        return [
            DiscountServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     */
    protected function getEnvironmentSetUp($app): void
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Set default discount configuration
        $app['config']->set('discounts.stacking_strategy', 'sequential');
        $app['config']->set('discounts.max_percentage_cap', 100);
        $app['config']->set('discounts.rounding_mode', 'half_up');
        $app['config']->set('discounts.rounding_precision', 2);
        $app['config']->set('discounts.enable_audit', true);
    }
}
