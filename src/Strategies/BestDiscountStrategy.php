<?php

namespace TaskB\UserDiscounts\Strategies;

use Illuminate\Support\Collection;
use TaskB\UserDiscounts\Contracts\DiscountStackingStrategyInterface;
use TaskB\UserDiscounts\DTOs\DiscountApplicationResult;
use TaskB\UserDiscounts\Models\Discount;

/**
 * Best Discount Stacking Strategy.
 * 
 * Applies only the best (highest value) discount.
 * Useful when you want to prevent stacking but give users the best deal.
 */
class BestDiscountStrategy implements DiscountStackingStrategyInterface
{
    public function __construct(
        private readonly string $roundingMode = 'half_up',
        private readonly int $roundingPrecision = 2
    ) {}

    /**
     * Apply only the best single discount.
     */
    public function apply(float $amount, Collection $discounts): DiscountApplicationResult
    {
        if ($amount <= 0 || $discounts->isEmpty()) {
            return new DiscountApplicationResult(
                originalAmount: $amount,
                discountAmount: 0,
                finalAmount: $amount,
                appliedDiscounts: [],
                metadata: ['strategy' => $this->getName()]
            );
        }

        $bestDiscount = null;
        $bestDiscountAmount = 0;

        foreach ($discounts as $discount) {
            $discountValue = $this->calculateDiscount($amount, $discount);
            
            if ($discountValue > $bestDiscountAmount) {
                $bestDiscountAmount = $discountValue;
                $bestDiscount = $discount;
            }
        }

        if ($bestDiscount === null) {
            return new DiscountApplicationResult(
                originalAmount: $amount,
                discountAmount: 0,
                finalAmount: $amount,
                appliedDiscounts: [],
                metadata: ['strategy' => $this->getName()]
            );
        }

        $discountAmount = $this->round($bestDiscountAmount);
        $finalAmount = $this->round($amount - $discountAmount);

        return new DiscountApplicationResult(
            originalAmount: $amount,
            discountAmount: $discountAmount,
            finalAmount: $finalAmount,
            appliedDiscounts: [$bestDiscount],
            metadata: ['strategy' => $this->getName()]
        );
    }

    /**
     * Calculate the discount for a single discount.
     */
    private function calculateDiscount(float $amount, Discount $discount): float
    {
        if ($discount->type === Discount::TYPE_PERCENTAGE) {
            return ($amount * $discount->value) / 100;
        }

        if ($discount->type === Discount::TYPE_FIXED) {
            return min($discount->value, $amount);
        }

        return 0;
    }

    /**
     * Round a value according to the configured rounding mode.
     */
    private function round(float $value): float
    {
        $mode = match($this->roundingMode) {
            'up' => PHP_ROUND_HALF_UP,
            'down' => PHP_ROUND_HALF_DOWN,
            'half_even' => PHP_ROUND_HALF_EVEN,
            default => PHP_ROUND_HALF_UP,
        };

        return round($value, $this->roundingPrecision, $mode);
    }

    public function getName(): string
    {
        return 'best';
    }
}
