<?php

namespace App\Booking\Infrastructure\Request;

use Symfony\Component\Validator\Constraints as Assert;

class CreateBookingRequest
{
    public function __construct(
        #[Assert\NotBlank]
        public ?string $title = null,
        public ?string $description = null,
        #[Assert\NotBlank]
        #[Assert\DateTime(format: 'Y-m-d\TH:i')]
        public ?string $start_at = null,
        #[Assert\NotBlank]
        #[Assert\DateTime(format: 'Y-m-d\TH:i')]
        #[Assert\GreaterThan(propertyPath: 'start_at')]
        public ?string $end_at = null,
        #[Assert\Type('array')]
        public array $quantity = [],
        #[Assert\All(
            new Assert\Uuid(),
        )]
        #[Assert\Count(min: 1, minMessage: "You must select at least one participant.")]
        public array $participants = [],
    )
    {
        
    }
}