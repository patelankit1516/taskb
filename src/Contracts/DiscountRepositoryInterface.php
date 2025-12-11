<?php

namespace TaskB\UserDiscounts\Contracts;

use Illuminate\Support\Collection;

/**
 * Interface for discount repository operations.
 * 
 * Follows the Repository Pattern for data access abstraction.
 */
interface DiscountRepositoryInterface
{
    /**
     * Find a discount by ID.
     *
     * @param int $id
     * @return \TaskB\UserDiscounts\Models\Discount|null
     */
    public function find(int $id);

    /**
     * Find a discount by code.
     *
     * @param string $code
     * @return \TaskB\UserDiscounts\Models\Discount|null
     */
    public function findByCode(string $code);

    /**
     * Get all active and valid discounts.
     *
     * @return Collection
     */
    public function getValidDiscounts(): Collection;

    /**
     * Get eligible discounts for a user.
     *
     * @param int $userId
     * @return Collection
     */
    public function getEligibleDiscountsForUser(int $userId): Collection;

    /**
     * Assign a discount to a user.
     *
     * @param int $userId
     * @param int $discountId
     * @param string|null $assignedBy
     * @param array $options
     * @return \TaskB\UserDiscounts\Models\UserDiscount
     */
    public function assignToUser(int $userId, int $discountId, ?string $assignedBy = null, array $options = []);

    /**
     * Revoke a discount from a user.
     *
     * @param int $userId
     * @param int $discountId
     * @param string|null $revokedBy
     * @return bool
     */
    public function revokeFromUser(int $userId, int $discountId, ?string $revokedBy = null): bool;

    /**
     * Check if a user has a specific discount assigned and active.
     *
     * @param int $userId
     * @param int $discountId
     * @return bool
     */
    public function userHasDiscount(int $userId, int $discountId): bool;

    /**
     * Increment the usage count for a user discount.
     *
     * @param int $userId
     * @param int $discountId
     * @return void
     */
    public function incrementUsage(int $userId, int $discountId): void;

    /**
     * Create an audit log entry.
     *
     * @param array $data
     * @return \TaskB\UserDiscounts\Models\DiscountAudit
     */
    public function createAudit(array $data);
}
