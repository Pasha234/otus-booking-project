<?php

namespace App\Booking\Application\Query\GetBookingList;

use App\Shared\Application\Query\QueryInterface;

class GetBookingListQuery implements QueryInterface
{
    public function __construct(
        public readonly string $group_id,
        public readonly string $start_at,
        public readonly string $end_at,
        public readonly ?string $user_id = null,
    )
    {
        
    }
}