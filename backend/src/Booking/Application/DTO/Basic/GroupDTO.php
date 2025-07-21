<?php

namespace App\Booking\Application\DTO\Basic;

use App\Booking\Domain\Entity\Group;
use DateTimeImmutable;

class GroupDTO
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $description,
        public ?array $settings,
        public ?DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $updatedAt,
    )
    {}

    public static function fromEntity(Group $group): self
    {
        return new self(
            $group->getId(),
            $group->getName(),
            $group->getDescription(),
            $group->getSettings(),
            $group->getCreatedAt(),
            $group->getUpdatedAt(),
        );
    }
}