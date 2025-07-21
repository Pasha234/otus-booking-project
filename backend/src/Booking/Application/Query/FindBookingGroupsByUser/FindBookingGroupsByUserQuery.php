<?php

namespace App\Booking\Application\Query\FindBookingGroupsByUser;

use App\Shared\Application\Query\QueryInterface;

class FindBookingGroupsByUserQuery implements QueryInterface
{
    public function __construct(public readonly string $user_id)
    {
    }
}