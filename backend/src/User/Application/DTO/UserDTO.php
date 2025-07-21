<?php

namespace App\User\Application\DTO;

use App\User\Domain\Entity\User;

class UserDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $full_name,
        public readonly string $email,
    )
    {
        
    }

    public static function fromEntity(User $user): self
    {
        return new self(
            $user->getId(),
            $user->getFullName(),
            $user->getEmail(),
        );
    }
}