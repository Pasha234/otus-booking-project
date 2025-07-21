<?php

namespace App\Booking\Application\Command\CreateBookingGroup;

use App\Shared\Application\Command\CommandInterface;

class CreateBookingGroupCommand implements CommandInterface
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $description,
        public readonly string $ownerEmail,
    ) {}
}