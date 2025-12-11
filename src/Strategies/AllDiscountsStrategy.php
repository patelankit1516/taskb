<?php

namespace TaskB\UserDiscounts\Strategies;

use Illuminate\Support\Collection;
use TaskB\UserDiscounts\Contracts\DiscountStackingStrategyInterface;
use TaskB\UserDiscounts\DTOs\DiscountApplicationResult;
use TaskB\UserDiscounts\Models\Discount;

/**
 * All Discounts Strategy.
 * 
 * Sums up all discount values and applies them to the original amount.
 * Note: Percentage discounts are calculated on the original amount, not compound.
 */
class AllDiscountsStrategy implements DiscountStackingStrategyInterface
{
    public function __construct(
        private readonly int $maxPercentageCap = 100,
        private readonly string $roundingMode = 'half_up',
        private readonly int $roundingPrecision = 2
    ) {}

    /**
     * Apply all discounts by summing their values.
     */
    public function apply(float $amount, Collection $discounts): DiscountApplicationResult
    {
        if ($amount <= 0) {
            return new DiscountApplicationResult(
                originalAmount: $amount,
                discountAmount: 0,
                finalAmount: $amount,
                appliedDiscounts: [],
                metadata: ['strategy' => $this->getName()]
            );
        }

        $totalDiscountAmount = 0;
        $appliedDiscounts = [];

        foreach ($discounts as $discount) {
            $discountValue = $this->calculateDiscount($amount, $discount);
            
            if ($discountValue > 0) {
                $totalDiscountAmount += $discountValue;
                $appliedDiscounts[] = $discount;
            }
        }

        // Apply max percentage cap
        $maxDiscountAmount = ($amount * $this->maxPercentageCap) / 100;
        $totalDiscountAmount = min($totalDiscountAmount, $maxDiscountAmount);
        
        // Ensure discount doesn't exceed the original amount
        $totalDiscountAmount = min($totalDiscountAmount, $amount);

        $discountAmount = $this->round($totalDiscountAmount);
        $finalAmount = $this->round($amount - $discountAmount);

        return new DiscountApplicationResult(
            originalAmount: $amount,
            discountAmount: $discountAmount,
            finalAmount: $finalAmount,
            appliedDiscounts: $appliedDiscounts,
            metadata: [
                'strategy' => $this->getName(),
                'max_percentage_cap' => $this->maxPercentageCap
            ]
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
            return $discount->value;
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
        return 'all';
    }
}
