<?php

namespace App\Booking\Infrastructure\Request;

use Symfony\Component\Validator\Constraints as Assert;

class CreateResourceRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 255)]
    public ?string $name = null;

    #[Assert\NotBlank]
    #[Assert\Type('numeric')]
    #[Assert\GreaterThan(0)]
    public ?string $quantity = null;
}