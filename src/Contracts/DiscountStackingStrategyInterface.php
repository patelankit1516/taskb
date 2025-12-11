<?php

namespace TaskB\UserDiscounts\Contracts;

use Illuminate\Support\Collection;
use TaskB\UserDiscounts\DTOs\DiscountApplicationResult;

/**
 * Interface for discount stacking strategies.
 * 
 * Defines how multiple discounts should be combined
 * when applied to an amount following the Strategy Pattern.
 */
interface DiscountStackingStrategyInterface
{
    /**
     * Apply the discounts to the given amount using this strategy.
     *
     * @param float $amount The original amount before discounts
     * @param Collection $discounts Collection of Discount models
     * @return DiscountApplicationResult The result of applying discounts
     */
    public function apply(float $amount, Collection $discounts): DiscountApplicationResult;

    /**
     * Get the name of this stacking strategy.
     *
     * @return string
     */
    public function getName(): string;
}
