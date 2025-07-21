<?php

namespace App\Booking\Application\DTO\Basic;

use DateTimeImmutable;
use App\Booking\Domain\Entity\GroupParticipant;

class MemberDTO
{
    public function __construct(
        public readonly ?string $user_id,
        public readonly ?string $name,
        public readonly ?DateTimeImmutable $joined_at,
        public readonly ?DateTimeImmutable $banned_at,
    )
    {
        
    }

    public static function fromEntity(GroupParticipant $groupParticipant): self
    {
        return new self(
            $groupParticipant->getUser()?->getId(),
            $groupParticipant->getUser()?->getFullName(),
            $groupParticipant->getJoinedAt(),
            $groupParticipant->getBannedAt(),
        );
    }
}