<?php

namespace TaskB\UserDiscounts\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Discount Model
 * 
 * Represents a discount that can be assigned to users.
 * Supports both percentage and fixed-amount discounts.
 * 
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string|null $description
 * @property string $type
 * @property float $value
 * @property Carbon|null $starts_at
 * @property Carbon|null $expires_at
 * @property int $max_usage_per_user
 * @property int|null $max_total_usage
 * @property int $current_usage
 * @property bool $is_active
 * @property int $priority
 * @property array|null $conditions
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 */
class Discount extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'discounts';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'type',
        'value',
        'starts_at',
        'expires_at',
        'max_usage_per_user',
        'max_total_usage',
        'current_usage',
        'is_active',
        'priority',
        'conditions',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'value' => 'decimal:2',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'max_usage_per_user' => 'integer',
        'max_total_usage' => 'integer',
        'current_usage' => 'integer',
        'priority' => 'integer',
        'conditions' => 'array',
    ];

    /**
     * Discount type constants
     */
    public const TYPE_PERCENTAGE = 'percentage';
    public const TYPE_FIXED = 'fixed';

    /**
     * Get the user discounts for this discount.
     */
    public function userDiscounts(): HasMany
    {
        return $this->hasMany(UserDiscount::class);
    }

    /**
     * Get the audit records for this discount.
     */
    public function audits(): HasMany
    {
        return $this->hasMany(DiscountAudit::class);
    }

    /**
     * Check if the discount is currently active and valid.
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();

        if ($this->starts_at && $now->lessThan($this->starts_at)) {
            return false;
        }

        if ($this->expires_at && $now->greaterThan($this->expires_at)) {
            return false;
        }

        if ($this->max_total_usage && $this->current_usage >= $this->max_total_usage) {
            return false;
        }

        return true;
    }

    /**
     * Check if the discount has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && now()->greaterThan($this->expires_at);
    }

    /**
     * Check if the discount is a percentage type.
     */
    public function isPercentage(): bool
    {
        return $this->type === self::TYPE_PERCENTAGE;
    }

    /**
     * Check if the discount is a fixed amount type.
     */
    public function isFixed(): bool
    {
        return $this->type === self::TYPE_FIXED;
    }

    /**
     * Scope to get only active discounts.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only valid discounts (active and within date range).
     */
    public function scopeValid($query)
    {
        $now = now();
        
        return $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', $now);
            });
    }

    /**
     * Scope to order by priority.
     */
    public function scopeOrderByPriority($query, string $direction = 'desc')
    {
        return $query->orderBy('priority', $direction);
    }
}
