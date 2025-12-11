<?php

namespace TaskB\UserDiscounts\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use TaskB\UserDiscounts\DTOs\DiscountApplicationResult;

/**
 * Event fired when discounts are successfully applied to an amount.
 */
class DiscountApplied
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The user ID who used the discount.
     */
    public int $userId;

    /**
     * The result of the discount application.
     */
    public DiscountApplicationResult $result;

    /**
     * Additional metadata.
     */
    public array $metadata;

    /**
     * Create a new event instance.
     */
    public function __construct(
        int $userId,
        DiscountApplicationResult $result,
        array $metadata = []
    ) {
        $this->userId = $userId;
        $this->result = $result;
        $this->metadata = $metadata;
    }
}
