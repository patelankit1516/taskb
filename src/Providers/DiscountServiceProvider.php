<?php

namespace TaskB\UserDiscounts\Providers;

use Illuminate\Support\ServiceProvider;
use TaskB\UserDiscounts\Contracts\DiscountRepositoryInterface;
use TaskB\UserDiscounts\Contracts\DiscountStackingStrategyInterface;
use TaskB\UserDiscounts\Exceptions\DiscountException;
use TaskB\UserDiscounts\Repositories\DiscountRepository;
use TaskB\UserDiscounts\Services\DiscountService;
use TaskB\UserDiscounts\Strategies\AllDiscountsStrategy;
use TaskB\UserDiscounts\Strategies\BestDiscountStrategy;
use TaskB\UserDiscounts\Strategies\SequentialStackingStrategy;

/**
 * Discount Service Provider.
 * 
 * Registers the package services, bindings, and publishes configuration.
 */
class DiscountServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/discounts.php',
            'discounts'
        );

        // Bind repository interface to implementation
        $this->app->bind(
            DiscountRepositoryInterface::class,
            DiscountRepository::class
        );

        // Bind stacking strategy based on config
        $this->app->bind(DiscountStackingStrategyInterface::class, function ($app) {
            $strategy = config('discounts.stacking_strategy', 'sequential');
            $maxPercentageCap = config('discounts.max_percentage_cap', 100);
            $roundingMode = config('discounts.rounding_mode', 'half_up');
            $roundingPrecision = config('discounts.rounding_precision', 2);

            return match ($strategy) {
                'sequential' => new SequentialStackingStrategy(
                    $maxPercentageCap,
                    $roundingMode,
                    $roundingPrecision
                ),
                'best' => new BestDiscountStrategy(
                    $roundingMode,
                    $roundingPrecision
                ),
                'all' => new AllDiscountsStrategy(
                    $maxPercentageCap,
                    $roundingMode,
                    $roundingPrecision
                ),
                default => throw DiscountException::invalidStackingStrategy($strategy),
            };
        });

        // Bind the main service
        $this->app->singleton(DiscountService::class, function ($app) {
            return new DiscountService(
                $app->make(DiscountRepositoryInterface::class),
                $app->make(DiscountStackingStrategyInterface::class),
                config('discounts.enable_audit', true)
            );
        });

        // Alias for easier access
        $this->app->alias(DiscountService::class, 'discount.service');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Publish migrations
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../database/migrations' => database_path('migrations'),
            ], 'discounts-migrations');

            // Publish config
            $this->publishes([
                __DIR__ . '/../../config/discounts.php' => config_path('discounts.php'),
            ], 'discounts-config');
        }

        // Load migrations automatically in tests
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            DiscountRepositoryInterface::class,
            DiscountStackingStrategyInterface::class,
            DiscountService::class,
            'discount.service',
        ];
    }
}
