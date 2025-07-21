<?php

namespace App\Booking\Application\DTO\Basic;

use App\Booking\Domain\Entity\Group;
use App\User\Domain\Entity\User;

class InvitationUserDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $full_name,
        public readonly string $email,
        public readonly bool $is_invited
    )
    {
        
    }

    public static function fromEntity(User $user, Group $group): static
    {
        return new static(
            $user->getId(),
            $user->getFullName(),
            $user->getEmail(),
            $user->hasPendingInvitation($group),
        );
    }
}