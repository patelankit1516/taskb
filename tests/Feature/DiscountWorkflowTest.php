<?php

namespace TaskB\UserDiscounts\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use TaskB\UserDiscounts\Events\DiscountApplied;
use TaskB\UserDiscounts\Events\DiscountAssigned;
use TaskB\UserDiscounts\Events\DiscountRevoked;
use TaskB\UserDiscounts\Models\Discount;
use TaskB\UserDiscounts\Models\DiscountAudit;
use TaskB\UserDiscounts\Models\UserDiscount;
use TaskB\UserDiscounts\Services\DiscountService;
use TaskB\UserDiscounts\Tests\TestCase;

/**
 * Feature tests for the complete discount workflow.
 * 
 * Tests end-to-end scenarios including events and audits.
 */
class DiscountWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private DiscountService $discountService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->discountService = app(DiscountService::class);
        Event::fake();
    }

    /**
     * Test complete assign → eligible → apply workflow.
     * 
     * @test
     */
    public function it_completes_full_workflow_with_audits(): void
    {
        // Arrange
        $userId = 100;
        $discount = Discount::create([
            'name' => 'Workflow Test',
            'code' => 'WORKFLOW',
            'type' => Discount::TYPE_PERCENTAGE,
            'value' => 20.00,
            'is_active' => true,
            'max_usage_per_user' => 3,
        ]);

        // Act 1: Assign
        $userDiscount = $this->discountService->assignDiscount($userId, $discount->id, 'admin');

        // Assert 1: Assignment
        $this->assertNotNull($userDiscount);
        $this->assertEquals($userId, $userDiscount->user_id);
        $this->assertEquals($discount->id, $userDiscount->discount_id);
        Event::assertDispatched(DiscountAssigned::class);
        
        // Check audit
        $audit = DiscountAudit::where('user_id', $userId)
            ->where('action', DiscountAudit::ACTION_ASSIGNED)
            ->first();
        $this->assertNotNull($audit);

        // Act 2: Check eligibility
        $isEligible = $this->discountService->isEligibleFor($userId, $discount->id);
        $this->assertTrue($isEligible);

        // Act 3: Apply
        $result = $this->discountService->applyDiscounts($userId, 100.00);

        // Assert 3: Application
        $this->assertEquals(20.00, $result->discountAmount);
        $this->assertEquals(80.00, $result->finalAmount);
        Event::assertDispatched(DiscountApplied::class);
        
        // Check audit
        $applyAudit = DiscountAudit::where('user_id', $userId)
            ->where('action', DiscountAudit::ACTION_APPLIED)
            ->first();
        $this->assertNotNull($applyAudit);
        $this->assertEquals(100.00, $applyAudit->original_amount);
        $this->assertEquals(80.00, $applyAudit->final_amount);
    }

    /**
     * Test revoke workflow.
     * 
     * @test
     */
    public function it_revokes_discount_correctly(): void
    {
        // Arrange
        $userId = 101;
        $discount = Discount::create([
            'name' => 'Revoke Test',
            'code' => 'REVOKE_TEST',
            'type' => Discount::TYPE_PERCENTAGE,
            'value' => 15.00,
            'is_active' => true,
            'max_usage_per_user' => 5,
        ]);

        $this->discountService->assignDiscount($userId, $discount->id);

        // Act
        $revoked = $this->discountService->revokeDiscount($userId, $discount->id, 'admin');

        // Assert
        $this->assertTrue($revoked);
        Event::assertDispatched(DiscountRevoked::class);
        
        $userDiscount = UserDiscount::where('user_id', $userId)
            ->where('discount_id', $discount->id)
            ->first();
        $this->assertNotNull($userDiscount->revoked_at);
        
        // Should not be eligible anymore
        $isEligible = $this->discountService->isEligibleFor($userId, $discount->id);
        $this->assertFalse($isEligible);
        
        // Check audit
        $audit = DiscountAudit::where('user_id', $userId)
            ->where('action', DiscountAudit::ACTION_REVOKED)
            ->first();
        $this->assertNotNull($audit);
    }

    /**
     * Test eligibility checks with various conditions.
     * 
     * @test
     */
    public function it_checks_eligibility_correctly(): void
    {
        // Arrange
        $userId = 102;
        
        // Active discount
        $activeDiscount = Discount::create([
            'name' => 'Active',
            'code' => 'ACTIVE',
            'type' => Discount::TYPE_PERCENTAGE,
            'value' => 10.00,
            'is_active' => true,
            'max_usage_per_user' => 1,
        ]);

        // Expired discount
        $expiredDiscount = Discount::create([
            'name' => 'Expired',
            'code' => 'EXPIRED_CHECK',
            'type' => Discount::TYPE_PERCENTAGE,
            'value' => 10.00,
            'is_active' => true,
            'expires_at' => now()->subDay(),
            'max_usage_per_user' => 1,
        ]);

        $this->discountService->assignDiscount($userId, $activeDiscount->id);
        $this->discountService->assignDiscount($userId, $expiredDiscount->id);

        // Act & Assert
        $this->assertTrue($this->discountService->isEligibleFor($userId, $activeDiscount->id));
        $this->assertFalse($this->discountService->isEligibleFor($userId, $expiredDiscount->id));
    }

    /**
     * Test concurrent application safety.
     * 
     * @test
     */
    public function it_handles_concurrent_applications_safely(): void
    {
        // Arrange
        $userId = 103;
        $discount = Discount::create([
            'name' => 'Concurrent Test',
            'code' => 'CONCURRENT_FEATURE',
            'type' => Discount::TYPE_FIXED,
            'value' => 10.00,
            'is_active' => true,
            'max_usage_per_user' => 5,
        ]);

        $this->discountService->assignDiscount($userId, $discount->id);

        // Act - Simulate concurrent applications
        for ($i = 0; $i < 3; $i++) {
            $this->discountService->applyDiscounts($userId, 100.00);
        }

        // Assert
        $userDiscount = UserDiscount::where('user_id', $userId)
            ->where('discount_id', $discount->id)
            ->first();
        
        $this->assertEquals(3, $userDiscount->usage_count);
        
        // Check audits
        $auditCount = DiscountAudit::where('user_id', $userId)
            ->where('discount_id', $discount->id)
            ->where('action', DiscountAudit::ACTION_APPLIED)
            ->count();
        
        $this->assertEquals(3, $auditCount);
    }

    /**
     * Test calculate vs apply (preview vs actual).
     * 
     * @test
     */
    public function it_calculates_without_applying(): void
    {
        // Arrange
        $userId = 104;
        $discount = Discount::create([
            'name' => 'Calculate Test',
            'code' => 'CALC',
            'type' => Discount::TYPE_PERCENTAGE,
            'value' => 15.00,
            'is_active' => true,
            'max_usage_per_user' => 2,
        ]);

        $this->discountService->assignDiscount($userId, $discount->id);

        // Act
        $calculated = $this->discountService->calculateDiscounts($userId, 100.00);
        
        // Assert - Calculation should not increment usage
        $this->assertEquals(15.00, $calculated->discountAmount);
        $this->assertEquals(85.00, $calculated->finalAmount);
        
        $userDiscount = UserDiscount::where('user_id', $userId)
            ->where('discount_id', $discount->id)
            ->first();
        $this->assertEquals(0, $userDiscount->usage_count);
        
        // Now actually apply
        $applied = $this->discountService->applyDiscounts($userId, 100.00);
        
        // Assert - Application should increment usage
        $userDiscount->refresh();
        $this->assertEquals(1, $userDiscount->usage_count);
    }
}
