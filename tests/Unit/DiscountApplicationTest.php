<?php

namespace TaskB\UserDiscounts\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use TaskB\UserDiscounts\Models\Discount;
use TaskB\UserDiscounts\Models\UserDiscount;
use TaskB\UserDiscounts\Services\DiscountService;
use TaskB\UserDiscounts\Tests\TestCase;

/**
 * Unit tests for discount application logic and usage cap enforcement.
 * 
 * Tests the core business logic of the discount system.
 */
class DiscountApplicationTest extends TestCase
{
    use RefreshDatabase;

    private DiscountService $discountService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->discountService = app(DiscountService::class);
    }

    /**
     * Test that a percentage discount is applied correctly.
     * 
     * @test
     */
    public function it_applies_percentage_discount_correctly(): void
    {
        // Arrange
        $userId = 1;
        $discount = Discount::create([
            'name' => '10% Off',
            'code' => 'TEN_PERCENT',
            'type' => Discount::TYPE_PERCENTAGE,
            'value' => 10.00,
            'is_active' => true,
            'max_usage_per_user' => 5,
            'priority' => 1,
        ]);

        $this->discountService->assignDiscount($userId, $discount->id);

        // Act
        $result = $this->discountService->applyDiscounts($userId, 100.00);

        // Assert
        $this->assertEquals(100.00, $result->originalAmount);
        $this->assertEquals(10.00, $result->discountAmount);
        $this->assertEquals(90.00, $result->finalAmount);
        $this->assertCount(1, $result->appliedDiscounts);
        $this->assertEquals($discount->id, $result->appliedDiscounts[0]->id);
    }

    /**
     * Test that a fixed discount is applied correctly.
     * 
     * @test
     */
    public function it_applies_fixed_discount_correctly(): void
    {
        // Arrange
        $userId = 2;
        $discount = Discount::create([
            'name' => '$15 Off',
            'code' => 'FIFTEEN_OFF',
            'type' => Discount::TYPE_FIXED,
            'value' => 15.00,
            'is_active' => true,
            'max_usage_per_user' => 3,
        ]);

        $this->discountService->assignDiscount($userId, $discount->id);

        // Act
        $result = $this->discountService->applyDiscounts($userId, 100.00);

        // Assert
        $this->assertEquals(100.00, $result->originalAmount);
        $this->assertEquals(15.00, $result->discountAmount);
        $this->assertEquals(85.00, $result->finalAmount);
    }

    /**
     * Test that expired discounts are not applied.
     * 
     * @test
     */
    public function it_does_not_apply_expired_discounts(): void
    {
        // Arrange
        $userId = 3;
        $discount = Discount::create([
            'name' => 'Expired Discount',
            'code' => 'EXPIRED',
            'type' => Discount::TYPE_PERCENTAGE,
            'value' => 20.00,
            'is_active' => true,
            'expires_at' => now()->subDay(),
            'max_usage_per_user' => 1,
        ]);

        $this->discountService->assignDiscount($userId, $discount->id);

        // Act
        $result = $this->discountService->applyDiscounts($userId, 100.00);

        // Assert
        $this->assertEquals(100.00, $result->originalAmount);
        $this->assertEquals(0.00, $result->discountAmount);
        $this->assertEquals(100.00, $result->finalAmount);
        $this->assertCount(0, $result->appliedDiscounts);
    }

    /**
     * Test that inactive discounts are not applied.
     * 
     * @test
     */
    public function it_does_not_apply_inactive_discounts(): void
    {
        // Arrange
        $userId = 4;
        $discount = Discount::create([
            'name' => 'Inactive Discount',
            'code' => 'INACTIVE',
            'type' => Discount::TYPE_PERCENTAGE,
            'value' => 25.00,
            'is_active' => false,
            'max_usage_per_user' => 1,
        ]);

        $this->discountService->assignDiscount($userId, $discount->id);

        // Act
        $result = $this->discountService->applyDiscounts($userId, 100.00);

        // Assert
        $this->assertEquals(0.00, $result->discountAmount);
        $this->assertCount(0, $result->appliedDiscounts);
    }

    /**
     * Test that usage cap is enforced correctly.
     * 
     * @test
     */
    public function it_enforces_usage_cap_per_user(): void
    {
        // Arrange
        $userId = 5;
        $discount = Discount::create([
            'name' => 'Limited Use',
            'code' => 'LIMITED',
            'type' => Discount::TYPE_PERCENTAGE,
            'value' => 10.00,
            'is_active' => true,
            'max_usage_per_user' => 2,
        ]);

        $this->discountService->assignDiscount($userId, $discount->id);

        // Act - First application
        $result1 = $this->discountService->applyDiscounts($userId, 100.00);
        $this->assertEquals(10.00, $result1->discountAmount);

        // Act - Second application
        $result2 = $this->discountService->applyDiscounts($userId, 100.00);
        $this->assertEquals(10.00, $result2->discountAmount);

        // Act - Third application (should fail due to usage cap)
        $result3 = $this->discountService->applyDiscounts($userId, 100.00);
        
        // Assert
        $this->assertEquals(0.00, $result3->discountAmount);
        $this->assertEquals(100.00, $result3->finalAmount);
        $this->assertCount(0, $result3->appliedDiscounts);

        // Verify usage count
        $userDiscount = UserDiscount::where('user_id', $userId)
            ->where('discount_id', $discount->id)
            ->first();
        $this->assertEquals(2, $userDiscount->usage_count);
    }

    /**
     * Test that revoked discounts are not applied.
     * 
     * @test
     */
    public function it_does_not_apply_revoked_discounts(): void
    {
        // Arrange
        $userId = 6;
        $discount = Discount::create([
            'name' => 'Revoked Discount',
            'code' => 'REVOKED',
            'type' => Discount::TYPE_PERCENTAGE,
            'value' => 15.00,
            'is_active' => true,
            'max_usage_per_user' => 5,
        ]);

        $this->discountService->assignDiscount($userId, $discount->id);
        $this->discountService->revokeDiscount($userId, $discount->id);

        // Act
        $result = $this->discountService->applyDiscounts($userId, 100.00);

        // Assert
        $this->assertEquals(0.00, $result->discountAmount);
        $this->assertCount(0, $result->appliedDiscounts);
    }

    /**
     * Test sequential stacking strategy.
     * 
     * @test
     */
    public function it_stacks_discounts_sequentially(): void
    {
        // Arrange
        $userId = 7;
        
        $discount1 = Discount::create([
            'name' => '10% Off',
            'code' => 'TEN',
            'type' => Discount::TYPE_PERCENTAGE,
            'value' => 10.00,
            'is_active' => true,
            'max_usage_per_user' => 5,
            'priority' => 2,
        ]);

        $discount2 = Discount::create([
            'name' => '20% Off',
            'code' => 'TWENTY',
            'type' => Discount::TYPE_PERCENTAGE,
            'value' => 20.00,
            'is_active' => true,
            'max_usage_per_user' => 5,
            'priority' => 1,
        ]);

        $this->discountService->assignDiscount($userId, $discount1->id);
        $this->discountService->assignDiscount($userId, $discount2->id);

        // Act
        $result = $this->discountService->applyDiscounts($userId, 100.00);

        // Assert
        // 100 * 0.9 (10% off) = 90
        // 90 * 0.8 (20% off) = 72
        // Total discount = 28
        $this->assertEquals(100.00, $result->originalAmount);
        $this->assertEquals(28.00, $result->discountAmount);
        $this->assertEquals(72.00, $result->finalAmount);
        $this->assertCount(2, $result->appliedDiscounts);
    }

    /**
     * Test that fixed discount cannot exceed original amount.
     * 
     * @test
     */
    public function it_limits_fixed_discount_to_original_amount(): void
    {
        // Arrange
        $userId = 8;
        $discount = Discount::create([
            'name' => '$100 Off',
            'code' => 'HUNDRED_OFF',
            'type' => Discount::TYPE_FIXED,
            'value' => 100.00,
            'is_active' => true,
            'max_usage_per_user' => 1,
        ]);

        $this->discountService->assignDiscount($userId, $discount->id);

        // Act
        $result = $this->discountService->applyDiscounts($userId, 50.00);

        // Assert - Discount should be limited to $50, not $100
        $this->assertEquals(50.00, $result->originalAmount);
        $this->assertEquals(50.00, $result->discountAmount);
        $this->assertEquals(0.00, $result->finalAmount);
    }

    /**
     * Test rounding is applied correctly.
     * 
     * @test
     */
    public function it_applies_rounding_correctly(): void
    {
        // Arrange
        $userId = 9;
        $discount = Discount::create([
            'name' => '33.33% Off',
            'code' => 'THIRD_OFF',
            'type' => Discount::TYPE_PERCENTAGE,
            'value' => 33.33,
            'is_active' => true,
            'max_usage_per_user' => 1,
        ]);

        $this->discountService->assignDiscount($userId, $discount->id);

        // Act
        $result = $this->discountService->applyDiscounts($userId, 100.00);

        // Assert - Should be rounded to 2 decimal places
        $this->assertEquals(33.33, $result->discountAmount);
        $this->assertEquals(66.67, $result->finalAmount);
    }

    /**
     * Test concurrent application safety (simulated).
     * 
     * @test
     */
    public function it_prevents_double_increment_on_concurrent_applications(): void
    {
        // Arrange
        $userId = 10;
        $discount = Discount::create([
            'name' => 'Concurrent Test',
            'code' => 'CONCURRENT',
            'type' => Discount::TYPE_PERCENTAGE,
            'value' => 10.00,
            'is_active' => true,
            'max_usage_per_user' => 3,
        ]);

        $this->discountService->assignDiscount($userId, $discount->id);

        // Act - Apply multiple times
        $this->discountService->applyDiscounts($userId, 100.00);
        $this->discountService->applyDiscounts($userId, 100.00);

        // Assert
        $userDiscount = UserDiscount::where('user_id', $userId)
            ->where('discount_id', $discount->id)
            ->first();
        
        // Usage count should be exactly 2, not more due to race conditions
        $this->assertEquals(2, $userDiscount->usage_count);
    }
}
