<?php

namespace TaskB\UserDiscounts\Exceptions;

use Exception;

/**
 * Custom exception for discount-related errors.
 */
class DiscountException extends Exception
{
    /**
     * Create a new discount not found exception.
     */
    public static function notFound(int $discountId): self
    {
        return new self("Discount with ID {$discountId} not found.");
    }

    /**
     * Create a new inactive discount exception.
     */
    public static function inactive(int $discountId): self
    {
        return new self("Discount with ID {$discountId} is not active.");
    }

    /**
     * Create a new expired discount exception.
     */
    public static function expired(int $discountId): self
    {
        return new self("Discount with ID {$discountId} has expired.");
    }

    /**
     * Create a new usage limit exceeded exception.
     */
    public static function usageLimitExceeded(int $discountId): self
    {
        return new self("Usage limit exceeded for discount ID {$discountId}.");
    }

    /**
     * Create a new invalid stacking strategy exception.
     */
    public static function invalidStackingStrategy(string $strategy): self
    {
        return new self("Invalid stacking strategy: {$strategy}");
    }
}
