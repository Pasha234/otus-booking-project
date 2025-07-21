<?php

namespace App\Booking\Infrastructure\Request;

use Symfony\Component\Validator\Constraints as Assert;

class CreateBookingGroupRequest
{
    public function __construct(
        #[Assert\NotBlank()]
        public readonly string $name = '',
        public readonly string $description = '',
    ) {}
}