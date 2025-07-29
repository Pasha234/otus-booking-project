<?php

namespace App\Booking\Application\Command\DeleteBooking;

use App\Shared\Application\Command\CommandInterface;

class DeleteBookingCommand implements CommandInterface
{
    public function __construct(
        public readonly string $id,
    )
    {
        
    }
}