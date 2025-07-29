<?php

namespace App\Booking\Application\Command\CreateBooking;

use App\Shared\Application\Command\CommandInterface;

class CreateBookingCommand implements CommandInterface
{
    public function __construct(
        public readonly string $groupId,
        public readonly string $authorId,
        public readonly string $title,
        public readonly ?string $description,
        public readonly \DateTimeImmutable $startAt,
        public readonly \DateTimeImmutable $endAt,
        public readonly array $resources = [],
        public readonly array $userIds = [],
    ) {
    }
}