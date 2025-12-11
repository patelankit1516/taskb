<?php

namespace TaskB\UserDiscounts\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use TaskB\UserDiscounts\Models\Discount;

/**
 * Event fired when a discount is revoked from a user.
 */
class DiscountRevoked
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The user ID whose discount was revoked.
     */
    public int $userId;

    /**
     * The discount that was revoked.
     */
    public Discount $discount;

    /**
     * The user who performed the revocation.
     */
    public ?string $revokedBy;

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
        ?string $revokedBy = null,
        array $metadata = []
    ) {
        $this->userId = $userId;
        $this->discount = $discount;
        $this->revokedBy = $revokedBy;
        $this->metadata = $metadata;
    }
}
