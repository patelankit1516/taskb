<?php

namespace TaskB\UserDiscounts\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use TaskB\UserDiscounts\Contracts\DiscountRepositoryInterface;
use TaskB\UserDiscounts\Contracts\DiscountStackingStrategyInterface;
use TaskB\UserDiscounts\DTOs\DiscountApplicationResult;
use TaskB\UserDiscounts\Events\DiscountApplied;
use TaskB\UserDiscounts\Events\DiscountAssigned;
use TaskB\UserDiscounts\Events\DiscountRevoked;
use TaskB\UserDiscounts\Exceptions\DiscountException;
use TaskB\UserDiscounts\Models\Discount;
use TaskB\UserDiscounts\Models\DiscountAudit;

/**
 * Discount Service.
 * 
 * Main service class handling all discount business logic.
 * Follows Single Responsibility Principle and uses dependency injection.
 */
class DiscountService
{
    public function __construct(
        private readonly DiscountRepositoryInterface $repository,
        private readonly DiscountStackingStrategyInterface $stackingStrategy,
        private readonly bool $enableAudit = true
    ) {}

    /**
     * Assign a discount to a user.
     * 
     * @param int $userId
     * @param int $discountId
     * @param string|null $assignedBy
     * @param array $options
     * @return \TaskB\UserDiscounts\Models\UserDiscount
     * @throws DiscountException
     */
    public function assignDiscount(
        int $userId,
        int $discountId,
        ?string $assignedBy = null,
        array $options = []
    ) {
        $discount = $this->repository->find($discountId);

        if (!$discount) {
            throw DiscountException::notFound($discountId);
        }

        if (!$discount->is_active) {
            throw DiscountException::inactive($discountId);
        }

        $userDiscount = $this->repository->assignToUser(
            $userId,
            $discountId,
            $assignedBy,
            $options
        );

        // Fire event
        Event::dispatch(new DiscountAssigned(
            $userId,
            $discount,
            $assignedBy,
            $options
        ));

        // Create audit log
        if ($this->enableAudit) {
            $this->repository->createAudit([
                'user_id' => $userId,
                'discount_id' => $discountId,
                'action' => DiscountAudit::ACTION_ASSIGNED,
                'performed_by' => $assignedBy,
                'metadata' => $options,
            ]);
        }

        return $userDiscount;
    }

    /**
     * Revoke a discount from a user.
     * 
     * @param int $userId
     * @param int $discountId
     * @param string|null $revokedBy
     * @return bool
     * @throws DiscountException
     */
    public function revokeDiscount(
        int $userId,
        int $discountId,
        ?string $revokedBy = null
    ): bool {
        $discount = $this->repository->find($discountId);

        if (!$discount) {
            throw DiscountException::notFound($discountId);
        }

        $result = $this->repository->revokeFromUser($userId, $discountId, $revokedBy);

        if ($result) {
            // Fire event
            Event::dispatch(new DiscountRevoked(
                $userId,
                $discount,
                $revokedBy
            ));

            // Create audit log
            if ($this->enableAudit) {
                $this->repository->createAudit([
                    'user_id' => $userId,
                    'discount_id' => $discountId,
                    'action' => DiscountAudit::ACTION_REVOKED,
                    'performed_by' => $revokedBy,
                ]);
            }
        }

        return $result;
    }

    /**
     * Get eligible discounts for a user.
     * 
     * Returns discounts the user can currently use.
     * 
     * @param int $userId
     * @return \Illuminate\Support\Collection
     */
    public function getEligibleDiscounts(int $userId)
    {
        return $this->repository->getEligibleDiscountsForUser($userId);
    }

    /**
     * Check if a user is eligible for a specific discount.
     * 
     * @param int $userId
     * @param int $discountId
     * @return bool
     */
    public function isEligibleFor(int $userId, int $discountId): bool
    {
        $discount = $this->repository->find($discountId);

        if (!$discount || !$discount->isValid()) {
            return false;
        }

        if (!$this->repository->userHasDiscount($userId, $discountId)) {
            return false;
        }

        // Check usage limit
        $eligibleDiscounts = $this->getEligibleDiscounts($userId);
        
        return $eligibleDiscounts->contains('id', $discountId);
    }

    /**
     * Apply discounts to an amount for a user.
     * 
     * This is the main method for discount application.
     * - Uses transactions for concurrency safety
     * - Increments usage counters atomically
     * - Creates audit trail
     * - Fires events
     * 
     * @param int $userId
     * @param float $amount
     * @param array $discountIds Optional: specific discount IDs to apply
     * @return DiscountApplicationResult
     */
    public function applyDiscounts(
        int $userId,
        float $amount,
        array $discountIds = []
    ): DiscountApplicationResult {
        if ($amount <= 0) {
            return new DiscountApplicationResult(
                originalAmount: $amount,
                discountAmount: 0,
                finalAmount: $amount
            );
        }

        // Use database transaction for atomicity
        return DB::transaction(function () use ($userId, $amount, $discountIds) {
            // Get eligible discounts
            $eligibleDiscounts = $this->getEligibleDiscounts($userId);

            // Filter by specific IDs if provided
            if (!empty($discountIds)) {
                $eligibleDiscounts = $eligibleDiscounts->whereIn('id', $discountIds);
            }

            if ($eligibleDiscounts->isEmpty()) {
                return new DiscountApplicationResult(
                    originalAmount: $amount,
                    discountAmount: 0,
                    finalAmount: $amount
                );
            }

            // Apply discounts using the configured strategy
            $result = $this->stackingStrategy->apply($amount, $eligibleDiscounts);

            // Increment usage count for each applied discount (with pessimistic locking)
            foreach ($result->appliedDiscounts as $discount) {
                $this->repository->incrementUsage($userId, $discount->id);
            }

            // Create audit log
            if ($this->enableAudit) {
                foreach ($result->appliedDiscounts as $discount) {
                    $this->repository->createAudit([
                        'user_id' => $userId,
                        'discount_id' => $discount->id,
                        'action' => DiscountAudit::ACTION_APPLIED,
                        'original_amount' => $amount,
                        'discount_amount' => $result->discountAmount,
                        'final_amount' => $result->finalAmount,
                        'discount_type' => $discount->type,
                        'discount_value' => $discount->value,
                        'metadata' => [
                            'strategy' => $this->stackingStrategy->getName(),
                            'total_discounts_applied' => count($result->appliedDiscounts),
                        ],
                    ]);
                }
            }

            // Fire event
            Event::dispatch(new DiscountApplied($userId, $result));

            return $result;
        });
    }

    /**
     * Calculate what the discounted amount would be without applying.
     * 
     * Useful for previewing discounts before checkout.
     * 
     * @param int $userId
     * @param float $amount
     * @param array $discountIds
     * @return DiscountApplicationResult
     */
    public function calculateDiscounts(
        int $userId,
        float $amount,
        array $discountIds = []
    ): DiscountApplicationResult {
        if ($amount <= 0) {
            return new DiscountApplicationResult(
                originalAmount: $amount,
                discountAmount: 0,
                finalAmount: $amount
            );
        }

        $eligibleDiscounts = $this->getEligibleDiscounts($userId);

        if (!empty($discountIds)) {
            $eligibleDiscounts = $eligibleDiscounts->whereIn('id', $discountIds);
        }

        if ($eligibleDiscounts->isEmpty()) {
            return new DiscountApplicationResult(
                originalAmount: $amount,
                discountAmount: 0,
                finalAmount: $amount
            );
        }

        // Just calculate, don't apply or increment
        return $this->stackingStrategy->apply($amount, $eligibleDiscounts);
    }
}
