<?php

namespace TaskB\UserDiscounts\Strategies;

use Illuminate\Support\Collection;
use TaskB\UserDiscounts\Contracts\DiscountStackingStrategyInterface;
use TaskB\UserDiscounts\DTOs\DiscountApplicationResult;
use TaskB\UserDiscounts\Models\Discount;

/**
 * Sequential Discount Stacking Strategy.
 * 
 * Applies discounts one after another, each on the reduced amount.
 * This is the most common strategy where discounts compound.
 * Example: $100 with 10% then 20% = $100 * 0.9 * 0.8 = $72
 */
class SequentialStackingStrategy implements DiscountStackingStrategyInterface
{
    public function __construct(
        private readonly int $maxPercentageCap = 100,
        private readonly string $roundingMode = 'half_up',
        private readonly int $roundingPrecision = 2
    ) {}

    /**
     * Apply discounts sequentially based on priority.
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

        // Sort by priority (higher priority first)
        $sortedDiscounts = $discounts->sortByDesc('priority');
        
        $currentAmount = $amount;
        $appliedDiscounts = [];
        $totalDiscountAmount = 0;

        foreach ($sortedDiscounts as $discount) {
            $discountValue = $this->calculateDiscount($currentAmount, $discount);
            
            if ($discountValue > 0) {
                $currentAmount -= $discountValue;
                $totalDiscountAmount += $discountValue;
                $appliedDiscounts[] = $discount;
                
                // Check if we've hit the max percentage cap
                $currentDiscountPercentage = (($amount - $currentAmount) / $amount) * 100;
                if ($currentDiscountPercentage >= $this->maxPercentageCap) {
                    // Adjust to exactly hit the cap
                    $maxDiscountAmount = ($amount * $this->maxPercentageCap) / 100;
                    $currentAmount = $amount - $maxDiscountAmount;
                    $totalDiscountAmount = $maxDiscountAmount;
                    break;
                }
            }
        }

        $finalAmount = $this->round($currentAmount);
        $totalDiscountAmount = $this->round($amount - $finalAmount);

        return new DiscountApplicationResult(
            originalAmount: $amount,
            discountAmount: $totalDiscountAmount,
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
            return $this->round(($amount * $discount->value) / 100);
        }

        if ($discount->type === Discount::TYPE_FIXED) {
            // Fixed discount cannot exceed the current amount
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
        return 'sequential';
    }
}
