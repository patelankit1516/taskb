<?php

namespace TaskB\UserDiscounts\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use TaskB\UserDiscounts\Contracts\DiscountRepositoryInterface;
use TaskB\UserDiscounts\Models\Discount;
use TaskB\UserDiscounts\Models\DiscountAudit;
use TaskB\UserDiscounts\Models\UserDiscount;

/**
 * Discount Repository Implementation.
 * 
 * Handles all database operations for discounts, user discounts, and audits.
 * Implements the Repository Pattern for clean separation of concerns.
 */
class DiscountRepository implements DiscountRepositoryInterface
{
    /**
     * Find a discount by ID.
     */
    public function find(int $id): ?Discount
    {
        return Discount::find($id);
    }

    /**
     * Find a discount by code.
     */
    public function findByCode(string $code): ?Discount
    {
        return Discount::where('code', $code)->first();
    }

    /**
     * Get all active and valid discounts.
     */
    public function getValidDiscounts(): Collection
    {
        return Discount::valid()
            ->orderByPriority('desc')
            ->get();
    }

    /**
     * Get eligible discounts for a user.
     * 
     * Returns discounts that are:
     * - Assigned to the user
     * - Not revoked
     * - Active and within date range
     * - Not exceeded usage limit
     */
    public function getEligibleDiscountsForUser(int $userId): Collection
    {
        return Discount::valid()
            ->whereHas('userDiscounts', function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->whereNull('revoked_at')
                    ->whereRaw('user_discounts.usage_count < discounts.max_usage_per_user');
            })
            ->with(['userDiscounts' => function ($query) use ($userId) {
                $query->where('user_id', $userId);
            }])
            ->orderByPriority('desc')
            ->get();
    }

    /**
     * Assign a discount to a user.
     * 
     * Uses upsert to handle race conditions gracefully.
     */
    public function assignToUser(
        int $userId,
        int $discountId,
        ?string $assignedBy = null,
        array $options = []
    ): UserDiscount {
        $now = now();
        
        // Try to find existing assignment
        $existing = UserDiscount::where('user_id', $userId)
            ->where('discount_id', $discountId)
            ->first();

        if ($existing) {
            // If it's already assigned and not revoked, throw exception
            if (!$existing->revoked_at) {
                throw new \TaskB\UserDiscounts\Exceptions\DiscountException(
                    "Discount is already assigned to this user."
                );
            }
            
            // If it was revoked, we can "reassign" by clearing revoked_at
            $existing->update([
                'revoked_at' => null,
                'revoked_by' => null,
                'assigned_at' => $now,
                'assigned_by' => $assignedBy,
                'usage_count' => 0, // Reset usage count on reassignment
                'notes' => $options['notes'] ?? null,
            ]);
            
            return $existing->fresh();
        }

        // Create new assignment
        return UserDiscount::create([
            'user_id' => $userId,
            'discount_id' => $discountId,
            'assigned_at' => $now,
            'assigned_by' => $assignedBy,
            'usage_count' => 0,
            'notes' => $options['notes'] ?? null,
        ]);
    }

    /**
     * Revoke a discount from a user.
     */
    public function revokeFromUser(int $userId, int $discountId, ?string $revokedBy = null): bool
    {
        $userDiscount = UserDiscount::where('user_id', $userId)
            ->where('discount_id', $discountId)
            ->whereNull('revoked_at')
            ->first();

        if (!$userDiscount) {
            return false;
        }

        return $userDiscount->update([
            'revoked_at' => now(),
            'revoked_by' => $revokedBy,
        ]);
    }

    /**
     * Check if a user has a specific discount assigned and active.
     */
    public function userHasDiscount(int $userId, int $discountId): bool
    {
        return UserDiscount::where('user_id', $userId)
            ->where('discount_id', $discountId)
            ->whereNull('revoked_at')
            ->exists();
    }

    /**
     * Increment the usage count for a user discount.
     * 
     * Uses lockForUpdate to prevent concurrent increments.
     */
    public function incrementUsage(int $userId, int $discountId): void
    {
        DB::transaction(function () use ($userId, $discountId) {
            $userDiscount = UserDiscount::where('user_id', $userId)
                ->where('discount_id', $discountId)
                ->whereNull('revoked_at')
                ->lockForUpdate()
                ->first();

            if ($userDiscount) {
                $userDiscount->increment('usage_count');
            }
        });
    }

    /**
     * Create an audit log entry.
     */
    public function createAudit(array $data): DiscountAudit
    {
        $data['created_at'] = $data['created_at'] ?? now();
        $data['ip_address'] = $data['ip_address'] ?? request()->ip();
        
        return DiscountAudit::create($data);
    }

    /**
     * Get user discount with lock for update.
     * 
     * Used for concurrent-safe operations.
     */
    public function getUserDiscountForUpdate(int $userId, int $discountId): ?UserDiscount
    {
        return UserDiscount::where('user_id', $userId)
            ->where('discount_id', $discountId)
            ->whereNull('revoked_at')
            ->lockForUpdate()
            ->first();
    }
}
