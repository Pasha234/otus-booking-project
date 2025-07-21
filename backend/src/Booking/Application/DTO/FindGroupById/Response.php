<?php

namespace App\Booking\Application\DTO\FindGroupById;

use DateTimeImmutable;
use App\Booking\Domain\Entity\Group;
use App\User\Application\DTO\UserDTO;
use App\Booking\Domain\Entity\GroupParticipant;
use App\Booking\Application\DTO\Basic\MemberDTO;

class Response
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $description,
        public ?array $settings,
        public ?DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $updatedAt,
        public UserDTO $owner,
        public ?array $members,
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
            UserDTO::fromEntity($group->getOwner()),
            array_map(function(GroupParticipant $member) {
                return MemberDTO::fromEntity($member);
            }, $group->getMembers()->toArray())
        );
    }
}