<?php

namespace App\Booking\Application\DTO\Basic;

use App\Booking\Domain\Entity\BookedResource;

class BookedResourceDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $quantity,
        public readonly ResourceDTO $resource,
    )
    {
        
    }

    public static function fromEntity(BookedResource $bookedResource): static
    {
        return new static(
            $bookedResource->getId(),
            $bookedResource->getQuantity(),
            ResourceDTO::fromEntity($bookedResource->getResource()),
        );
    }
}