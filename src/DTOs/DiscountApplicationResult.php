<?php

namespace TaskB\UserDiscounts\DTOs;

use TaskB\UserDiscounts\Models\Discount;

/**
 * Data Transfer Object for discount application results.
 * 
 * Immutable object containing the result of applying discounts.
 */
class DiscountApplicationResult
{
    /**
     * Create a new instance.
     */
    public function __construct(
        public readonly float $originalAmount,
        public readonly float $discountAmount,
        public readonly float $finalAmount,
        public readonly array $appliedDiscounts = [],
        public readonly array $metadata = []
    ) {}

    /**
     * Get the discount percentage applied.
     */
    public function getDiscountPercentage(): float
    {
        if ($this->originalAmount <= 0) {
            return 0;
        }

        return ($this->discountAmount / $this->originalAmount) * 100;
    }

    /**
     * Check if any discounts were applied.
     */
    public function hasDiscounts(): bool
    {
        return count($this->appliedDiscounts) > 0;
    }

    /**
     * Get the number of discounts applied.
     */
    public function getDiscountCount(): int
    {
        return count($this->appliedDiscounts);
    }

    /**
     * Convert to array representation.
     */
    public function toArray(): array
    {
        return [
            'original_amount' => $this->originalAmount,
            'discount_amount' => $this->discountAmount,
            'final_amount' => $this->finalAmount,
            'discount_percentage' => $this->getDiscountPercentage(),
            'applied_discounts' => array_map(fn(Discount $discount) => [
                'id' => $discount->id,
                'name' => $discount->name,
                'code' => $discount->code,
                'type' => $discount->type,
                'value' => $discount->value,
            ], $this->appliedDiscounts),
            'metadata' => $this->metadata,
        ];
    }
}
