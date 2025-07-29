<?php

namespace App\Booking\Infrastructure\Request;

use Symfony\Component\Validator\Constraints as Assert;

class GetAvailableResourcesRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\DateTime(format: 'Y-m-d\TH:i')]
        public ?string $start_at = null,
        #[Assert\NotBlank]
        #[Assert\DateTime(format: 'Y-m-d\TH:i')]
        #[Assert\GreaterThan(propertyPath: 'start_at')]
        public ?string $end_at = null,
        #[Assert\Uuid()]
        public ?string $current_booking_id = null,
    )
    {
        
    }
}