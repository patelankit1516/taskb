<?php

namespace TaskB\UserDiscounts\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use TaskB\UserDiscounts\Models\Discount;
use TaskB\UserDiscounts\Strategies\AllDiscountsStrategy;
use TaskB\UserDiscounts\Strategies\BestDiscountStrategy;
use TaskB\UserDiscounts\Strategies\SequentialStackingStrategy;
use TaskB\UserDiscounts\Tests\TestCase;

/**
 * Unit tests for stacking strategies.
 * 
 * Tests the different discount stacking algorithms in isolation.
 */
class StackingStrategyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test sequential stacking strategy.
     * 
     * @test
     */
    public function it_applies_sequential_strategy_correctly(): void
    {
        // Arrange
        $strategy = new SequentialStackingStrategy(100, 'half_up', 2);
        
        $discounts = collect([
            Discount::make([
                'id' => 1,
                'name' => 'First 10%',
                'type' => Discount::TYPE_PERCENTAGE,
                'value' => 10.00,
                'priority' => 2,
            ]),
            Discount::make([
                'id' => 2,
                'name' => 'Second 20%',
                'type' => Discount::TYPE_PERCENTAGE,
                'value' => 20.00,
                'priority' => 1,
            ]),
        ]);

        // Act
        $result = $strategy->apply(100.00, $discounts);

        // Assert
        // Higher priority (2) applied first: 100 * 0.9 = 90
        // Lower priority (1) applied second: 90 * 0.8 = 72
        $this->assertEquals(100.00, $result->originalAmount);
        $this->assertEquals(28.00, $result->discountAmount);
        $this->assertEquals(72.00, $result->finalAmount);
    }

    /**
     * Test best discount strategy.
     * 
     * @test
     */
    public function it_applies_best_discount_strategy_correctly(): void
    {
        // Arrange
        $strategy = new BestDiscountStrategy('half_up', 2);
        
        $discounts = collect([
            Discount::make([
                'id' => 1,
                'name' => '10%',
                'type' => Discount::TYPE_PERCENTAGE,
                'value' => 10.00,
            ]),
            Discount::make([
                'id' => 2,
                'name' => '25%',
                'type' => Discount::TYPE_PERCENTAGE,
                'value' => 25.00,
            ]),
            Discount::make([
                'id' => 3,
                'name' => '$15 Fixed',
                'type' => Discount::TYPE_FIXED,
                'value' => 15.00,
            ]),
        ]);

        // Act
        $result = $strategy->apply(100.00, $discounts);

        // Assert - 25% should be selected as it gives $25 discount
        $this->assertEquals(100.00, $result->originalAmount);
        $this->assertEquals(25.00, $result->discountAmount);
        $this->assertEquals(75.00, $result->finalAmount);
        $this->assertCount(1, $result->appliedDiscounts);
        $this->assertEquals(2, $result->appliedDiscounts[0]->id);
    }

    /**
     * Test all discounts strategy.
     * 
     * @test
     */
    public function it_applies_all_discounts_strategy_correctly(): void
    {
        // Arrange
        $strategy = new AllDiscountsStrategy(100, 'half_up', 2);
        
        $discounts = collect([
            Discount::make([
                'id' => 1,
                'name' => '10%',
                'type' => Discount::TYPE_PERCENTAGE,
                'value' => 10.00,
            ]),
            Discount::make([
                'id' => 2,
                'name' => '20%',
                'type' => Discount::TYPE_PERCENTAGE,
                'value' => 20.00,
            ]),
        ]);

        // Act
        $result = $strategy->apply(100.00, $discounts);

        // Assert - Both percentages applied to original amount
        // 10% of 100 = 10
        // 20% of 100 = 20
        // Total = 30
        $this->assertEquals(100.00, $result->originalAmount);
        $this->assertEquals(30.00, $result->discountAmount);
        $this->assertEquals(70.00, $result->finalAmount);
    }

    /**
     * Test percentage cap enforcement in sequential strategy.
     * 
     * @test
     */
    public function it_enforces_percentage_cap_in_sequential_strategy(): void
    {
        // Arrange
        $strategy = new SequentialStackingStrategy(50, 'half_up', 2);
        
        $discounts = collect([
            Discount::make([
                'id' => 1,
                'type' => Discount::TYPE_PERCENTAGE,
                'value' => 40.00,
                'priority' => 2,
            ]),
            Discount::make([
                'id' => 2,
                'type' => Discount::TYPE_PERCENTAGE,
                'value' => 30.00,
                'priority' => 1,
            ]),
        ]);

        // Act
        $result = $strategy->apply(100.00, $discounts);

        // Assert - Should stop at 50% cap
        $this->assertEquals(100.00, $result->originalAmount);
        $this->assertEquals(50.00, $result->discountAmount);
        $this->assertEquals(50.00, $result->finalAmount);
    }

    /**
     * Test percentage cap enforcement in all strategy.
     * 
     * @test
     */
    public function it_enforces_percentage_cap_in_all_strategy(): void
    {
        // Arrange
        $strategy = new AllDiscountsStrategy(80, 'half_up', 2);
        
        $discounts = collect([
            Discount::make([
                'id' => 1,
                'type' => Discount::TYPE_PERCENTAGE,
                'value' => 50.00,
            ]),
            Discount::make([
                'id' => 2,
                'type' => Discount::TYPE_PERCENTAGE,
                'value' => 40.00,
            ]),
        ]);

        // Act
        $result = $strategy->apply(100.00, $discounts);

        // Assert - 50% + 40% = 90%, but capped at 80%
        $this->assertEquals(100.00, $result->originalAmount);
        $this->assertEquals(80.00, $result->discountAmount);
        $this->assertEquals(20.00, $result->finalAmount);
    }
}
