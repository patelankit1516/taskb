<?php

namespace TaskB\UserDiscounts\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * UserDiscount Model
 * 
 * Represents the relationship between a user and a discount.
 * Tracks assignment, revocation, and usage count.
 * 
 * @property int $id
 * @property int $user_id
 * @property int $discount_id
 * @property Carbon $assigned_at
 * @property Carbon|null $revoked_at
 * @property int $usage_count
 * @property string|null $assigned_by
 * @property string|null $revoked_by
 * @property string|null $notes
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class UserDiscount extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'user_discounts';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'discount_id',
        'assigned_at',
        'revoked_at',
        'usage_count',
        'assigned_by',
        'revoked_by',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'user_id' => 'integer',
        'discount_id' => 'integer',
        'assigned_at' => 'datetime',
        'revoked_at' => 'datetime',
        'usage_count' => 'integer',
    ];

    /**
     * Get the discount that this user discount belongs to.
     */
    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    /**
     * Check if this user discount has been revoked.
     */
    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    /**
     * Check if this user discount is currently active.
     */
    public function isActive(): bool
    {
        return $this->revoked_at === null;
    }

    /**
     * Check if the user has reached the maximum usage limit for this discount.
     */
    public function hasReachedUsageLimit(): bool
    {
        if (!$this->discount) {
            return true;
        }

        return $this->usage_count >= $this->discount->max_usage_per_user;
    }

    /**
     * Check if the user can use this discount.
     */
    public function canUse(): bool
    {
        return $this->isActive() 
            && !$this->hasReachedUsageLimit()
            && $this->discount 
            && $this->discount->isValid();
    }

    /**
     * Increment the usage count.
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Scope to get only active (non-revoked) user discounts.
     */
    public function scopeActive($query)
    {
        return $query->whereNull('revoked_at');
    }

    /**
     * Scope to get only revoked user discounts.
     */
    public function scopeRevoked($query)
    {
        return $query->whereNotNull('revoked_at');
    }

    /**
     * Scope to get user discounts for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get user discounts that haven't reached usage limit.
     */
    public function scopeWithinUsageLimit($query)
    {
        return $query->whereRaw('usage_count < (SELECT max_usage_per_user FROM discounts WHERE discounts.id = user_discounts.discount_id)');
    }
}
