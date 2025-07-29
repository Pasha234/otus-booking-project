<?php

namespace App\Booking\Application\DTO\Basic;

use App\User\Domain\Entity\User;
use App\Booking\Domain\Entity\Group;
use App\Booking\Domain\Entity\Resource;

class ResourceDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly int $quantity,
        public readonly bool $is_active,
    )
    {
        
    }

    public static function fromEntity(Resource $resource): static
    {
        return new static(
            $resource->getId(),
            $resource->getName(),
            $resource->getQuantity(),
            $resource->isActive(),
        );
    }
}