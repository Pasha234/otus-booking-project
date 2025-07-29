<?php

namespace App\Booking\Application\Query\GetResourcesForGroupWithAvailableQuantity;

use App\Shared\Application\Query\QueryInterface;

class GetResourcesForGroupWithAvailableQuantityQuery implements QueryInterface
{
    public function __construct(
        public readonly string $group_id,
        public readonly \DateTimeImmutable $start_at,
        public readonly \DateTimeImmutable $end_at,
        public readonly ?string $current_booking_id = null,
    )
    {
        
    }
}