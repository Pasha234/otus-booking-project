<?php

namespace App\User\Application\DTO;

use App\User\Domain\Entity\Invitation;
use DateTimeImmutable;

class InvitationDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $invited_email,
        public readonly string $status,
        public readonly ?DateTimeImmutable $expires_at,
        public readonly string $group_name,
        public readonly string $invitee_name,
    )
    {
        
    }

    public static function fromEntity(Invitation $invitation): static
    {
        return new static(
            $invitation->getId(),
            $invitation->getInvitedEmail(),
            $invitation->getStatus()->label(),
            $invitation->getExpiresAt(),
            $invitation->getGroup()->getName(),
            $invitation->getInvitee()->getFullName(),
        );
    }
}