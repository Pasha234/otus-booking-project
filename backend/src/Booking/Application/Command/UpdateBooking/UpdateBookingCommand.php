<?php

namespace App\Booking\Application\Command\UpdateBooking;

use App\Shared\Application\Command\CommandInterface;

class UpdateBookingCommand implements CommandInterface
{
    public function __construct(
        public readonly ?string $id = null,
        public readonly ?string $title = null,
        public readonly ?string $description = null,
        public readonly ?\DateTimeImmutable $startAt = null,
        public readonly ?\DateTimeImmutable $endAt = null,
        public readonly ?array $resources = null,
        public readonly ?array $userIds = null,
    )
    {
        
    }
}