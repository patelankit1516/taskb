<?php

namespace TaskB\UserDiscounts\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \TaskB\UserDiscounts\Models\UserDiscount assignDiscount(int $userId, int $discountId, ?string $assignedBy = null, array $options = [])
 * @method static bool revokeDiscount(int $userId, int $discountId, ?string $revokedBy = null)
 * @method static \Illuminate\Support\Collection getEligibleDiscounts(int $userId)
 * @method static bool isEligibleFor(int $userId, int $discountId)
 * @method static \TaskB\UserDiscounts\DTOs\DiscountApplicationResult applyDiscounts(int $userId, float $amount, array $discountIds = [])
 * @method static \TaskB\UserDiscounts\DTOs\DiscountApplicationResult calculateDiscounts(int $userId, float $amount, array $discountIds = [])
 *
 * @see \TaskB\UserDiscounts\Services\DiscountService
 */
class Discount extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'discount.service';
    }
}
