# FULL DOCUMENTATION

## User Discounts Laravel Package - Complete Guide

This comprehensive guide covers all aspects of the User Discounts package.

## Table of Contents

1. [Installation](#installation)
2. [Configuration](#configuration)
3. [Core Concepts](#core-concepts)
4. [Usage Examples](#usage-examples)
5. [API Reference](#api-reference)
6. [Testing](#testing)
7. [Architecture](#architecture)

## Installation

### Step 1: Install Package

```bash
composer require taskb/user-discounts
```

### Step 2: Publish Assets

```bash
# Publish configuration
php artisan vendor:publish --tag=discounts-config

# Publish migrations
php artisan vendor:publish --tag=discounts-migrations
```

### Step 3: Run Migrations

```bash
php artisan migrate
```

## Configuration

Edit `config/discounts.php`:

```php
<?php

return [
    // Stacking Strategy
    // Options: 'sequential', 'best', 'all'
    'stacking_strategy' => env('DISCOUNT_STACKING_STRATEGY', 'sequential'),

    // Maximum discount percentage (0-100)
    'max_percentage_cap' => env('DISCOUNT_MAX_PERCENTAGE_CAP', 100),

    // Rounding Mode
    // Options: 'up', 'down', 'half_up', 'half_down', 'half_even'
    'rounding_mode' => env('DISCOUNT_ROUNDING_MODE', 'half_up'),

    // Rounding precision (decimal places)
    'rounding_precision' => env('DISCOUNT_ROUNDING_PRECISION', 2),

    // Enable audit logging
    'enable_audit' => env('DISCOUNT_ENABLE_AUDIT', true),

    // Queue events
    'queue_events' => env('DISCOUNT_QUEUE_EVENTS', false),
];
```

## Core Concepts

### Discount Types

#### 1. Percentage Discount
Applies a percentage reduction to the amount.

```php
$discount = Discount::create([
    'name' => '10% Off',
    'code' => 'TEN_PERCENT',
    'type' => Discount::TYPE_PERCENTAGE,
    'value' => 10.00, // 10%
    'is_active' => true,
    'max_usage_per_user' => 5,
]);
```

#### 2. Fixed Amount Discount
Applies a fixed dollar amount reduction.

```php
$discount = Discount::create([
    'name' => '$25 Off',
    'code' => 'TWENTY_FIVE',
    'type' => Discount::TYPE_FIXED,
    'value' => 25.00, // $25
    'is_active' => true,
    'max_usage_per_user' => 1,
]);
```

### Stacking Strategies

#### Sequential Strategy (Default)
Discounts are applied one after another based on priority.

**Example:**
- Original Amount: $100
- Discount 1: 10% off → $90
- Discount 2: 20% off $90 → $72
- **Total Saved: $28**

#### Best Discount Strategy
Only the best (highest value) discount is applied.

**Example:**
- Original Amount: $100
- Discount 1: 10% ($10 off)
- Discount 2: 25% ($25 off) ← Selected
- Discount 3: $15 off
- **Total Saved: $25**

#### All Discounts Strategy
All discounts are summed and applied to the original amount.

**Example:**
- Original Amount: $100
- Discount 1: 10% of $100 = $10
- Discount 2: 20% of $100 = $20
- **Total Saved: $30**

### Priority System

Discounts have a `priority` field (integer). Higher priority values are applied first in sequential mode.

```php
$discount1 = Discount::create([
    'name' => 'VIP Discount',
    'priority' => 10, // Applied first
    // ...
]);

$discount2 = Discount::create([
    'name' => 'Member Discount',
    'priority' => 5, // Applied second
    // ...
]);
```

## Usage Examples

### Example 1: E-commerce Checkout

```php
use TaskB\UserDiscounts\Services\DiscountService;

class CheckoutController extends Controller
{
    public function __construct(
        private DiscountService $discountService
    ) {}

    public function calculateTotal(Request $request)
    {
        $cartTotal = $request->input('cart_total');
        $userId = auth()->id();

        // Get eligible discounts
        $eligibleDiscounts = $this->discountService->getEligibleDiscounts($userId);

        // Preview discount
        $preview = $this->discountService->calculateDiscounts($userId, $cartTotal);

        return response()->json([
            'original_amount' => $cartTotal,
            'discount_amount' => $preview->discountAmount,
            'final_amount' => $preview->finalAmount,
            'eligible_discounts' => $eligibleDiscounts,
        ]);
    }

    public function completePurchase(Request $request)
    {
        $cartTotal = $request->input('cart_total');
        $userId = auth()->id();

        // Apply discounts (increments usage)
        $result = $this->discountService->applyDiscounts($userId, $cartTotal);

        // Process payment with final amount
        $payment = $this->processPayment($result->finalAmount);

        return response()->json([
            'payment' => $payment,
            'discount_applied' => $result->discountAmount,
            'you_saved' => $result->getDiscountPercentage() . '%',
        ]);
    }
}
```

### Example 2: Admin Discount Management

```php
use TaskB\UserDiscounts\Services\DiscountService;
use TaskB\UserDiscounts\Models\Discount;

class AdminDiscountController extends Controller
{
    public function assignDiscountToUser(Request $request, DiscountService $service)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'discount_code' => 'required|exists:discounts,code',
            'notes' => 'nullable|string',
        ]);

        $discount = Discount::where('code', $validated['discount_code'])->first();

        $service->assignDiscount(
            userId: $validated['user_id'],
            discountId: $discount->id,
            assignedBy: auth()->user()->email,
            options: [
                'notes' => $validated['notes'] ?? 'Assigned by admin',
            ]
        );

        return back()->with('success', 'Discount assigned successfully!');
    }

    public function revokeDiscountFromUser(Request $request, DiscountService $service)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'discount_id' => 'required|exists:discounts,id',
        ]);

        $service->revokeDiscount(
            userId: $validated['user_id'],
            discountId: $validated['discount_id'],
            revokedBy: auth()->user()->email
        );

        return back()->with('success', 'Discount revoked successfully!');
    }
}
```

### Example 3: Promotional Campaign

```php
use TaskB\UserDiscounts\Models\Discount;
use TaskB\UserDiscounts\Services\DiscountService;

class PromotionalCampaignService
{
    public function __construct(
        private DiscountService $discountService
    ) {}

    public function createBlackFridaySale(): Discount
    {
        return Discount::create([
            'name' => 'Black Friday 50% Off',
            'code' => 'BLACKFRIDAY2024',
            'description' => 'Annual Black Friday sale',
            'type' => Discount::TYPE_PERCENTAGE,
            'value' => 50.00,
            'starts_at' => now()->startOfDay(),
            'expires_at' => now()->addDays(3)->endOfDay(),
            'max_usage_per_user' => 1,
            'max_total_usage' => 10000,
            'is_active' => true,
            'priority' => 100,
        ]);
    }

    public function assignToVIPCustomers(Discount $discount): void
    {
        $vipCustomers = User::where('is_vip', true)->get();

        foreach ($vipCustomers as $customer) {
            $this->discountService->assignDiscount(
                userId: $customer->id,
                discountId: $discount->id,
                assignedBy: 'system',
                options: [
                    'notes' => 'VIP Black Friday exclusive access',
                ]
            );
        }
    }
}
```

### Example 4: Loyalty Program

```php
class LoyaltyProgramService
{
    public function grantTierDiscount(User $user, string $tier): void
    {
        $discountValue = match($tier) {
            'bronze' => 5.00,
            'silver' => 10.00,
            'gold' => 15.00,
            'platinum' => 20.00,
            default => 0,
        };

        if ($discountValue > 0) {
            $discount = Discount::create([
                'name' => ucfirst($tier) . ' Loyalty Discount',
                'code' => strtoupper($tier) . '_LOYALTY_' . now()->timestamp,
                'type' => Discount::TYPE_PERCENTAGE,
                'value' => $discountValue,
                'is_active' => true,
                'max_usage_per_user' => 999999, // Unlimited
                'priority' => 1,
            ]);

            app(DiscountService::class)->assignDiscount(
                userId: $user->id,
                discountId: $discount->id,
                assignedBy: 'loyalty-system'
            );
        }
    }
}
```

## API Reference

### DiscountService

#### assignDiscount()

```php
public function assignDiscount(
    int $userId,
    int $discountId,
    ?string $assignedBy = null,
    array $options = []
): UserDiscount
```

**Parameters:**
- `$userId`: The user ID to assign the discount to
- `$discountId`: The discount ID to assign
- `$assignedBy`: Optional identifier of who assigned it
- `$options`: Optional array (can include 'notes')

**Returns:** `UserDiscount` model instance

**Throws:** `DiscountException` if discount not found or inactive

---

#### revokeDiscount()

```php
public function revokeDiscount(
    int $userId,
    int $discountId,
    ?string $revokedBy = null
): bool
```

**Parameters:**
- `$userId`: The user ID
- `$discountId`: The discount ID to revoke
- `$revokedBy`: Optional identifier of who revoked it

**Returns:** `bool` - true if revoked, false if not found

---

#### getEligibleDiscounts()

```php
public function getEligibleDiscounts(int $userId): Collection
```

**Parameters:**
- `$userId`: The user ID

**Returns:** `Collection` of `Discount` models that are eligible

---

#### isEligibleFor()

```php
public function isEligibleFor(int $userId, int $discountId): bool
```

**Parameters:**
- `$userId`: The user ID
- `$discountId`: The discount ID to check

**Returns:** `bool` - true if eligible, false otherwise

---

#### applyDiscounts()

```php
public function applyDiscounts(
    int $userId,
    float $amount,
    array $discountIds = []
): DiscountApplicationResult
```

**Parameters:**
- `$userId`: The user ID
- `$amount`: The original amount before discounts
- `$discountIds`: Optional array of specific discount IDs to apply

**Returns:** `DiscountApplicationResult` DTO

**Side Effects:**
- Increments usage counter
- Creates audit log
- Dispatches `DiscountApplied` event

---

#### calculateDiscounts()

```php
public function calculateDiscounts(
    int $userId,
    float $amount,
    array $discountIds = []
): DiscountApplicationResult
```

**Parameters:**
- `$userId`: The user ID
- `$amount`: The original amount before discounts
- `$discountIds`: Optional array of specific discount IDs to calculate

**Returns:** `DiscountApplicationResult` DTO

**Note:** This does NOT increment usage or create audit logs. Use for previews.

---

### DiscountApplicationResult (DTO)

```php
readonly class DiscountApplicationResult
{
    public readonly float $originalAmount;
    public readonly float $discountAmount;
    public readonly float $finalAmount;
    public readonly array $appliedDiscounts;
    public readonly array $metadata;

    public function getDiscountPercentage(): float;
    public function hasDiscounts(): bool;
    public function getDiscountCount(): int;
    public function toArray(): array;
}
```

## Testing

### Running Tests

```bash
# All tests
./vendor/bin/phpunit

# Unit tests only
./vendor/bin/phpunit --testsuite Unit

# Feature tests only
./vendor/bin/phpunit --testsuite Feature

# With coverage
./vendor/bin/phpunit --coverage-html coverage
```

### Writing Tests

```php
use TaskB\UserDiscounts\Tests\TestCase;

class MyDiscountTest extends TestCase
{
    use RefreshDatabase;

    public function test_my_scenario(): void
    {
        $discount = Discount::create([
            'name' => 'Test',
            'code' => 'TEST',
            'type' => Discount::TYPE_PERCENTAGE,
            'value' => 10.00,
            'is_active' => true,
            'max_usage_per_user' => 1,
        ]);

        $service = app(DiscountService::class);
        $service->assignDiscount(1, $discount->id);

        $result = $service->applyDiscounts(1, 100.00);

        $this->assertEquals(10.00, $result->discountAmount);
        $this->assertEquals(90.00, $result->finalAmount);
    }
}
```

## Architecture

### Design Patterns

1. **Repository Pattern**
   - Interface: `DiscountRepositoryInterface`
   - Implementation: `DiscountRepository`
   - Purpose: Abstracts database operations

2. **Strategy Pattern**
   - Interface: `DiscountStackingStrategyInterface`
   - Implementations: `SequentialStackingStrategy`, `BestDiscountStrategy`, `AllDiscountsStrategy`
   - Purpose: Pluggable discount calculation algorithms

3. **Service Layer Pattern**
   - `DiscountService`: Orchestrates business logic
   - Purpose: Single entry point for discount operations

4. **Data Transfer Object (DTO)**
   - `DiscountApplicationResult`: Immutable result object
   - Purpose: Type-safe data transfer

5. **Observer Pattern**
   - Events: `DiscountAssigned`, `DiscountRevoked`, `DiscountApplied`
   - Purpose: Decoupled notifications

### SOLID Principles

✅ **Single Responsibility**: Each class has one reason to change
✅ **Open/Closed**: Extendable without modification (strategies)
✅ **Liskov Substitution**: Strategies are interchangeable
✅ **Interface Segregation**: Focused interfaces
✅ **Dependency Inversion**: Depends on abstractions, not concretions

### Concurrency Safety

The package uses:
1. **Database Transactions**: Atomic operations
2. **Pessimistic Locking**: `lockForUpdate()` on critical sections
3. **Idempotency**: Same operation produces same result

```php
// Example from DiscountRepository
DB::transaction(function () use ($userId, $discountId) {
    $userDiscount = UserDiscount::where('user_id', $userId)
        ->where('discount_id', $discountId)
        ->lockForUpdate() // <-- Prevents race conditions
        ->first();

    if ($userDiscount) {
        $userDiscount->increment('usage_count');
    }
});
```

## Advanced Topics

### Custom Event Listeners

```php
// In EventServiceProvider
use TaskB\UserDiscounts\Events\DiscountApplied;

protected $listen = [
    DiscountApplied::class => [
        SendDiscountNotification::class,
        TrackDiscountAnalytics::class,
        UpdateUserRewards::class,
    ],
];
```

### Queued Events

Enable in config:

```php
'queue_events' => true,
```

Then update events:

```php
use Illuminate\Contracts\Queue\ShouldQueue;

class DiscountApplied implements ShouldQueue
{
    // Event will be queued automatically
}
```

### Caching Strategies

```php
use Illuminate\Support\Facades\Cache;

class CachedDiscountService extends DiscountService
{
    public function getEligibleDiscounts(int $userId): Collection
    {
        return Cache::remember(
            "user.{$userId}.eligible_discounts",
            60,
            fn() => parent::getEligibleDiscounts($userId)
        );
    }
}
```

## Troubleshooting

### Issue: Discounts not applying

**Check:**
1. Discount is active: `is_active = true`
2. Not expired: `expires_at` is null or future
3. User has it assigned: Check `user_discounts` table
4. Not revoked: `revoked_at` is null
5. Usage limit not exceeded: `usage_count < max_usage_per_user`

### Issue: Wrong discount amount

**Check:**
1. Rounding configuration
2. Stacking strategy setting
3. Percentage cap configuration
4. Multiple discounts being applied

### Issue: Performance problems

**Solutions:**
1. Add indexes to frequently queried columns
2. Enable caching for eligible discounts
3. Use eager loading: `with('userDiscounts')`
4. Queue events for async processing

## Important Fixes Applied

### 1. Max Usage Per User Enforcement Fix

**Issue**: The query wasn't properly checking usage limits due to ambiguous column references.

**Fix Applied**: Updated `DiscountRepository::getEligibleDiscountsForUser()`
```php
// Before (broken)
->whereRaw('usage_count < max_usage_per_user')

// After (fixed)
->whereRaw('user_discounts.usage_count < discounts.max_usage_per_user')
```

### 2. Duplicate Assignment Prevention

**Issue**: Users could be assigned the same discount multiple times.

**Fix Applied**: Updated `DiscountRepository::assignToUser()`
```php
if ($existing && !$existing->revoked_at) {
    throw new DiscountException("Discount is already assigned to this user.");
}
```

### 3. Usage Count Reset on Reassignment

**Issue**: When reassigning a revoked discount, the old usage count persisted.

**Fix Applied**: Reset usage count to 0 when restoring revoked discounts.
```php
'usage_count' => 0, // Reset on reassignment
```

### 4. Audit Trail User Relationship

**Issue**: DiscountAudit model couldn't eager load user information.

**Fix Applied**: Added configurable user relationship.
```php
public function user(): BelongsTo
{
    $userModel = config('auth.providers.users.model', \App\Models\User::class);
    return $this->belongsTo($userModel);
}
```

### 5. Soft Delete Handling

**Issue**: Soft-deleted discounts caused unique constraint violations.

**Solution**: Always check with `withTrashed()` when looking for existing discounts to restore or update them.

## License

MIT License - See LICENSE file for details.

---

**For questions or support, refer to the package repository.**
