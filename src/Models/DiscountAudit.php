<?php

namespace TaskB\UserDiscounts\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * DiscountAudit Model
 * 
 * Represents an audit log entry for discount operations.
 * Provides a complete trail of all discount-related actions.
 * 
 * @property int $id
 * @property int $user_id
 * @property int $discount_id
 * @property string $action
 * @property float|null $original_amount
 * @property float|null $discount_amount
 * @property float|null $final_amount
 * @property string|null $discount_type
 * @property float|null $discount_value
 * @property array|null $metadata
 * @property string|null $performed_by
 * @property string|null $ip_address
 * @property Carbon $created_at
 */
class DiscountAudit extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'discount_audits';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'discount_id',
        'action',
        'original_amount',
        'discount_amount',
        'final_amount',
        'discount_type',
        'discount_value',
        'metadata',
        'performed_by',
        'ip_address',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'user_id' => 'integer',
        'discount_id' => 'integer',
        'original_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Action type constants
     */
    public const ACTION_ASSIGNED = 'assigned';
    public const ACTION_REVOKED = 'revoked';
    public const ACTION_APPLIED = 'applied';
    public const ACTION_FAILED = 'failed';

    /**
     * Get the discount that this audit belongs to.
     */
    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    /**
     * Get the user that this audit belongs to.
     */
    public function user(): BelongsTo
    {
        $userModel = config('auth.providers.users.model', \App\Models\User::class);
        return $this->belongsTo($userModel);
    }

    /**
     * Scope to get audits for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get audits for a specific discount.
     */
    public function scopeForDiscount($query, int $discountId)
    {
        return $query->where('discount_id', $discountId);
    }

    /**
     * Scope to get audits by action type.
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope to get audits within a date range.
     */
    public function scopeWithinDateRange($query, Carbon $startDate, Carbon $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
}
