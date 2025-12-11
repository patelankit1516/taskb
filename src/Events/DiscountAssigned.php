<?php

namespace TaskB\UserDiscounts\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use TaskB\UserDiscounts\Models\Discount;

/**
 * Event fired when a discount is assigned to a user.
 */
class DiscountAssigned
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The user ID who received the discount.
     */
    public int $userId;

    /**
     * The discount that was assigned.
     */
    public Discount $discount;

    /**
     * The user who performed the assignment.
     */
    public ?string $assignedBy;

    /**
     * Additional metadata.
     */
    public array $metadata;

    /**
     * Create a new event instance.
     */
    public function __construct(
        int $userId,
        Discount $discount,
        ?string $assignedBy = null,
        array $metadata = []
    ) {
        $this->userId = $userId;
        $this->discount = $discount;
        $this->assignedBy = $assignedBy;
        $this->metadata = $metadata;
    }
}
