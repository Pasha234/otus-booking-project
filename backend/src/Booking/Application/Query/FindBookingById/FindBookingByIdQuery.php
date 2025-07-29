<?php

namespace App\Booking\Application\Query\FindBookingById;

use App\Shared\Application\Query\QueryInterface;

class FindBookingByIdQuery implements QueryInterface
{
    public function __construct(
        public readonly string $id
    )
    {
        
    }
}