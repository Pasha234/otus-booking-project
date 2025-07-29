<?php

namespace App\Booking\Application\DTO\Basic;

use App\Booking\Domain\Entity\Resource;

class ResourceWithAvailableDTO
{
    public function __construct(
        public readonly ?string $id,
        public readonly string $name,
        public readonly int $total_quantity,
        public readonly int $available_quantity,
        public readonly bool $is_active,
        public readonly ?int $booked_quantity = null,
    )
    {
        
    }

    public static function fromEntity(Resource $resource, int $available_quantity, ?int $booked_quantity): static
    {
        return new static(
            $resource->getId(),
            $resource->getName(),
            $resource->getQuantity(),
            $available_quantity,
            $resource->isActive(),
            $booked_quantity
        );
    }
}